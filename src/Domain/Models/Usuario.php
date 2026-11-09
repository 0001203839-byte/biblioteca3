<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\ValueObjects\Dinheiro;
use App\Exceptions\LimiteEmprestimosException;
use App\Exceptions\UsuarioBloqueadoException;
use App\Exceptions\ValidacaoException;
use LogicException;

abstract class Usuario
{
    private ?int $id;
    private string $nome;
    private string $email;
    private string $documento;
    private bool $bloqueado;

    public function __construct(
        string $nome,
        string $email,
        string $documento,
        bool $bloqueado = false,
        ?int $id = null,
    ) {
        $nome = trim($nome);
        $email = mb_strtolower(trim($email));
        $documento = trim($documento);

        $erros = [];

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 120) {
            $erros['nome'] = 'O nome deve ter entre 3 e 120 caracteres.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 150) {
            $erros['email'] = 'Informe um e-mail válido.';
        }

        if (!preg_match('/^[A-Za-z0-9.\-\/]{3,30}$/', $documento)) {
            $erros['documento'] = 'Informe a matrícula ou documento com 3 a 30 caracteres (letras, números, ponto, hífen ou barra).';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->documento = $documento;
        $this->bloqueado = $bloqueado;
    }

    abstract public function tipo(): string;

    abstract public function descricaoTipo(): string;

    abstract public function limiteEmprestimos(): int;

    abstract public function diasExtrasPrazo(): int;

    abstract public function multaPorDia(): Dinheiro;

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('O usuário já possui identificador.');
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

    public function documento(): string
    {
        return $this->documento;
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado;
    }

    public function bloquear(): void
    {
        $this->bloqueado = true;
    }

    public function desbloquear(): void
    {
        $this->bloqueado = false;
    }

    public function garantirQueNaoEstaBloqueado(): void
    {
        if ($this->bloqueado) {
            throw new UsuarioBloqueadoException();
        }
    }

    public function garantirLimiteDeEmprestimos(int $emprestimosAtivos): void
    {
        if ($emprestimosAtivos >= $this->limiteEmprestimos()) {
            throw new LimiteEmprestimosException($this->limiteEmprestimos());
        }
    }

    public function podeEmprestar(int $emprestimosAtivos): bool
    {
        return !$this->bloqueado && $emprestimosAtivos < $this->limiteEmprestimos();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'documento' => $this->documento,
            'tipo' => $this->tipo(),
            'tipo_descricao' => $this->descricaoTipo(),
            'bloqueado' => $this->bloqueado,
            'limite_emprestimos' => $this->limiteEmprestimos(),
            'dias_extras_prazo' => $this->diasExtrasPrazo(),
            'multa_por_dia' => $this->multaPorDia()->emDecimal(),
        ];
    }
}
