<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\Material;
use App\Http\Request;
use App\Http\Response;
use App\Services\MaterialService;

final class MaterialController
{
    public function __construct(private readonly MaterialService $materiais)
    {
    }

    public function listar(Request $request): Response
    {
        $resultado = $this->materiais->listar($request->query());

        return Response::sucesso(
            $resultado->mapear(static fn (Material $material): array => $material->toArray()),
            200,
            ['paginacao' => $resultado->meta()],
        );
    }

    public function buscar(Request $request): Response
    {
        return Response::sucesso($this->materiais->buscar($request->parametroInteiro('id'))->toArray());
    }

    public function cadastrar(Request $request): Response
    {
        return Response::criado($this->materiais->cadastrar($request->corpo(), $request->conta())->toArray());
    }

    public function atualizar(Request $request): Response
    {
        $material = $this->materiais->atualizar($request->parametroInteiro('id'), $request->corpo(), $request->conta());

        return Response::sucesso($material->toArray());
    }
}
