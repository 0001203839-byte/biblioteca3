<?php

declare(strict_types=1);

namespace App\Domain\Politicas;

use App\Domain\Models\Usuario;
use App\Domain\ValueObjects\Dinheiro;

final class MultaPorDiaDeAtraso implements PoliticaMultaInterface
{
    public function calcular(Usuario $usuario, int $diasAtraso): Dinheiro
    {
        if ($diasAtraso <= 0) {
            return Dinheiro::zero();
        }

        return $usuario->multaPorDia()->multiplicar($diasAtraso);
    }
}
