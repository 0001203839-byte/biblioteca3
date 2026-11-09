<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\ValueObjects\Dinheiro;

final class Aluno extends Usuario
{
    public function tipo(): string
    {
        return 'aluno';
    }

    public function descricaoTipo(): string
    {
        return 'Aluno';
    }

    public function limiteEmprestimos(): int
    {
        return 3;
    }

    public function diasExtrasPrazo(): int
    {
        return 0;
    }

    public function multaPorDia(): Dinheiro
    {
        return Dinheiro::deCentavos(200);
    }
}
