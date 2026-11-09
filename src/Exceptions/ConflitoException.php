<?php

declare(strict_types=1);

namespace App\Exceptions;

class ConflitoException extends AppException
{
    public function __construct(string $mensagem, string $codigo = 'CONFLITO', array $detalhes = [])
    {
        parent::__construct($mensagem, $codigo, $detalhes);
    }

    public function statusHttp(): int
    {
        return 409;
    }
}
