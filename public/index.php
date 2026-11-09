<?php

declare(strict_types=1);

use App\Aplicacao;
use App\Http\Request;
use App\Http\Response;

require dirname(__DIR__) . '/bootstrap.php';

try {
    $aplicacao = Aplicacao::criar(require dirname(__DIR__) . '/config/app.php');
    $resposta = $aplicacao->processar(Request::capturar());
} catch (Throwable $erro) {
    error_log((string) $erro);
    $resposta = Response::erro(500, 'ERRO_INTERNO', 'Não foi possível iniciar a aplicação.');
}

$resposta->enviar();
