<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Exceptions\MaterialIndisponivelException;
use App\Exceptions\ValidacaoException;
use App\Support\Entrada;
use LogicException;

abstract class Material
{
    private ?int $id;
    private string $titulo;
    private string $autor;
    private int $anoPublicacao;
    private int $quantidadeTotal;
    private int $quantidadeDisponivel;

    public function __construct(
        string $titulo,
        string $autor,
        int $anoPublicacao,
        int $quantidadeTotal,
        ?int $quantidadeDisponivel = null,
        ?int $id = null,
    ) {
        $titulo = trim($titulo);
        $autor = trim($autor);
        $quantidadeDisponivel ??= $quantidadeTotal;

        $erros = $this->validarDadosComuns($titulo, $autor, $anoPublicacao, $quantidadeTotal, $quantidadeDisponivel)
            + $this->validarDetalhes();

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        $this->id = $id;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->anoPublicacao = $anoPublicacao;
        $this->quantidadeTotal = $quantidadeTotal;
        $this->quantidadeDisponivel = $quantidadeDisponivel;
    }

    abstract public static function deEntrada(Entrada $dados, ?int $quantidadeDisponivel = null, ?int $id = null): static;

    abstract public function tipo(): string;

    abstract public function descricaoTipo(): string;

    abstract public function prazoBaseEmDias(): int;

    abstract public function detalhes(): array;

    abstract protected function validarDetalhes(): array;

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('O material já possui identificador.');
        }

        $this->id = $id;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function autor(): string
    {
        return $this->autor;
    }

    public function anoPublicacao(): int
    {
        return $this->anoPublicacao;
    }

    public function quantidadeTotal(): int
    {
        return $this->quantidadeTotal;
    }

    public function quantidadeDisponivel(): int
    {
        return $this->quantidadeDisponivel;
    }

    public function quantidadeEmprestada(): int
    {
        return $this->quantidadeTotal - $this->quantidadeDisponivel;
    }

    public function estaDisponivel(): bool
    {
        return $this->quantidadeDisponivel > 0;
    }

    public function retirarExemplar(): void
    {
        if (!$this->estaDisponivel()) {
            throw new MaterialIndisponivelException();
        }

        $this->quantidadeDisponivel--;
    }

    public function receberExemplar(): void
    {
        if ($this->quantidadeDisponivel >= $this->quantidadeTotal) {
            throw new LogicException('Todos os exemplares deste material já constam como disponíveis.');
        }

        $this->quantidadeDisponivel++;
    }

    public function dadosEditaveis(): array
    {
        return [
            'titulo' => $this->titulo,
            'autor' => $this->autor,
            'ano_publicacao' => $this->anoPublicacao,
            'quantidade_total' => $this->quantidadeTotal,
        ] + $this->detalhes();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo(),
            'tipo_descricao' => $this->descricaoTipo(),
            'titulo' => $this->titulo,
            'autor' => $this->autor,
            'ano_publicacao' => $this->anoPublicacao,
            'quantidade_total' => $this->quantidadeTotal,
            'quantidade_disponivel' => $this->quantidadeDisponivel,
            'disponivel' => $this->estaDisponivel(),
            'prazo_base_dias' => $this->prazoBaseEmDias(),
            'detalhes' => $this->detalhes(),
        ];
    }

    private function validarDadosComuns(string $titulo, string $autor, int $ano, int $total, int $disponivel): array
    {
        $erros = [];
        $anoMaximo = (int) date('Y') + 1;

        if (mb_strlen($titulo) < 2 || mb_strlen($titulo) > 200) {
            $erros['titulo'] = 'O título deve ter entre 2 e 200 caracteres.';
        }

        if (mb_strlen($autor) < 2 || mb_strlen($autor) > 150) {
            $erros['autor'] = 'O autor deve ter entre 2 e 150 caracteres.';
        }

        if ($ano < 1450 || $ano > $anoMaximo) {
            $erros['ano_publicacao'] = "O ano de publicação deve estar entre 1450 e {$anoMaximo}.";
        }

        if ($total < 1) {
            $erros['quantidade_total'] = 'A quantidade total deve ser de pelo menos 1.';
        } elseif ($disponivel < 0 || $disponivel > $total) {
            $erros['quantidade_disponivel'] = 'A quantidade disponível deve ficar entre 0 e a quantidade total.';
        }

        return $erros;
    }
}
