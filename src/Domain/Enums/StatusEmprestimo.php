<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum StatusEmprestimo: string
{
    case ATIVO = 'ativo';
    case DEVOLVIDO = 'devolvido';
}
