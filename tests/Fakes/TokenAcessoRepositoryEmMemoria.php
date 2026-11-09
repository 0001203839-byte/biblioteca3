<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Models\TokenAcesso;
use App\Repositories\Contracts\TokenAcessoRepositoryInterface;

final class TokenAcessoRepositoryEmMemoria implements TokenAcessoRepositoryInterface
{
    private array $tokens = [];

    public function buscarPorHash(string $hash): ?TokenAcesso
    {
        return $this->tokens[$hash] ?? null;
    }

    public function salvar(TokenAcesso $token): void
    {
        $token->definirId(count($this->tokens) + 1);
        $this->tokens[$token->hash()] = $token;
    }
}
