<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Models\TokenAcesso;
use App\Repositories\Contracts\TokenAcessoRepositoryInterface;
use DateTimeImmutable;

final class PdoTokenAcessoRepository extends PdoRepository implements TokenAcessoRepositoryInterface
{
    public function buscarPorHash(string $hash): ?TokenAcesso
    {
        $linha = $this->buscarLinha(
            'SELECT id, conta_id, token_hash, criado_em, expira_em, revogado_em FROM tokens_acesso WHERE token_hash = :hash',
            ['hash' => $hash],
        );

        if ($linha === null) {
            return null;
        }

        return new TokenAcesso(
            (int) $linha['conta_id'],
            $linha['token_hash'],
            new DateTimeImmutable($linha['criado_em']),
            new DateTimeImmutable($linha['expira_em']),
            $this->paraData($linha['revogado_em']),
            (int) $linha['id'],
        );
    }

    public function salvar(TokenAcesso $token): void
    {
        if ($token->id() === null) {
            $this->executar(
                'INSERT INTO tokens_acesso (conta_id, token_hash, criado_em, expira_em)
                 VALUES (:conta_id, :token_hash, :criado_em, :expira_em)',
                [
                    'conta_id' => $token->contaId(),
                    'token_hash' => $token->hash(),
                    'criado_em' => $token->criadoEm(),
                    'expira_em' => $token->expiraEm(),
                ],
            );
            $token->definirId($this->ultimoId());

            return;
        }

        $this->executar(
            'UPDATE tokens_acesso SET revogado_em = :revogado_em WHERE id = :id',
            ['revogado_em' => $token->revogadoEm(), 'id' => $token->id()],
        );
    }
}
