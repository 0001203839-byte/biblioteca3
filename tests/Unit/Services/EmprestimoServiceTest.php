<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Enums\Perfil;
use App\Domain\Enums\TipoMovimentacao;
use App\Domain\Models\ContaAcesso;
use App\Domain\Models\Material;
use App\Domain\Models\Usuario;
use App\Domain\Politicas\MultaPorDiaDeAtraso;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\DevolucaoDuplicadaException;
use App\Exceptions\LimiteEmprestimosException;
use App\Exceptions\MaterialIndisponivelException;
use App\Exceptions\UsuarioBloqueadoException;
use App\Services\EmprestimoService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fakes\EmprestimoRepositoryEmMemoria;
use Tests\Fakes\Exemplos;
use Tests\Fakes\HistoricoRepositoryEmMemoria;
use Tests\Fakes\MaterialRepositoryEmMemoria;
use Tests\Fakes\MultaRepositoryEmMemoria;
use Tests\Fakes\RelogioFixo;
use Tests\Fakes\TransacaoEmMemoria;
use Tests\Fakes\UsuarioRepositoryEmMemoria;

final class EmprestimoServiceTest extends TestCase
{
    private UsuarioRepositoryEmMemoria $usuarios;
    private MaterialRepositoryEmMemoria $materiais;
    private EmprestimoRepositoryEmMemoria $emprestimos;
    private MultaRepositoryEmMemoria $multas;
    private HistoricoRepositoryEmMemoria $historico;
    private TransacaoEmMemoria $transacao;
    private RelogioFixo $relogio;
    private EmprestimoService $servico;
    private ContaAcesso $bibliotecario;

    protected function setUp(): void
    {
        $this->usuarios = new UsuarioRepositoryEmMemoria();
        $this->materiais = new MaterialRepositoryEmMemoria();
        $this->emprestimos = new EmprestimoRepositoryEmMemoria();
        $this->multas = new MultaRepositoryEmMemoria();
        $this->historico = new HistoricoRepositoryEmMemoria();
        $this->transacao = new TransacaoEmMemoria();
        $this->relogio = new RelogioFixo('2026-03-02 10:00:00');

        $this->servico = new EmprestimoService(
            $this->usuarios,
            $this->materiais,
            $this->emprestimos,
            $this->multas,
            $this->historico,
            $this->transacao,
            new MultaPorDiaDeAtraso(),
            $this->relogio,
        );

        $this->bibliotecario = Exemplos::conta(Perfil::BIBLIOTECARIO, null, 2);
    }

    public function testCenarioAAnaPegaUmLivroDisponivel(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro(3));

