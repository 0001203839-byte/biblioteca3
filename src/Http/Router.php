<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\MetodoNaoPermitidoException;
use App\Exceptions\RecursoNaoEncontradoException;

final class Router
{
    private array $rotas = [];

    public function get(string $padrao, callable $acao, array $middlewares = []): void
    {
        $this->adicionar('GET', $padrao, $acao, $middlewares);
    }

    public function post(string $padrao, callable $acao, array $middlewares = []): void
    {
        $this->adicionar('POST', $padrao, $acao, $middlewares);
    }

    public function put(string $padrao, callable $acao, array $middlewares = []): void
    {
        $this->adicionar('PUT', $padrao, $acao, $middlewares);
    }

    public function despachar(Request $request): Response
    {
        $metodosDaRota = [];

        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $request->caminho(), $encontrados)) {
                continue;
            }

            if ($rota['metodo'] !== $request->metodo()) {
                $metodosDaRota[] = $rota['metodo'];
                continue;
            }

            $request->definirParametrosDeRota(array_filter($encontrados, 'is_string', ARRAY_FILTER_USE_KEY));

            return $this->executar($rota, $request);
        }

        if ($metodosDaRota !== []) {
            throw new MetodoNaoPermitidoException($metodosDaRota);
        }

        throw RecursoNaoEncontradoException::rota();
    }

    private function adicionar(string $metodo, string $padrao, callable $acao, array $middlewares): void
    {
        $this->rotas[] = [
            'metodo' => $metodo,
            'regex' => '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[1-9]\d*)', $padrao) . '$#',
            'acao' => $acao,
            'middlewares' => $middlewares,
        ];
    }

    private function executar(array $rota, Request $request): Response
    {
        $proximo = static fn (Request $requisicao): Response => ($rota['acao'])($requisicao);

        foreach (array_reverse($rota['middlewares']) as $middleware) {
            $proximo = static fn (Request $requisicao): Response => $middleware->processar($requisicao, $proximo);
        }

        return $proximo($request);
    }
}
