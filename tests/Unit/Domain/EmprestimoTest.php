<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Models\Emprestimo;
use App\Domain\Models\Material;
use App\Domain\Models\Usuario;
use App\Exceptions\DevolucaoDuplicadaException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Exemplos;

final class EmprestimoTest extends TestCase
{
    private DateTimeImmutable $dia;

    protected function setUp(): void
    {
        $this->dia = new DateTimeImmutable('2026-03-02 10:00:00');
    }

    public function testAlunoRecebePrazoBaseDoLivro(): void
    {
        $emprestimo = $this->emprestar(Exemplos::ana(), Exemplos::livro());

        $this->assertSame(14, $emprestimo->prazoEmDias());
        $this->assertSame('2026-03-16', $emprestimo->dataPrevista()->format('Y-m-d'));
    }

    public function testProfessorGanhaSeteDiasExtras(): void
    {
        $emprestimo = $this->emprestar(Exemplos::carlos(), Exemplos::ebook());

        $this->assertSame(17, $emprestimo->prazoEmDias());
        $this->assertSame('2026-03-19', $emprestimo->dataPrevista()->format('Y-m-d'));
    }

    public function testPesquisadorGanhaCincoDiasExtras(): void
    {
        $emprestimo = $this->emprestar(Exemplos::marina(), Exemplos::revista());

        $this->assertSame(12, $emprestimo->prazoEmDias());
        $this->assertSame('2026-03-14', $emprestimo->dataPrevista()->format('Y-m-d'));
    }

    public function testCalculaDiasDeAtrasoSomenteAposAPrevisao(): void
    {
        $emprestimo = $this->emprestar(Exemplos::ana(), Exemplos::livro());

        $this->assertSame(0, $emprestimo->diasDeAtraso(new DateTimeImmutable('2026-03-16 23:59:00')));
        $this->assertSame(1, $emprestimo->diasDeAtraso(new DateTimeImmutable('2026-03-17 08:00:00')));
        $this->assertTrue($emprestimo->estaAtrasado(new DateTimeImmutable('2026-03-20')));
    }

    public function testDepoisDaDevolucaoOAtrasoFicaCongelado(): void
    {
        $emprestimo = $this->emprestar(Exemplos::ana(), Exemplos::livro());
        $emprestimo->registrarDevolucao(new DateTimeImmutable('2026-03-18 09:00:00'));

        $this->assertSame(2, $emprestimo->diasDeAtraso(new DateTimeImmutable('2026-04-30')));
        $this->assertFalse($emprestimo->estaAtrasado(new DateTimeImmutable('2026-04-30')));
    }

    public function testImpedeDevolucaoDuplicada(): void
    {
        $emprestimo = $this->emprestar(Exemplos::ana(), Exemplos::livro());
        $emprestimo->registrarDevolucao(new DateTimeImmutable('2026-03-10'));

        $this->expectException(DevolucaoDuplicadaException::class);

        $emprestimo->registrarDevolucao(new DateTimeImmutable('2026-03-11'));
    }

    private function emprestar(Usuario $usuario, Material $material): Emprestimo
    {
        $usuario->definirId(1);
        $material->definirId(1);

        return Emprestimo::registrar($usuario, $material, $this->dia);
    }
}
