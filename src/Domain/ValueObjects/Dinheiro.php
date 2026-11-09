<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Dinheiro
{
    private function __construct(private readonly int $centavos)
    {
        if ($centavos < 0) {
            throw new InvalidArgumentException('Valor monetário não pode ser negativo.');
        }
    }

    public static function deCentavos(int $centavos): self
    {
        return new self($centavos);
    }

    public static function deDecimal(string $valor): self
    {
        $valor = trim($valor);

        if (!preg_match('/^(\d+)(?:\.(\d{1,2})\d*)?$/', $valor, $partes)) {
            throw new InvalidArgumentException("Valor monetário inválido: {$valor}");
        }

        $centavos = str_pad($partes[2] ?? '0', 2, '0');

        return new self((int) $partes[1] * 100 + (int) $centavos);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function centavos(): int
    {
        return $this->centavos;
    }

    public function somar(self $outro): self
    {
        return new self($this->centavos + $outro->centavos);
    }

    public function multiplicar(int $fator): self
    {
        return new self($this->centavos * $fator);
    }

    public function maiorQue(self $outro): bool
    {
        return $this->centavos > $outro->centavos;
    }

    public function ehZero(): bool
    {
        return $this->centavos === 0;
    }

    public function emDecimal(): string
    {
        return sprintf('%d.%02d', intdiv($this->centavos, 100), $this->centavos % 100);
    }

    public function formatado(): string
    {
        return 'R$ ' . number_format($this->centavos / 100, 2, ',', '.');
    }
}
