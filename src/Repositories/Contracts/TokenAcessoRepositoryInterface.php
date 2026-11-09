<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Models\TokenAcesso;

interface TokenAcessoRepositoryInterface
{
    public function buscarPorHash(string $hash): ?TokenAcesso;

    public function salvar(TokenAcesso $token): void;
}
