<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum StatusMulta: string
{
    case PENDENTE = 'pendente';
    case PAGA = 'paga';
}
