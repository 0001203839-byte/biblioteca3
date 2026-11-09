<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Filtros\FiltroHistorico;
use App\Repositories\Contracts\HistoricoRepositoryInterface;
use App\Support\ResultadoPaginado;

final class HistoricoService
{
    public function __construct(private readonly HistoricoRepositoryInterface $historico)
    {
    }

    public function listar(array $parametros): ResultadoPaginado
    {
        return $this->historico->listar(FiltroHistorico::daQuery($parametros));
    }
}
