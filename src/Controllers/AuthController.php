<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Support\Entrada;

final class AuthController
{
    public function __construct(private readonly AuthService $autenticacao)
    {
    }

    public function login(Request $request): Response
    {
        $dados = $request->corpo();
        $entrada = new Entrada($dados);
        $senha = is_string($dados['senha'] ?? null) ? $dados['senha'] : '';

        return Response::sucesso($this->autenticacao->login($entrada->texto('email'), $senha));
    }

    public function logout(Request $request): Response
    {
        $this->autenticacao->logout((string) $request->tokenBearer());

        return Response::sucesso(['mensagem' => 'Sessão encerrada. O token foi revogado.']);
    }

    public function perfil(Request $request): Response
    {
        $conta = $request->conta();

        return Response::sucesso($conta->toArray() + [
            'permissoes' => array_map(static fn ($permissao) => $permissao->value, $conta->perfil()->permissoes()),
        ]);
    }
}
