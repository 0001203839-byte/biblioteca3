<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Enums\TipoMovimentacao;
use DateTimeImmutable;

final class Movimentacao
{
    public function __construct(
        private readonly TipoMovimentacao $tipo,
        private readonly string $descricao,
        private readonly DateTimeImmutable $registradaEm,
        private readonly ?int $usuarioId = null,
        private readonly ?int $materialId = null,
        private readonly ?int $emprestimoId = null,
        private readonly ?int $contaId = null,
        private ?int $id = null,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        $this->id ??= $id;
    }

    public function tipo(): TipoMovimentacao
    {
        return $this->tipo;
    }

    public function descricao(): string
    {
        return $this->descricao;
    }

    public function registradaEm(): DateTimeImmutable
    {
        return $this->registradaEm;
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function materialId(): ?int
    {
        return $this->materialId;
    }

    public function emprestimoId(): ?int
    {
        return $this->emprestimoId;
    }

    public function contaId(): ?int
    {
        return $this->contaId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'descricao' => $this->descricao,
            'usuario_id' => $this->usuarioId,
            'material_id' => $this->materialId,
            'emprestimo_id' => $this->emprestimoId,
            'conta_id' => $this->contaId,
            'registrada_em' => $this->registradaEm->format('Y-m-d H:i:s'),
        ];
    }
}
