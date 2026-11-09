<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Enums\StatusMulta;
use App\Domain\ValueObjects\Dinheiro;
use DateTimeImmutable;
use LogicException;

final class Multa
{
    public function __construct(
        private readonly int $emprestimoId,
        private readonly int $usuarioId,
        private readonly int $diasAtraso,
        private readonly Dinheiro $valor,
        private readonly DateTimeImmutable $criadaEm,
        private StatusMulta $status = StatusMulta::PENDENTE,
        private ?DateTimeImmutable $pagaEm = null,
        private ?int $id = null,
    ) {
        if ($diasAtraso < 1) {
            throw new LogicException('Uma multa só pode ser gerada para empréstimos com atraso.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('A multa já possui identificador.');
        }

        $this->id = $id;
    }

    public function emprestimoId(): int
    {
        return $this->emprestimoId;
    }

    public function usuarioId(): int
    {
        return $this->usuarioId;
    }

    public function diasAtraso(): int
    {
        return $this->diasAtraso;
    }

    public function valor(): Dinheiro
    {
        return $this->valor;
    }

    public function criadaEm(): DateTimeImmutable
    {
        return $this->criadaEm;
    }

    public function status(): StatusMulta
    {
        return $this->status;
    }

    public function pagaEm(): ?DateTimeImmutable
    {
        return $this->pagaEm;
    }

    public function estaPendente(): bool
    {
        return $this->status === StatusMulta::PENDENTE;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'emprestimo_id' => $this->emprestimoId,
            'usuario_id' => $this->usuarioId,
            'dias_atraso' => $this->diasAtraso,
            'valor' => $this->valor->emDecimal(),
            'valor_formatado' => $this->valor->formatado(),
            'status' => $this->status->value,
            'criada_em' => $this->criadaEm->format('Y-m-d H:i:s'),
            'paga_em' => $this->pagaEm?->format('Y-m-d H:i:s'),
        ];
    }
}
