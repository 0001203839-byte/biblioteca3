<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\Multa;
use App\Http\Request;
use App\Http\Response;
use App\Services\MultaService;

final class MultaController
{
    public function __construct(private readonly MultaService $multas)
    {
    }

    public function listar(Request $request): Response
    {
        return Response::sucesso(array_map(
            static fn (Multa $multa): array => $multa->toArray(),
            $this->multas->listar($request->query(), $request->conta()),
        ));
    }
}
