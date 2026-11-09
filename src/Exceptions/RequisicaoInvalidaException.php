<?php

declare(strict_types=1);

namespace App\Exceptions;

class RequisicaoInvalidaException extends AppException
{
    public function __construct(string $mensagem = 'Requisição inválida.', string $codigo = 'REQUISICAO_INVALIDA')
    {
        parent::__construct($mensagem, $codigo);
    }

    public function statusHttp(): int
    {
        return 400;
    }
}
