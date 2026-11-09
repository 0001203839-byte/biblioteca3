<?php

declare(strict_types=1);

namespace App\Exceptions;

final class CredenciaisInvalidasException extends NaoAutenticadoException
{
    public function __construct()
    {
        parent::__construct('E-mail ou senha inválidos.', 'CREDENCIAIS_INVALIDAS');
    }
}
