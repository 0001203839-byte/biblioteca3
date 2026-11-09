<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Factories\UsuarioFactory;
use App\Domain\Models\Pesquisador;
use App\Exceptions\LimiteEmprestimosException;
use App\Exceptions\UsuarioBloqueadoException;
use App\Exceptions\ValidacaoException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Exemplos;

final class UsuarioTest extends TestCase
{
    public function testAlunoPodeTerAteTresEmprestimos(): void
    {
        $aluno = Exemplos::ana();

        $this->assertSame(3, $aluno->limiteEmprestimos());
        $this->assertTrue($aluno->podeEmprestar(2));
        $this->assertFalse($aluno->podeEmprestar(3));
    }

    public function testProfessorPodeTerAteCincoEmprestimos(): void
    {
        $professor = Exemplos::carlos();

        $this->assertSame(5, $professor->limiteEmprestimos());
        $this->assertTrue($professor->podeEmprestar(4));
        $this->assertFalse($professor->podeEmprestar(5));
    }

    public function testPesquisadorPodeTerAteSeteEmprestimos(): void
    {
        $pesquisador = Exemplos::marina();

        $this->assertSame(7, $pesquisador->limiteEmprestimos());
        $this->assertTrue($pesquisador->podeEmprestar(6));
        $this->assertFalse($pesquisador->podeEmprestar(7));
    }

    public function testLancaExcecaoQuandoOLimiteEhAtingido(): void
    {
        $this->expectException(LimiteEmprestimosException::class);

        Exemplos::ana()->garantirLimiteDeEmprestimos(3);
    }

    public function testUsuarioBloqueadoNaoPodeEmprestar(): void
    {
        $usuario = Exemplos::carlos();
        $usuario->bloquear();

        $this->assertTrue($usuario->estaBloqueado());
        $this->assertFalse($usuario->podeEmprestar(0));

        $this->expectException(UsuarioBloqueadoException::class);
        $usuario->garantirQueNaoEstaBloqueado();
    }

    public function testDesbloqueioLiberaNovosEmprestimos(): void
    {
        $usuario = Exemplos::ana();
        $usuario->bloquear();
        $usuario->desbloquear();

        $this->assertTrue($usuario->podeEmprestar(0));
    }

    public function testFabricaMontaPesquisadorPeloTipo(): void
    {
        $usuario = (new UsuarioFactory())->criar([
            'tipo' => 'pesquisador',
            'nome' => 'Marina Costa',
            'email' => 'Marina.Costa@Email.com',
            'documento' => 'PESQ-0452',
        ]);

        $this->assertInstanceOf(Pesquisador::class, $usuario);
        $this->assertSame('marina.costa@email.com', $usuario->email());
    }

    public function testRejeitaUsuarioComDadosInvalidos(): void
    {
        try {
            (new UsuarioFactory())->criar(['tipo' => 'aluno', 'nome' => 'A', 'email' => 'sem-arroba', 'documento' => '']);
            $this->fail('Era esperada uma ValidacaoException.');
        } catch (ValidacaoException $erro) {
            $this->assertSame(['nome', 'email', 'documento'], array_keys($erro->erros()));
        }
    }

    public function testRejeitaTipoDeUsuarioInexistente(): void
    {
        $this->expectException(ValidacaoException::class);

        (new UsuarioFactory())->criar(['tipo' => 'visitante', 'nome' => 'Fulano', 'email' => 'f@x.com', 'documento' => 'V-1']);
    }
}
