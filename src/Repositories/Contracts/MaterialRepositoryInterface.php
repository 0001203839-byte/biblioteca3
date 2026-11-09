<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Filtros\FiltroMateriais;
use App\Domain\Models\Material;
use App\Support\ResultadoPaginado;

interface MaterialRepositoryInterface
{
    public function buscarPorId(int $id): ?Material;

    public function buscarParaAtualizacao(int $id): ?Material;

    public function listar(FiltroMateriais $filtro): ResultadoPaginado;

    public function salvar(Material $material): void;

    public function atualizarDisponibilidade(Material $material): void;
}
