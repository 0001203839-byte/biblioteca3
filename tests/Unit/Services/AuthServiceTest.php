<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Enums\Perfil;
use App\Domain\Models\ContaAcesso;
use App\Exceptions\CredenciaisInvalidasException;
use App\Exceptions\NaoAutenticadoException;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\ContaAcessoRepositoryEmMemoria;
use Tests\Fakes\RelogioFixo;
use Tests\Fakes\TokenAcessoRepositoryEmMemoria;

final class AuthServiceTest extends TestCase
{
    private ContaAcessoRepositoryEmMemoria $contas;
    private RelogioFixo $relogio;
    private AuthService $servico;

    protected function setUp(): void
    {
        $this->contas = new ContaAcessoRepositoryEmMemoria();
        $this->relogio = new RelogioFixo();
        $this->servico = new AuthService($this->contas, new TokenAcessoRepositoryEmMemoria(), $this->relogio, 60);

        $this->contas->salvar(ContaAcesso::criar('Paula Ribeiro', 'paula@biblioteca.local', 'Biblio@123', Perfil::BIBLIOTECARIO));
    }

    public function testSenhaEhGuardadaComHash(): void
    {
        $conta = $this->contas->buscarPorEmail('paula@biblioteca.local');

        $this->assertNotSame('Biblio@123', $conta->senhaHash());
        $this->assertNotNull(password_get_info($conta->senhaHash())['algo']);
        $this->assertTrue(password_verify('Biblio@123', $conta->senhaHash()));
    }

    public function testLoginValidoGeraTokenQueAutentica(): void
    {
        $sessao = $this->servico->login('PAULA@biblioteca.local', 'Biblio@123');

        $this->assertSame('Bearer', $sessao['tipo']);
        $this->assertSame(64, strlen($sessao['token']));
        $this->assertSame('2026-03-02 11:00:00', $sessao['expira_em']);
        $this->assertSame(Perfil::BIBLIOTECARIO, $this->servico->autenticar($sessao['token'])->perfil());
    }

    public function testSenhaErradaRetornaCredenciaisInvalidas(): void
    {
        $this->expectException(CredenciaisInvalidasException::class);

        $this->servico->login('paula@biblioteca.local', 'senhaerrada1');
    }

    public function testEmailInexistenteRetornaOMesmoErro(): void
    {
        $this->expectException(CredenciaisInvalidasException::class);

        $this->servico->login('ninguem@biblioteca.local', 'Biblio@123');
    }

    public function testTokenExpiradoNaoAutentica(): void
    {
        $sessao = $this->servico->login('paula@biblioteca.local', 'Biblio@123');
        $this->relogio->avancarMinutos(61);

        $this->expectException(NaoAutenticadoException::class);

        $this->servico->autenticar($sessao['token']);
    }

    public function testTokenRevogadoNoLogoutNaoAutentica(): void
    {
        $sessao = $this->servico->login('paula@biblioteca.local', 'Biblio@123');
        $this->servico->logout($sessao['token']);

        $this->expectException(NaoAutenticadoException::class);

        $this->servico->autenticar($sessao['token']);
    }
}
