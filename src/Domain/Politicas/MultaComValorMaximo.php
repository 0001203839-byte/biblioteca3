<?php

declare(strict_types=1);

namespace App\Domain\Politicas;

use App\Domain\Models\Usuario;
use App\Domain\ValueObjects\Dinheiro;

final class MultaComValorMaximo implements PoliticaMultaInterface
{
    public function __construct(
        private readonly PoliticaMultaInterface $politicaBase,
        private readonly Dinheiro $valorMaximo,
    ) {
    }

    public function calcular(Usuario $usuario, int $diasAtraso): Dinheiro
    {
        $valor = $this->politicaBase->calcular($usuario, $diasAtraso);

        return $valor->maiorQue($this->valorMaximo) ? $this->valorMaximo : $valor;
    }
}
