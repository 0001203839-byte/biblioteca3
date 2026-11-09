<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\ValueObjects\Dinheiro;

final class Professor extends Usuario
{
    public function tipo(): string
    {
        return 'professor';
    }

    public function descricaoTipo(): string
    {
        return 'Professor';
    }

    public function limiteEmprestimos(): int
    {
        return 5;
    }

    public function diasExtrasPrazo(): int
    {
        return 7;
    }

    public function multaPorDia(): Dinheiro
    {
        return Dinheiro::deCentavos(100);
    }
}
