<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\ValueObjects\Dinheiro;

final class Pesquisador extends Usuario
{
    public function tipo(): string
    {
        return 'pesquisador';
    }

    public function descricaoTipo(): string
    {
        return 'Pesquisador';
    }

    public function limiteEmprestimos(): int
    {
        return 7;
    }

    public function diasExtrasPrazo(): int
    {
        return 5;
    }

    public function multaPorDia(): Dinheiro
    {
        return Dinheiro::deCentavos(150);
    }
}
