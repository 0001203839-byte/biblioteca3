<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UsuarioBloqueadoException extends ConflitoException
{
    public function __construct()
    {
        parent::__construct('O usuário está bloqueado e não pode realizar empréstimos.', 'USUARIO_BLOQUEADO');
    }
}
