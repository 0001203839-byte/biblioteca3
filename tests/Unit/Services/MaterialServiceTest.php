<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Enums\Perfil;
use App\Domain\Enums\TipoMovimentacao;
use App\Domain\Filtros\FiltroMateriais;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Services\MaterialService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Exemplos;
use Tests\Fakes\HistoricoRepositoryEmMemoria;
use Tests\Fakes\MaterialRepositoryEmMemoria;
use Tests\Fakes\RelogioFixo;
use Tests\Fakes\TransacaoEmMemoria;

final class MaterialServiceTest extends TestCase
{
    private const LIVRO = [
        'tipo' => 'livro',
        'titulo' => 'Padrões de Projeto em PHP',
        'autor' => 'Gustavo Nunes',
        'ano_publicacao' => 2024,
        'quantidade_total' => 2,
        'isbn' => '978-85-0000-000-1',
        'editora' => 'Novatec',
        'paginas' => 320,
    ];

    private MaterialRepositoryEmMemoria $materiais;
    private HistoricoRepositoryEmMemoria $historico;
    private MaterialService $servico;

    protected function setUp(): void
    {
        $this->materiais = new MaterialRepositoryEmMemoria();
        $this->historico = new HistoricoRepositoryEmMemoria();
        $this->servico = new MaterialService($this->materiais, $this->historico, new TransacaoEmMemoria(), new RelogioFixo());
    }

    public function testBibliotecarioCadastraMaterialERegistraHistorico(): void
    {
        $material = $this->servico->cadastrar(self::LIVRO, Exemplos::conta(Perfil::BIBLIOTECARIO));

        $this->assertSame(1, $material->id());
        $this->assertSame(TipoMovimentacao::CADASTRO_MATERIAL, $this->historico->todas()[0]->tipo());
    }

    public function testCenarioELeitorNaoPodeCadastrarMaterial(): void
    {
        try {
            $this->servico->cadastrar(self::LIVRO, Exemplos::conta(Perfil::LEITOR, 1));
            $this->fail('Leitor não deveria cadastrar materiais.');
        } catch (AcessoNegadoException $erro) {
            $this->assertSame(403, $erro->statusHttp());
            $this->assertSame(0, $this->materiais->listar(new FiltroMateriais())->total);
        }
    }

    public function testBibliotecarioNaoPodeAlterarMaterialMasAdministradorPode(): void
    {
        $this->materiais->salvar(Exemplos::livro());

        $negado = false;

        try {
            $this->servico->atualizar(1, ['titulo' => 'Outro título'], Exemplos::conta(Perfil::BIBLIOTECARIO));
        } catch (AcessoNegadoException) {
            $negado = true;
        }

        $this->assertTrue($negado, 'Somente o administrador altera materiais.');

        $atualizado = $this->servico->atualizar(1, ['titulo' => 'Outro título'], Exemplos::conta(Perfil::ADMINISTRADOR));
        $this->assertSame('Outro título', $atualizado->titulo());
    }

    public function testCadastroComDadosInvalidosNaoChegaAoRepositorio(): void
    {
        $this->expectException(ValidacaoException::class);

        $this->servico->cadastrar(['tipo' => 'livro', 'titulo' => ''], Exemplos::conta(Perfil::ADMINISTRADOR));
    }

    public function testBuscarMaterialInexistenteLancaNaoEncontrado(): void
    {
        $this->expectException(RecursoNaoEncontradoException::class);

        $this->servico->buscar(999);
    }

    public function testFiltroComParametroInvalidoEhRecusado(): void
    {
        $this->expectException(ValidacaoException::class);

        $this->servico->listar(['disponivel' => 'talvez']);
    }
}
