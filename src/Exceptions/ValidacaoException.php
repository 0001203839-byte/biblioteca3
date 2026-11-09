<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ValidacaoException extends AppException
{
    public function __construct(private readonly array $erros, string $mensagem = 'Os dados enviados são inválidos.')
    {
        parent::__construct($mensagem, 'DADOS_INVALIDOS', $erros);
    }

    public function erros(): array
    {
        return $this->erros;
    }

    public function statusHttp(): int
    {
        return 422;
    }
}
