<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Domain\Enums\Perfil;
use App\Domain\Enums\Permissao;
use App\Domain\Models\ContaAcesso;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\NaoAutenticadoException;
use App\Http\Request;
use App\Http\Response;
use App\Middleware\AutenticacaoMiddleware;
use App\Middleware\AutorizacaoMiddleware;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\ContaAcessoRepositoryEmMemoria;
use Tests\Fakes\Exemplos;
use Tests\Fakes\RelogioFixo;
use Tests\Fakes\TokenAcessoRepositoryEmMemoria;

final class ControleDeAcessoTest extends TestCase
{
    private AuthService $autenticacao;

    protected function setUp(): void
    {
        $contas = new ContaAcessoRepositoryEmMemoria();
        $contas->salvar(ContaAcesso::criar('Ana Souza', 'ana@email.com', 'Leitor@123', Perfil::LEITOR, 1));

        $this->autenticacao = new AuthService($contas, new TokenAcessoRepositoryEmMemoria(), new RelogioFixo());
    }

    public function testRequisicaoSemTokenEhBarrada(): void
    {
        $this->expectException(NaoAutenticadoException::class);

        (new AutenticacaoMiddleware($this->autenticacao))->processar(new Request('GET', '/api/materiais'), $this->destino());
    }

    public function testTokenInventadoEhBarrado(): void
    {
        $requisicao = new Request('GET', '/api/materiais', [], '', ['Authorization' => 'Bearer ' . str_repeat('a', 64)]);

        $this->expectException(NaoAutenticadoException::class);

        (new AutenticacaoMiddleware($this->autenticacao))->processar($requisicao, $this->destino());
    }

    public function testTokenValidoIdentificaAConta(): void
    {
        $token = $this->autenticacao->login('ana@email.com', 'Leitor@123')['token'];
        $requisicao = new Request('GET', '/api/materiais', [], '', ['Authorization' => "Bearer {$token}"]);

        $resposta = (new AutenticacaoMiddleware($this->autenticacao))->processar($requisicao, $this->destino());

        $this->assertSame(200, $resposta->status());
        $this->assertSame(Perfil::LEITOR, $requisicao->conta()->perfil());
    }

    public function testLeitorNaoPassaPelaAutorizacaoDeCadastroDeMaterial(): void
    {
        $requisicao = new Request('POST', '/api/materiais');
        $requisicao->autenticarComo(Exemplos::conta(Perfil::LEITOR, 1));

        $this->expectException(AcessoNegadoException::class);

        (new AutorizacaoMiddleware(Permissao::CADASTRAR_MATERIAIS))->processar($requisicao, $this->destino());
    }

    public function testBibliotecarioNaoAcessaRelatorios(): void
    {
        $requisicao = new Request('GET', '/api/relatorios/resumo');
        $requisicao->autenticarComo(Exemplos::conta(Perfil::BIBLIOTECARIO));

        $this->expectException(AcessoNegadoException::class);

        (new AutorizacaoMiddleware(Permissao::CONSULTAR_RELATORIOS))->processar($requisicao, $this->destino());
    }

    public function testAdministradorPassaPelaAutorizacao(): void
    {
        $requisicao = new Request('GET', '/api/relatorios/resumo');
        $requisicao->autenticarComo(Exemplos::conta(Perfil::ADMINISTRADOR));

        $resposta = (new AutorizacaoMiddleware(Permissao::CONSULTAR_RELATORIOS))->processar($requisicao, $this->destino());

        $this->assertSame(200, $resposta->status());
    }

    public function testAutorizacaoSemAutenticacaoPreviaEhBarrada(): void
    {
        $this->expectException(NaoAutenticadoException::class);

        (new AutorizacaoMiddleware(Permissao::CONSULTAR_MATERIAIS))->processar(new Request('GET', '/api/materiais'), $this->destino());
    }

    private function destino(): callable
    {
        return static fn (Request $requisicao): Response => Response::sucesso(['ok' => true]);
    }
}
