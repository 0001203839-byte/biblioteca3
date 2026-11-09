<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Enums\Permissao;
use App\Domain\Filtros\FiltroMultas;
use App\Domain\Models\ContaAcesso;
use App\Exceptions\AcessoNegadoException;
use App\Repositories\Contracts\MultaRepositoryInterface;

final class MultaService
{
    public function __construct(private readonly MultaRepositoryInterface $multas)
    {
    }

    public function listar(array $parametros, ContaAcesso $solicitante): array
    {
        $filtro = FiltroMultas::daQuery($parametros);

        if (!$solicitante->pode(Permissao::CONSULTAR_MULTAS)) {
            if (!$solicitante->pode(Permissao::CONSULTAR_PROPRIAS_MULTAS) || $solicitante->usuarioId() === null) {
                throw new AcessoNegadoException();
            }

            $filtro = $filtro->restritoAoUsuario($solicitante->usuarioId());
        }

        return $this->multas->listar($filtro);
    }
}
