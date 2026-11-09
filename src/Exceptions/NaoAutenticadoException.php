<?php

declare(strict_types=1);

namespace App\Exceptions;

class NaoAutenticadoException extends AppException
{
    public function __construct(string $mensagem = 'É necessário estar autenticado.', string $codigo = 'NAO_AUTENTICADO')
    {
        parent::__construct($mensagem, $codigo);
    }

    public function statusHttp(): int
    {
        return 401;
    }
}