        $emprestimo = $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);

        $this->assertNotNull($emprestimo->id());
        $this->assertSame(2, $this->materiais->buscarPorId($livro->id())->quantidadeDisponivel());
        $this->assertSame('2026-03-16', $emprestimo->dataPrevista()->format('Y-m-d'));
        $this->assertSame(1, $this->transacao->confirmadas);
        $this->assertSame(TipoMovimentacao::EMPRESTIMO, $this->historico->todas()[0]->tipo());
    }

    public function testCenarioBCarlosPegaUmEbookERespeitaOsAcessosSimultaneos(): void
    {
        $carlos = $this->cadastrarUsuario(Exemplos::carlos());
        $marina = $this->cadastrarUsuario(Exemplos::marina());
        $ebook = $this->cadastrarMaterial(Exemplos::ebook(1));

        $this->servico->emprestar($carlos->id(), $ebook->id(), $this->bibliotecario);

        $this->assertSame(0, $ebook->quantidadeDisponivel());

        $this->expectException(MaterialIndisponivelException::class);
        $this->servico->emprestar($marina->id(), $ebook->id(), $this->bibliotecario);
    }

    public function testCenarioCMarinaDevolveSeisDiasAposOPrazo(): void
    {
        $marina = $this->cadastrarUsuario(Exemplos::marina());
        $livro = $this->cadastrarMaterial(Exemplos::livro(2));

        $emprestimo = $this->servico->emprestar($marina->id(), $livro->id(), $this->bibliotecario);
        $this->assertSame('2026-03-21', $emprestimo->dataPrevista()->format('Y-m-d'));

        $this->relogio->avancarDias(19 + 6);
        $resultado = $this->servico->devolver($emprestimo->id(), $this->bibliotecario);

        $this->assertTrue($resultado->gerouMulta());
        $this->assertSame(6, $resultado->multa->diasAtraso());
        $this->assertSame('9.00', $resultado->multa->valor()->emDecimal());
        $this->assertSame(2, $livro->quantidadeDisponivel());
        $this->assertFalse($resultado->emprestimo->estaAtivo());

        $tipos = array_map(static fn ($movimentacao) => $movimentacao->tipo(), $this->historico->todas());
        $this->assertSame([TipoMovimentacao::EMPRESTIMO, TipoMovimentacao::DEVOLUCAO, TipoMovimentacao::MULTA], $tipos);
    }

    public function testCenarioDRejeitaMaterialSemExemplaresSemDeixarInconsistencia(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro(1, 0));

        try {
            $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
            $this->fail('O empréstimo deveria ter sido rejeitado.');
        } catch (MaterialIndisponivelException) {
            $this->assertSame(0, $this->emprestimos->total());
            $this->assertSame(0, $livro->quantidadeDisponivel());
            $this->assertSame([], $this->historico->todas());
            $this->assertSame(1, $this->transacao->revertidas);
        }
    }

    public function testDevolucaoNoPrazoNaoGeraMulta(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro());

        $emprestimo = $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
        $this->relogio->avancarDias(14);

        $resultado = $this->servico->devolver($emprestimo->id(), $this->bibliotecario);

        $this->assertFalse($resultado->gerouMulta());
        $this->assertSame([], $this->multas->todas());
    }

    public function testMultaDoAlunoEDoProfessorUsamValoresDiferentes(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $carlos = $this->cadastrarUsuario(Exemplos::carlos());
        $livro = $this->cadastrarMaterial(Exemplos::livro(5));

        $daAna = $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
        $doCarlos = $this->servico->emprestar($carlos->id(), $livro->id(), $this->bibliotecario);

        $this->relogio->avancarDias(14 + 7 + 3);

        $this->assertSame('20.00', $this->servico->devolver($daAna->id(), $this->bibliotecario)->multa->valor()->emDecimal());
        $this->assertSame('3.00', $this->servico->devolver($doCarlos->id(), $this->bibliotecario)->multa->valor()->emDecimal());
    }

    public function testAlunoNaoUltrapassaOLimiteDeTresEmprestimos(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro(10));

        for ($i = 0; $i < 3; $i++) {
            $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
        }

        $this->expectException(LimiteEmprestimosException::class);
        $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
    }

    public function testProfessorNaoUltrapassaOLimiteDeCincoEmprestimos(): void
    {
        $carlos = $this->cadastrarUsuario(Exemplos::carlos());
        $livro = $this->cadastrarMaterial(Exemplos::livro(10));

        for ($i = 0; $i < 5; $i++) {
            $this->servico->emprestar($carlos->id(), $livro->id(), $this->bibliotecario);
        }

        $this->expectException(LimiteEmprestimosException::class);
        $this->servico->emprestar($carlos->id(), $livro->id(), $this->bibliotecario);
    }

    public function testPesquisadorNaoUltrapassaOLimiteDeSeteEmprestimos(): void
    {
        $marina = $this->cadastrarUsuario(Exemplos::marina());
        $livro = $this->cadastrarMaterial(Exemplos::livro(10));

        for ($i = 0; $i < 7; $i++) {
            $this->servico->emprestar($marina->id(), $livro->id(), $this->bibliotecario);
        }

        $this->assertSame(3, $livro->quantidadeDisponivel());

        $this->expectException(LimiteEmprestimosException::class);
        $this->servico->emprestar($marina->id(), $livro->id(), $this->bibliotecario);
    }

    public function testUsuarioBloqueadoNaoConsegueEmprestar(): void
    {
        $ana = Exemplos::ana();
        $ana->bloquear();
        $this->cadastrarUsuario($ana);
        $livro = $this->cadastrarMaterial(Exemplos::livro());

        $this->expectException(UsuarioBloqueadoException::class);

        $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
    }

    public function testImpedeDevolucaoDuplicada(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro(1));

        $emprestimo = $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
        $this->servico->devolver($emprestimo->id(), $this->bibliotecario);

        try {
            $this->servico->devolver($emprestimo->id(), $this->bibliotecario);
            $this->fail('A segunda devolução deveria ser recusada.');
        } catch (DevolucaoDuplicadaException) {
            $this->assertSame(1, $livro->quantidadeDisponivel());
        }
    }

    public function testLeitorNaoPodeRegistrarEmprestimo(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro());

        $this->expectException(AcessoNegadoException::class);

        $this->servico->emprestar($ana->id(), $livro->id(), Exemplos::conta(Perfil::LEITOR, $ana->id(), 3));
    }

    public function testFalhaAoGravarHistoricoReverteAOperacaoInteira(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $livro = $this->cadastrarMaterial(Exemplos::livro());
        $this->historico->falharNaProximaGravacao();

        try {
            $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
            $this->fail('A falha no histórico deveria interromper o empréstimo.');
        } catch (RuntimeException $erro) {
            $this->assertSame('Falha simulada ao gravar o histórico.', $erro->getMessage());
            $this->assertSame(1, $this->transacao->revertidas);
            $this->assertSame(0, $this->transacao->confirmadas);
        }
    }

    public function testLeitorSoEnxergaOsPropriosEmprestimos(): void
    {
        $ana = $this->cadastrarUsuario(Exemplos::ana());
        $carlos = $this->cadastrarUsuario(Exemplos::carlos());
        $livro = $this->cadastrarMaterial(Exemplos::livro(5));

        $daAna = $this->servico->emprestar($ana->id(), $livro->id(), $this->bibliotecario);
        $doCarlos = $this->servico->emprestar($carlos->id(), $livro->id(), $this->bibliotecario);

        $leitora = Exemplos::conta(Perfil::LEITOR, $ana->id(), 3);
        $visiveis = $this->servico->listar([], $leitora);

        $this->assertCount(1, $visiveis);
        $this->assertSame($daAna->id(), $visiveis[0]->id());
        $this->assertCount(2, $this->servico->listar([], $this->bibliotecario));

        $this->expectException(AcessoNegadoException::class);
        $this->servico->buscar($doCarlos->id(), $leitora);
    }

    private function cadastrarUsuario(Usuario $usuario): Usuario
    {
        $this->usuarios->salvar($usuario);

        return $usuario;
    }

    private function cadastrarMaterial(Material $material): Material
    {
        $this->materiais->salvar($material);

        return $material;
    }
}
