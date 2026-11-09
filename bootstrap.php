<?php

declare(strict_types=1);

use App\Support\Env;

$raiz = __DIR__;

if (is_file($raiz . '/vendor/autoload.php')) {
    require $raiz . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $classe) use ($raiz): void {
        if (!str_starts_with($classe, 'App\\')) {
            return;
        }

        $arquivo = $raiz . '/src/' . str_replace('\\', '/', substr($classe, 4)) . '.php';

        if (is_file($arquivo)) {
            require $arquivo;
        }
    });
}

Env::carregar($raiz . '/.env');

date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
