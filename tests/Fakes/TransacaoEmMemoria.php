<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Repositories\Contracts\TransacaoInterface;
use Throwable;

final class TransacaoEmMemoria implements TransacaoInterface
{
    public int $confirmadas = 0;
    public int $revertidas = 0;

    public function executar(callable $operacao): mixed
    {
        try {
            $resultado = $operacao();
            $this->confirmadas++;

            return $resultado;
        } catch (Throwable $erro) {
            $this->revertidas++;

            throw $erro;
        }
    }
}
