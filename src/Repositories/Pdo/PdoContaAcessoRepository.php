<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Enums\Perfil;
use App\Domain\Models\ContaAcesso;
use App\Repositories\Contracts\ContaAcessoRepositoryInterface;

final class PdoContaAcessoRepository extends PdoRepository implements ContaAcessoRepositoryInterface
{
    private const COLUNAS = 'id, usuario_id, nome, email, senha_hash, perfil, ativa';

    public function buscarPorId(int $id): ?ContaAcesso
    {
        $linha = $this->buscarLinha('SELECT ' . self::COLUNAS . ' FROM contas_acesso WHERE id = :id', ['id' => $id]);

        return $linha === null ? null : $this->hidratar($linha);
    }

    public function buscarPorEmail(string $email): ?ContaAcesso
    {
        $linha = $this->buscarLinha(
            'SELECT ' . self::COLUNAS . ' FROM contas_acesso WHERE email = :email',
            ['email' => mb_strtolower(trim($email))],
        );

        return $linha === null ? null : $this->hidratar($linha);
    }

    public function salvar(ContaAcesso $conta): void
    {
        if ($conta->id() === null) {
            $this->executar(
                'INSERT INTO contas_acesso (usuario_id, nome, email, senha_hash, perfil, ativa)
                 VALUES (:usuario_id, :nome, :email, :senha_hash, :perfil, :ativa)',
                [
                    'usuario_id' => $conta->usuarioId(),
                    'nome' => $conta->nome(),
                    'email' => $conta->email(),
                    'senha_hash' => $conta->senhaHash(),
                    'perfil' => $conta->perfil()->value,
                    'ativa' => $conta->estaAtiva(),
                ],
            );
            $conta->definirId($this->ultimoId());

            return;
        }

        $this->executar(
            'UPDATE contas_acesso SET senha_hash = :senha_hash, ativa = :ativa WHERE id = :id',
            ['senha_hash' => $conta->senhaHash(), 'ativa' => $conta->estaAtiva(), 'id' => $conta->id()],
        );
    }

    private function hidratar(array $linha): ContaAcesso
    {
        return new ContaAcesso(
            $linha['nome'],
            $linha['email'],
            $linha['senha_hash'],
            Perfil::from($linha['perfil']),
            $linha['usuario_id'] === null ? null : (int) $linha['usuario_id'],
            (bool) $linha['ativa'],
            (int) $linha['id'],
        );
    }
}
