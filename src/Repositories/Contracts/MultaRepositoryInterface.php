<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Filtros\FiltroMultas;
use App\Domain\Models\Multa;

interface MultaRepositoryInterface
{
    public function listar(FiltroMultas $filtro): array;

    public function salvar(Multa $multa): void;
}
