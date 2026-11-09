<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Filtros\FiltroMultas;
use App\Domain\Models\Multa;
use App\Repositories\Contracts\MultaRepositoryInterface;

final class MultaRepositoryEmMemoria implements MultaRepositoryInterface
{
    private array $multas = [];
    private int $proximoId = 1;

    public function listar(FiltroMultas $filtro): array
    {
        return array_values(array_filter(
            $this->multas,
            static fn (Multa $multa): bool => ($filtro->status === null || $multa->status() === $filtro->status)
                && ($filtro->usuarioId === null || $multa->usuarioId() === $filtro->usuarioId),
        ));
    }

    public function salvar(Multa $multa): void
    {
        if ($multa->id() === null) {
            $multa->definirId($this->proximoId++);
        }

        $this->multas[$multa->id()] = $multa;
    }

    public function todas(): array
    {
        return array_values($this->multas);
    }
}
