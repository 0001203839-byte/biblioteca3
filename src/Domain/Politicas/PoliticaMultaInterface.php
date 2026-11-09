<?php

declare(strict_types=1);

namespace App\Domain\Politicas;

use App\Domain\Models\Usuario;
use App\Domain\ValueObjects\Dinheiro;

interface PoliticaMultaInterface
{
    public function calcular(Usuario $usuario, int $diasAtraso): Dinheiro;
}
