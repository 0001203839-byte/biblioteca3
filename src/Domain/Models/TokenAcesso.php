<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTimeImmutable;

final class TokenAcesso
{
    public function __construct(
        private readonly int $contaId,
        private readonly string $hash,
        private readonly DateTimeImmutable $criadoEm,
        private readonly DateTimeImmutable $expiraEm,
        private ?DateTimeImmutable $revogadoEm = null,
        private ?int $id = null,
    ) {
    }

    public static function gerarValorAleatorio(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hashDe(string $valor): string
    {
        return hash('sha256', $valor);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        $this->id ??= $id;
    }

    public function contaId(): int
    {
        return $this->contaId;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function criadoEm(): DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function expiraEm(): DateTimeImmutable
    {
        return $this->expiraEm;
    }

    public function revogadoEm(): ?DateTimeImmutable
    {
        return $this->revogadoEm;
    }

    public function revogar(DateTimeImmutable $momento): void
    {
        $this->revogadoEm ??= $momento;
    }

    public function estaValido(DateTimeImmutable $momento): bool
    {
        return $this->revogadoEm === null && $momento < $this->expiraEm;
    }
}
