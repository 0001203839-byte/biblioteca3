<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\NaoAutenticadoException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

final class AutenticacaoMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthService $autenticacao)
    {
    }

    public function processar(Request $request, callable $proximo): Response
    {
        $token = $request->tokenBearer();

        if ($token === null) {
            throw new NaoAutenticadoException(
                'Envie o token no cabeçalho Authorization no formato "Bearer <token>".',
                'TOKEN_AUSENTE',
            );
        }

        $request->autenticarComo($this->autenticacao->autenticar($token));

        return $proximo($request);
    }
}
