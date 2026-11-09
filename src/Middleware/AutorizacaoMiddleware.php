<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Domain\Enums\Permissao;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\NaoAutenticadoException;
use App\Http\Request;
use App\Http\Response;

final class AutorizacaoMiddleware implements MiddlewareInterface
{
    private array $permissoesAceitas;

    public function __construct(Permissao ...$permissoesAceitas)
    {
        $this->permissoesAceitas = $permissoesAceitas;
    }

    public function processar(Request $request, callable $proximo): Response
    {
        $conta = $request->contaAutenticada() ?? throw new NaoAutenticadoException();

        foreach ($this->permissoesAceitas as $permissao) {
            if ($conta->pode($permissao)) {
                return $proximo($request);
            }
        }

        throw new AcessoNegadoException();
    }
}
