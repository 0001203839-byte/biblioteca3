<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\Usuario;
use App\Http\Request;
use App\Http\Response;
use App\Services\UsuarioService;

final class UsuarioController
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    public function listar(Request $request): Response
    {
        return Response::sucesso(array_map(
            static fn (Usuario $usuario): array => $usuario->toArray(),
            $this->usuarios->listar($request->query()),
        ));
    }

    public function buscar(Request $request): Response
    {
        return Response::sucesso($this->usuarios->buscar($request->parametroInteiro('id'))->toArray());
    }

    public function cadastrar(Request $request): Response
    {
        return Response::criado($this->usuarios->cadastrar($request->corpo(), $request->conta())->toArray());
    }

    public function bloquear(Request $request): Response
    {
        return Response::sucesso($this->usuarios->bloquear($request->parametroInteiro('id'), $request->conta())->toArray());
    }

    public function desbloquear(Request $request): Response
    {
        return Response::sucesso($this->usuarios->desbloquear($request->parametroInteiro('id'), $request->conta())->toArray());
    }
}
