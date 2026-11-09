<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Repositories\Contracts\TransacaoInterface;
use PDO;
use Throwable;

final class PdoTransacao implements TransacaoInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function executar(callable $operacao): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $operacao();
        }

        $this->pdo->beginTransaction();

        try {
            $resultado = $operacao();
            $this->pdo->commit();

            return $resultado;
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $erro;
        }
    }
}
