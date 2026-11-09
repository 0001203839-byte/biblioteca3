<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Factories\MaterialFactory;
use App\Domain\Models\EBook;
use App\Domain\Models\Livro;
use App\Domain\Models\Revista;
use App\Exceptions\MaterialIndisponivelException;
use App\Exceptions\ValidacaoException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Exemplos;

final class MaterialTest extends TestCase
{
    public function testCriaLivroValidoComTodosOsExemplaresDisponiveis(): void
    {
        $livro = Exemplos::livro(3);

        $this->assertInstanceOf(Livro::class, $livro);
        $this->assertSame('livro', $livro->tipo());
        $this->assertSame(3, $livro->quantidadeDisponivel());
        $this->assertTrue($livro->estaDisponivel());
        $this->assertSame(14, $livro->prazoBaseEmDias());
    }

    public function testFabricaCriaCadaTipoDeMaterialAPartirDoJson(): void
    {
        $fabrica = new MaterialFactory();

        $revista = $fabrica->criar([
            'tipo' => 'revista', 'titulo' => 'Tecnologia Hoje', 'autor' => 'Editora Tech Brasil',
            'ano_publicacao' => 2025, 'quantidade_total' => 2, 'edicao' => 148,
        ]);

        $ebook = $fabrica->criar([
            'tipo' => 'ebook', 'titulo' => 'APIs REST com PHP', 'autor' => 'Juliana Prado',
            'ano_publicacao' => '2024', 'quantidade_total' => '5', 'formato' => 'PDF',
        ]);

        $this->assertInstanceOf(Revista::class, $revista);
        $this->assertInstanceOf(EBook::class, $ebook);
        $this->assertSame(5, $ebook->quantidadeTotal());
        $this->assertSame('pdf', $ebook->detalhes()['formato']);
    }

    public function testRejeitaLivroComDadosInvalidos(): void
    {
        try {
            new Livro('', 'Autor', 1200, 0, '123', 'Novatec', 0);
            $this->fail('Era esperada uma ValidacaoException.');
        } catch (ValidacaoException $erro) {
            $campos = array_keys($erro->erros());

            $this->assertContains('titulo', $campos);
            $this->assertContains('ano_publicacao', $campos);
            $this->assertContains('quantidade_total', $campos);
            $this->assertContains('isbn', $campos);
            $this->assertContains('paginas', $campos);
        }
    }

    public function testRejeitaTipoDeMaterialDesconhecido(): void
    {
        $this->expectException(ValidacaoException::class);

        (new MaterialFactory())->criar(['tipo' => 'dvd', 'titulo' => 'Filme']);
    }

    public function testRejeitaEbookComFormatoInvalido(): void
    {
        $this->expectException(ValidacaoException::class);

        new EBook('APIs REST com PHP', 'Juliana Prado', 2024, 5, 'docx');
    }

    public function testNaoPermiteRetirarExemplarDeMaterialSemDisponibilidade(): void
    {
        $livro = Exemplos::livro(1);
        $livro->retirarExemplar();

        $this->expectException(MaterialIndisponivelException::class);

        $livro->retirarExemplar();
    }

    public function testEbookRespeitaOLimiteDeAcessosSimultaneos(): void
    {
        $ebook = Exemplos::ebook(2);
        $ebook->retirarExemplar();
        $ebook->retirarExemplar();

        $this->assertFalse($ebook->estaDisponivel());
        $this->assertSame(2, $ebook->toArray()['acessos_em_uso']);

        $this->expectException(MaterialIndisponivelException::class);
        $ebook->retirarExemplar();
    }

    public function testAtualizacaoNaoDeixaTotalMenorQueOsExemplaresEmprestados(): void
    {
        $livro = new Livro('PHP Orientado a Objetos', 'Ricardo Almeida', 2023, 3, '978-85-7522-610-4', 'Novatec', 384, 1, 10);

        $this->expectException(ValidacaoException::class);

        (new MaterialFactory())->atualizar($livro, ['quantidade_total' => 1]);
    }

    public function testAtualizacaoRecalculaADisponibilidade(): void
    {
        $livro = new Livro('PHP Orientado a Objetos', 'Ricardo Almeida', 2023, 3, '978-85-7522-610-4', 'Novatec', 384, 1, 10);

        $atualizado = (new MaterialFactory())->atualizar($livro, ['quantidade_total' => 5, 'titulo' => 'PHP OO - 2ª edição']);

        $this->assertSame(10, $atualizado->id());
        $this->assertSame('PHP OO - 2ª edição', $atualizado->titulo());
        $this->assertSame(5, $atualizado->quantidadeTotal());
        $this->assertSame(3, $atualizado->quantidadeDisponivel());
    }

    public function testNaoPermiteTrocarOTipoDoMaterial(): void
    {
        $this->expectException(ValidacaoException::class);

        (new MaterialFactory())->atualizar(Exemplos::livro(), ['tipo' => 'revista']);
    }
}
