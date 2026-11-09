<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    private static array $valores = [];

    public static function carregar(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }

        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = explode('=', $linha, 2);
            self::$valores[trim($chave)] = trim(trim($valor), "\"'");
        }
    }

    public static function get(string $chave, ?string $padrao = null): ?string
    {
        $valor = getenv($chave);

        if ($valor !== false) {
            return $valor;
        }

        return self::$valores[$chave] ?? $padrao;
    }
}
