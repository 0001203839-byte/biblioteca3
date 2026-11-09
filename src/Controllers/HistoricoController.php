<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\Movimentacao;
use App\Http\Request;
use App\Http\Response;
use App\Services\HistoricoService;

final class HistoricoController
{
    public function __construct(private readonly HistoricoService $historico)
    {
    }

    public function listar(Request $request): Response
    {
        $resultado = $this->historico->listar($request->query());

        return Response::sucesso(
            $resultado->mapear(static fn (Movimentacao $movimentacao): array => $movimentacao->toArray()),
            200,
            ['paginacao' => $resultado->meta()],
        );
    }
}
