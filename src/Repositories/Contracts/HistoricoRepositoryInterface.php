<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Filtros\FiltroHistorico;
use App\Domain\Models\Movimentacao;
use App\Support\ResultadoPaginado;

interface HistoricoRepositoryInterface
{
    public function registrar(Movimentacao $movimentacao): void;

    public function listar(FiltroHistorico $filtro): ResultadoPaginado;
}
