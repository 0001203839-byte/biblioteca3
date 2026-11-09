<?php

declare(strict_types=1);

namespace App\Exceptions;

final class DevolucaoDuplicadaException extends ConflitoException
{
    public function __construct()
    {
        parent::__construct('Este empréstimo já foi devolvido.', 'EMPRESTIMO_JA_DEVOLVIDO');
    }
}
