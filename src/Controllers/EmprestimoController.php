<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\Emprestimo;
use App\Exceptions\ValidacaoException;
use App\Http\Request;
use App\Http\Response;
use App\Services\EmprestimoService;
use App\Support\Entrada;
use App\Support\RelogioInterface;

final class EmprestimoController
{
    public function __construct(
        private readonly EmprestimoService $emprestimos,
        private readonly RelogioInterface $relogio,
    ) {
    }

    public function listar(Request $request): Response
    {
        $agora = $this->relogio->agora();

        return Response::sucesso(array_map(
            static fn (Emprestimo $emprestimo): array => $emprestimo->toArray($agora),
            $this->emprestimos->listar($request->query(), $request->conta()),
        ));
    }

    public function buscar(Request $request): Response
    {
        $emprestimo = $this->emprestimos->buscar($request->parametroInteiro('id'), $request->conta());

        return Response::sucesso($emprestimo->toArray($this->relogio->agora()));
    }

    public function registrar(Request $request): Response
    {
        $entrada = new Entrada($request->corpo());
        $erros = [];

        $usuarioId = $entrada->inteiro('usuario_id');
        if ($usuarioId < 1) {
            $erros['usuario_id'] = 'Informe o identificador do usuário.';
        }

        $materialId = $entrada->inteiro('material_id');
        if ($materialId < 1) {
            $erros['material_id'] = 'Informe o identificador do material.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        $emprestimo = $this->emprestimos->emprestar($usuarioId, $materialId, $request->conta());

        return Response::criado($emprestimo->toArray($this->relogio->agora()));
    }

    public function devolver(Request $request): Response
    {
        $resultado = $this->emprestimos->devolver($request->parametroInteiro('id'), $request->conta());

        return Response::sucesso([
            'emprestimo' => $resultado->emprestimo->toArray($this->relogio->agora()),
            'multa' => $resultado->multa?->toArray(),
        ]);
    }
}
