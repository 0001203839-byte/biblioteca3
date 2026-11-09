<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use DateTimeImmutable;

interface RelatorioRepositoryInterface
{
    public function totaisDeMateriais(): array;

    public function totalDeUsuarios(): int;

    public function totaisDeEmprestimos(DateTimeImmutable $referencia): array;

    public function multasPendentes(): array;

    public function emprestimosPorTipoDeMaterial(): array;
}
