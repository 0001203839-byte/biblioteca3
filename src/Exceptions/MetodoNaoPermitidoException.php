<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MetodoNaoPermitidoException extends AppException
{
    public function __construct(array $metodosPermitidos)
    {
        parent::__construct(
            'Método HTTP não permitido para esta rota.',
            'METODO_NAO_PERMITIDO',
            ['permitidos' => array_values(array_unique($metodosPermitidos))],
        );
    }

    public function statusHttp(): int
    {
        return 405;
    }
}
