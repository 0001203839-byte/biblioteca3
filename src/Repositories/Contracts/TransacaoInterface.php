<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface TransacaoInterface
{
    public function executar(callable $operacao): mixed;
}
