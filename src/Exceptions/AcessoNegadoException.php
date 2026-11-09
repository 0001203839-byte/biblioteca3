<?php

declare(strict_types=1);

namespace App\Exceptions;

final class AcessoNegadoException extends AppException
{
    public function __construct(
        string $mensagem = 'Seu perfil não tem permissão para realizar esta operação.',
        string $codigo = 'ACESSO_NEGADO',
    ) {
        parent::__construct($mensagem, $codigo);
    }

    public function statusHttp(): int
    {
        return 403;
    }
}
