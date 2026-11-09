<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Models\ContaAcesso;
use App\Repositories\Contracts\ContaAcessoRepositoryInterface;

final class ContaAcessoRepositoryEmMemoria implements ContaAcessoRepositoryInterface
{
    private array $contas = [];
    private int $proximoId = 1;

    public function buscarPorId(int $id): ?ContaAcesso
    {
        return $this->contas[$id] ?? null;
    }

    public function buscarPorEmail(string $email): ?ContaAcesso
    {
        foreach ($this->contas as $conta) {
            if ($conta->email() === mb_strtolower(trim($email))) {
                return $conta;
            }
        }

        return null;
    }

    public function salvar(ContaAcesso $conta): void
    {
        if ($conta->id() === null) {
            $conta->definirId($this->proximoId++);
        }

        $this->contas[$conta->id()] = $conta;
    }
}
