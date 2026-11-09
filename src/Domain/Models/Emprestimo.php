<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Enums\StatusEmprestimo;
use App\Exceptions\DevolucaoDuplicadaException;
use DateTimeImmutable;
use LogicException;

final class Emprestimo
{
    public function __construct(
        private readonly int $usuarioId,
        private readonly int $materialId,
        private readonly DateTimeImmutable $dataEmprestimo,
        private readonly DateTimeImmutable $dataPrevista,
        private StatusEmprestimo $status = StatusEmprestimo::ATIVO,
        private ?DateTimeImmutable $dataDevolucao = null,
        private ?int $id = null,
    ) {
        if ($dataPrevista->setTime(0, 0) < $dataEmprestimo->setTime(0, 0)) {
            throw new LogicException('A data prevista de devolução não pode ser anterior à data do empréstimo.');
        }
    }

    public static function registrar(Usuario $usuario, Material $material, DateTimeImmutable $momento): self
    {
        if ($usuario->id() === null || $material->id() === null) {
            throw new LogicException('Usuário e material precisam estar cadastrados para gerar um empréstimo.');
        }

        $prazo = $material->prazoBaseEmDias() + $usuario->diasExtrasPrazo();

        return new self(
            $usuario->id(),
            $material->id(),
            $momento,
            $momento->setTime(0, 0)->modify("+{$prazo} days"),
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('O empréstimo já possui identificador.');
        }

        $this->id = $id;
    }

    public function usuarioId(): int
    {
        return $this->usuarioId;
    }

    public function materialId(): int
    {
        return $this->materialId;
    }

    public function dataEmprestimo(): DateTimeImmutable
    {
        return $this->dataEmprestimo;
    }

    public function dataPrevista(): DateTimeImmutable
    {
        return $this->dataPrevista;
    }

    public function dataDevolucao(): ?DateTimeImmutable
    {
        return $this->dataDevolucao;
    }

    public function status(): StatusEmprestimo
    {
        return $this->status;
    }

    public function estaAtivo(): bool
    {
        return $this->status === StatusEmprestimo::ATIVO;
    }

    public function prazoEmDias(): int
    {
        return (int) $this->dataEmprestimo->setTime(0, 0)->diff($this->dataPrevista->setTime(0, 0))->days;
    }

    public function registrarDevolucao(DateTimeImmutable $momento): void
    {
        if (!$this->estaAtivo()) {
            throw new DevolucaoDuplicadaException();
        }

        $this->status = StatusEmprestimo::DEVOLVIDO;
        $this->dataDevolucao = $momento;
    }

    public function diasDeAtraso(DateTimeImmutable $referencia): int
    {
        $base = $this->dataDevolucao ?? $referencia;
        $diferenca = $this->dataPrevista->setTime(0, 0)->diff($base->setTime(0, 0));

        return $diferenca->invert === 1 ? 0 : (int) $diferenca->days;
    }

    public function estaAtrasado(DateTimeImmutable $referencia): bool
    {
        return $this->estaAtivo() && $this->diasDeAtraso($referencia) > 0;
    }

    public function toArray(DateTimeImmutable $referencia): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'material_id' => $this->materialId,
            'data_emprestimo' => $this->dataEmprestimo->format('Y-m-d H:i:s'),
            'data_prevista' => $this->dataPrevista->format('Y-m-d'),
            'data_devolucao' => $this->dataDevolucao?->format('Y-m-d H:i:s'),
            'status' => $this->status->value,
            'atrasado' => $this->estaAtrasado($referencia),
            'dias_atraso' => $this->diasDeAtraso($referencia),
        ];
    }
}
