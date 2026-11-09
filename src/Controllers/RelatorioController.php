<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\RelatorioService;

final class RelatorioController
{
    public function __construct(private readonly RelatorioService $relatorios)
    {
    }

    public function resumo(Request $request): Response
    {
        return Response::sucesso($this->relatorios->resumo());
    }

    public function emprestimosPorTipo(Request $request): Response
    {
        return Response::sucesso($this->relatorios->emprestimosPorTipo());
    }
}
