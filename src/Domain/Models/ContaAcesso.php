<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Enums\Perfil;
use App\Domain\Enums\Permissao;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\ValidacaoException;
use LogicException;

final class ContaAcesso
{
    public function __construct(
        private readonly string $nome,
        private readonly string $email,
        private string $senhaHash,
        private readonly Perfil $perfil,
        private readonly ?int $usuarioId = null,
        private bool $ativa = true,
        private ?int $id = null,
    ) {
        if ($perfil === Perfil::LEITOR && $usuarioId === null) {
            throw new LogicException('Contas de leitor precisam estar vinculadas a um usuário da biblioteca.');
        }
    }

    public static function criar(string $nome, string $email, string $senha, Perfil $perfil, ?int $usuarioId = null): self
    {
        $email = mb_strtolower(trim($email));
        $erros = [];

        if (mb_strlen(trim($nome)) < 3) {
            $erros['nome'] = 'O nome deve ter pelo menos 3 caracteres.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $erros['email'] = 'Informe um e-mail válido.';
        }

        if ($erro = self::validarSenha($senha)) {
            $erros['senha'] = $erro;
        }

        if ($perfil === Perfil::LEITOR && $usuarioId === null) {
            $erros['usuario_id'] = 'Contas de leitor precisam estar vinculadas a um usuário.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        return new self(trim($nome), $email, password_hash($senha, PASSWORD_DEFAULT), $perfil, $usuarioId);
    }

    public static function validarSenha(string $senha): ?string
    {
        if (mb_strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
            return 'A senha deve ter no mínimo 8 caracteres, com letras e números.';
        }

        return null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('A conta já possui identificador.');
        }

        $this->id = $id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function senhaHash(): string
    {
        return $this->senhaHash;
    }

    public function perfil(): Perfil
    {
        return $this->perfil;
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function estaAtiva(): bool
    {
        return $this->ativa;
    }

    public function senhaConfere(string $senha): bool
    {
        return password_verify($senha, $this->senhaHash);
    }

    public function precisaAtualizarHash(): bool
    {
        return password_needs_rehash($this->senhaHash, PASSWORD_DEFAULT);
    }

    public function regerarHash(string $senhaAtual): void
    {
        $this->senhaHash = password_hash($senhaAtual, PASSWORD_DEFAULT);
    }

    public function pode(Permissao $permissao): bool
    {
        return $this->ativa && $this->perfil->permite($permissao);
    }

    public function exigirPermissao(Permissao $permissao): void
    {
        if (!$this->pode($permissao)) {
            throw new AcessoNegadoException();
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'perfil' => $this->perfil->value,
            'perfil_descricao' => $this->perfil->descricao(),
            'usuario_id' => $this->usuarioId,
            'ativa' => $this->ativa,
        ];
    }
}
