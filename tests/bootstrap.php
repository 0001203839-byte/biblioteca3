<?php

declare(strict_types=1);

$raiz = dirname(__DIR__);

if (is_file($raiz . '/vendor/autoload.php')) {
    require $raiz . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $classe) use ($raiz): void {
        $mapa = ['App\\' => '/src/', 'Tests\\' => '/tests/'];

        foreach ($mapa as $prefixo => $pasta) {
            if (str_starts_with($classe, $prefixo)) {
                $arquivo = $raiz . $pasta . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';

                if (is_file($arquivo)) {
                    require $arquivo;
                }

                return;
            }
        }
    });
}

date_default_timezone_set('America/Sao_Paulo');
