<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Enums\Perfil;
use App\Domain\Politicas\MultaPorDiaDeAtraso;
use App\Repositories\Pdo\PdoEmprestimoRepository;
use App\Repositories\Pdo\PdoMaterialRepository;
use App\Repositories\Pdo\PdoMultaRepository;
use App\Repositories\Pdo\PdoTransacao;
use App\Repositories\Pdo\PdoUsuarioRepository;
use App\Services\EmprestimoService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fakes\Exemplos;
use Tests\Fakes\HistoricoRepositoryEmMemoria;
use Tests\Fakes\RelogioFixo;

final class TransacaoTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = BancoEmMemoria::criar();
        BancoEmMemoria::popular($this->pdo);
    }

    public function testTransacaoDesfazTudoQuandoAlgoFalha(): void
    {
        $transacao = new PdoTransacao($this->pdo);

        try {
            $transacao->executar(function (): void {
                $this->pdo->exec("UPDATE materiais SET quantidade_disponivel = 0 WHERE id = 1");
                $this->pdo->exec("INSERT INTO emprestimos (usuario_id, material_id, data_emprestimo, data_prevista) VALUES (1, 1, '2026-03-02 10:00:00', '2026-03-16')");

                throw new RuntimeException('Falha no meio da operação.');
            });
            $this->fail('A exceção deveria ter sido propagada.');
        } catch (RuntimeException) {
            $this->assertFalse($this->pdo->inTransaction());
            $this->assertSame(3, (int) $this->pdo->query('SELECT quantidade_disponivel FROM materiais WHERE id = 1')->fetchColumn());
            $this->assertSame(1, BancoEmMemoria::contar($this->pdo, 'emprestimos'));
        }
    }

    public function testEmprestimoComFalhaNoHistoricoNaoDeixaRastroNoBanco(): void
    {
        $historico = new HistoricoRepositoryEmMemoria();
        $historico->falharNaProximaGravacao();

        $servico = new EmprestimoService(
            new PdoUsuarioRepository($this->pdo),
            new PdoMaterialRepository($this->pdo),
            new PdoEmprestimoRepository($this->pdo),
            new PdoMultaRepository($this->pdo),
            $historico,
            new PdoTransacao($this->pdo),
            new MultaPorDiaDeAtraso(),
            new RelogioFixo(),
        );

        try {
            $servico->emprestar(1, 1, Exemplos::conta(Perfil::BIBLIOTECARIO, null, 2));
            $this->fail('O empréstimo deveria falhar.');
        } catch (RuntimeException) {
            $this->assertSame(1, BancoEmMemoria::contar($this->pdo, 'emprestimos'));
            $this->assertSame(3, (int) $this->pdo->query('SELECT quantidade_disponivel FROM materiais WHERE id = 1')->fetchColumn());
        }
    }

    public function testEmprestimoConfirmadoPersisteTodasAsEtapas(): void
    {
        $historico = new HistoricoRepositoryEmMemoria();

        $servico = new EmprestimoService(
            new PdoUsuarioRepository($this->pdo),
            new PdoMaterialRepository($this->pdo),
            new PdoEmprestimoRepository($this->pdo),
            new PdoMultaRepository($this->pdo),
            $historico,
            new PdoTransacao($this->pdo),
            new MultaPorDiaDeAtraso(),
            new RelogioFixo(),
        );

        $emprestimo = $servico->emprestar(1, 1, Exemplos::conta(Perfil::BIBLIOTECARIO, null, 2));

        $this->assertSame(2, BancoEmMemoria::contar($this->pdo, 'emprestimos'));
        $this->assertSame(2, (int) $this->pdo->query('SELECT quantidade_disponivel FROM materiais WHERE id = 1')->fetchColumn());
        $this->assertSame($emprestimo->id(), $historico->todas()[0]->emprestimoId());
    }
}
