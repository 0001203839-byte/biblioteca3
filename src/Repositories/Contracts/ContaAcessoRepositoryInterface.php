<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Models\ContaAcesso;

interface ContaAcessoRepositoryInterface
{
    public function buscarPorId(int $id): ?ContaAcesso;

    public function buscarPorEmail(string $email): ?ContaAcesso;

    public function salvar(ContaAcesso $conta): void;
}
