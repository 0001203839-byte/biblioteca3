<?php

declare(strict_types=1);

namespace App\Exceptions;

final class LimiteEmprestimosException extends ConflitoException
{
    public function __construct(int $limite)
    {
        parent::__construct(
            "O usuário já atingiu o limite de {$limite} empréstimos simultâneos.",
            'LIMITE_EMPRESTIMOS_ATINGIDO',
            ['limite' => $limite],
        );
    }
}
