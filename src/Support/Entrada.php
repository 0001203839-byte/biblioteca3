<?php

declare(strict_types=1);

namespace App\Support;

final class Entrada
{
    public function __construct(private readonly array $dados)
    {
    }

    public function tem(string $campo): bool
    {
        return array_key_exists($campo, $this->dados);
    }

    public function texto(string $campo): string
    {
        $valor = $this->dados[$campo] ?? '';

        if (is_bool($valor) || !is_scalar($valor)) {
            return '';
        }

        return trim((string) $valor);
    }

    public function textoOuNulo(string $campo): ?string
    {
        $valor = $this->texto($campo);

        return $valor === '' ? null : $valor;
    }

    public function inteiro(string $campo): int
    {
        return $this->inteiroOuNulo($campo) ?? 0;
    }

    public function inteiroOuNulo(string $campo): ?int
    {
        $valor = $this->dados[$campo] ?? null;

        if (is_int($valor)) {
            return $valor;
        }

        if (is_string($valor) && preg_match('/^-?\d{1,18}$/', trim($valor))) {
            return (int) trim($valor);
        }

        return null;
    }
}
