<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Aplicacao;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\RelogioFixo;

final class ApiTest extends TestCase
{
    private Aplicacao $aplicacao;
    private RelogioFixo $relogio;

    protected function setUp(): void
    {
        $pdo = BancoEmMemoria::criar();
        BancoEmMemoria::popular($pdo);

        $this->relogio = new RelogioFixo('2026-03-02 10:00:00');
        $this->aplicacao = new Aplicacao($pdo, ['debug' => true], $this->relogio);
    }

    public function testLoginComCredenciaisInvalidasRetorna401(): void
    {
        $resposta = $this->enviar('POST', '/api/auth/login', ['email' => 'admin@biblioteca.local', 'senha' => 'errada123']);

        $this->assertSame(401, $resposta->status());
        $this->assertFalse($resposta->corpo()['sucesso']);
        $this->assertSame('CREDENCIAIS_INVALIDAS', $resposta->corpo()['erro']['codigo']);
    }

    public function testRotaProtegidaSemTokenRetorna401(): void
    {
        $resposta = $this->enviar('GET', '/api/materiais');

        $this->assertSame(401, $resposta->status());
        $this->assertSame('TOKEN_AUSENTE', $resposta->corpo()['erro']['codigo']);
    }

    public function testListagemDeMateriaisComFiltroEPaginacao(): void
    {
        $token = $this->login('ana.souza@email.com', 'Leitor@123');

        $resposta = $this->enviar('GET', '/api/materiais', null, $token, ['tipo' => 'livro', 'ordenar' => 'titulo', 'por_pagina' => 1]);
        $corpo = $resposta->corpo();

        $this->assertSame(200, $resposta->status());
        $this->assertCount(1, $corpo['dados']);
        $this->assertSame('Banco de Dados Relacional', $corpo['dados'][0]['titulo']);
        $this->assertSame(['pagina' => 1, 'por_pagina' => 1, 'total_itens' => 2, 'total_paginas' => 2], $corpo['paginacao']);

        $disponiveis = $this->enviar('GET', '/api/materiais', null, $token, ['disponivel' => 'false'])->corpo();
        $this->assertSame([4], array_column($disponiveis['dados'], 'id'));
    }

    public function testCenarioELeitorNaoCadastraMaterial(): void
    {
        $token = $this->login('ana.souza@email.com', 'Leitor@123');

        $resposta = $this->enviar('POST', '/api/materiais', [
            'tipo' => 'livro', 'titulo' => 'Livro do leitor', 'autor' => 'Ana', 'ano_publicacao' => 2024,
            'quantidade_total' => 1, 'isbn' => '9788500000001', 'editora' => 'Teste', 'paginas' => 10,
        ], $token);

        $this->assertSame(403, $resposta->status());
        $this->assertSame('ACESSO_NEGADO', $resposta->corpo()['erro']['codigo']);
    }

    public function testFluxoCompletoDeEmprestimoEDevolucao(): void
    {
        $token = $this->login('paula.ribeiro@biblioteca.local', 'Biblio@123');

        $emprestimo = $this->enviar('POST', '/api/emprestimos', ['usuario_id' => 3, 'material_id' => 1], $token);
        $this->assertSame(201, $emprestimo->status());
        $id = $emprestimo->corpo()['dados']['id'];
        $this->assertSame('2026-03-21', $emprestimo->corpo()['dados']['data_prevista']);

        $material = $this->enviar('GET', '/api/materiais/1', null, $token);
        $this->assertSame(2, $material->corpo()['dados']['quantidade_disponivel']);

        $this->relogio->avancarDias(25);
        $this->assertSame(401, $this->enviar('GET', '/api/materiais/1', null, $token)->status());

        $token = $this->login('paula.ribeiro@biblioteca.local', 'Biblio@123');
        $devolucao = $this->enviar('POST', "/api/emprestimos/{$id}/devolucao", null, $token);

        $this->assertSame(200, $devolucao->status());
        $this->assertSame('9.00', $devolucao->corpo()['dados']['multa']['valor']);

        $repetida = $this->enviar('POST', "/api/emprestimos/{$id}/devolucao", null, $token);
        $this->assertSame(409, $repetida->status());
        $this->assertSame('EMPRESTIMO_JA_DEVOLVIDO', $repetida->corpo()['erro']['codigo']);
    }

    public function testCenarioDMaterialSemExemplaresRetorna409(): void
    {
        $token = $this->login('paula.ribeiro@biblioteca.local', 'Biblio@123');

        $resposta = $this->enviar('POST', '/api/emprestimos', ['usuario_id' => 1, 'material_id' => 4], $token);

        $this->assertSame(409, $resposta->status());
        $this->assertSame([
            'sucesso' => false,
            'erro' => ['codigo' => 'MATERIAL_INDISPONIVEL', 'mensagem' => 'O material não está disponível.'],
        ], $resposta->corpo());
    }

    public function testCadastroComDadosInvalidosRetorna422(): void
    {
        $token = $this->login('admin@biblioteca.local', 'Admin@123');

        $resposta = $this->enviar('POST', '/api/materiais', ['tipo' => 'livro', 'titulo' => 'X'], $token);

        $this->assertSame(422, $resposta->status());
        $this->assertArrayHasKey('titulo', $resposta->corpo()['erro']['detalhes']);
    }

    public function testJsonMalformadoRetorna400(): void
    {
        $token = $this->login('admin@biblioteca.local', 'Admin@123');

        $resposta = $this->aplicacao->processar(
            new Request('POST', '/api/materiais', [], '{"titulo": ', ['Authorization' => "Bearer {$token}"]),
        );

        $this->assertSame(400, $resposta->status());
    }

    public function testCenarioFAdministradorConsultaResumo(): void
    {
        $token = $this->login('admin@biblioteca.local', 'Admin@123');

        $resposta = $this->enviar('GET', '/api/relatorios/resumo', null, $token);
        $dados = $resposta->corpo()['dados'];

        $this->assertSame(200, $resposta->status());
        $this->assertSame(4, $dados['total_materiais']);
        $this->assertSame(3, $dados['total_usuarios']);
        $this->assertSame(1, $dados['emprestimos_ativos']);
        $this->assertSame(1, $dados['emprestimos_atrasados']);
        $this->assertSame('0.00', $dados['multas_pendentes']['valor_total']);
    }

    public function testLogoutRevogaOToken(): void
    {
        $token = $this->login('admin@biblioteca.local', 'Admin@123');

        $this->assertSame(200, $this->enviar('POST', '/api/auth/logout', null, $token)->status());
        $this->assertSame(401, $this->enviar('GET', '/api/auth/me', null, $token)->status());
    }

    public function testRotaInexistenteEMetodoErrado(): void
    {
        $this->assertSame(404, $this->enviar('GET', '/api/nao-existe')->status());
        $this->assertSame(405, $this->enviar('DELETE', '/api/materiais/1')->status());
    }

    private function login(string $email, string $senha): string
    {
        return $this->enviar('POST', '/api/auth/login', ['email' => $email, 'senha' => $senha])->corpo()['dados']['token'];
    }

    private function enviar(string $metodo, string $caminho, ?array $corpo = null, ?string $token = null, array $query = []): Response
    {
        $cabecalhos = ['Content-Type' => 'application/json'];

        if ($token !== null) {
            $cabecalhos['Authorization'] = "Bearer {$token}";
        }

        return $this->aplicacao->processar(
            new Request($metodo, $caminho, $query, $corpo === null ? '' : json_encode($corpo), $cabecalhos),
        );
    }
}
