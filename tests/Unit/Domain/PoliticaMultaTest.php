<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Politicas\MultaComValorMaximo;
use App\Domain\Politicas\MultaPorDiaDeAtraso;
use App\Domain\ValueObjects\Dinheiro;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Exemplos;

final class PoliticaMultaTest extends TestCase
{
    private MultaPorDiaDeAtraso $politica;

    protected function setUp(): void
    {
        $this->politica = new MultaPorDiaDeAtraso();
    }

    public function testAlunoPagaDoisReaisPorDia(): void
    {
        $this->assertSame('6.00', $this->politica->calcular(Exemplos::ana(), 3)->emDecimal());
    }

    public function testProfessorPagaUmRealPorDia(): void
    {
        $this->assertSame('3.00', $this->politica->calcular(Exemplos::carlos(), 3)->emDecimal());
    }

    public function testPesquisadorPagaUmRealECinquentaPorDia(): void
    {
        $valor = $this->politica->calcular(Exemplos::marina(), 6);

        $this->assertSame('9.00', $valor->emDecimal());
        $this->assertSame('R$ 9,00', $valor->formatado());
    }

    public function testSemAtrasoNaoHaMulta(): void
    {
        $this->assertTrue($this->politica->calcular(Exemplos::ana(), 0)->ehZero());
    }

    public function testNovaPoliticaPodeLimitarOValorSemAlterarAOriginal(): void
    {
        $comTeto = new MultaComValorMaximo($this->politica, Dinheiro::deDecimal('10.00'));

        $this->assertSame('10.00', $comTeto->calcular(Exemplos::ana(), 30)->emDecimal());
        $this->assertSame('4.00', $comTeto->calcular(Exemplos::ana(), 2)->emDecimal());
    }

    public function testDinheiroConverteValoresDoBanco(): void
    {
        $this->assertSame(450, Dinheiro::deDecimal('4.50')->centavos());
        $this->assertSame(450, Dinheiro::deDecimal('4.5')->centavos());
        $this->assertSame(900, Dinheiro::deDecimal('9')->centavos());
    }
}
