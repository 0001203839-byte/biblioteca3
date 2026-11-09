<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'token_validade_minutos' => (int) Env::get('TOKEN_VALIDADE_MINUTOS', '120'),
    'multa_valor_maximo' => Env::get('MULTA_VALOR_MAXIMO', ''),
    'banco' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'porta' => (int) Env::get('DB_PORT', '3306'),
        'nome' => Env::get('DB_DATABASE', 'biblioteca_digital'),
        'usuario' => Env::get('DB_USERNAME', 'root'),
        'senha' => Env::get('DB_PASSWORD', ''),
    ],
];
