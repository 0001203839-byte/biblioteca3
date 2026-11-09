<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

abstract class AppException extends RuntimeException
{
    public function __construct(
        string $mensagem,
        private readonly string $codigoErro,
        private readonly array $detalhes = [],
        ?Throwable $anterior = null,
    ) {
        parent::__construct($mensagem, 0, $anterior);
    }

    abstract public function statusHttp(): int;

    public function codigoErro(): string
    {
        return $this->codigoErro;
    }

    public function detalhes(): array
    {
        return $this->detalhes;
    }
}
