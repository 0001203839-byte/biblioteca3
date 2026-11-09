<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Support\Entrada;

final class Livro extends Material
{
    private string $isbn;
    private string $editora;
    private int $paginas;

    public function __construct(
        string $titulo,
        string $autor,
        int $anoPublicacao,
        int $quantidadeTotal,
        string $isbn,
        string $editora,
        int $paginas,
        ?int $quantidadeDisponivel = null,
        ?int $id = null,
    ) {
        $this->isbn = strtoupper(trim($isbn));
        $this->editora = trim($editora);
        $this->paginas = $paginas;

        parent::__construct($titulo, $autor, $anoPublicacao, $quantidadeTotal, $quantidadeDisponivel, $id);
    }

    public static function deEntrada(Entrada $dados, ?int $quantidadeDisponivel = null, ?int $id = null): static
    {
        return new self(
            $dados->texto('titulo'),
            $dados->texto('autor'),
            $dados->inteiro('ano_publicacao'),
            $dados->inteiro('quantidade_total'),
            $dados->texto('isbn'),
            $dados->texto('editora'),
            $dados->inteiro('paginas'),
            $quantidadeDisponivel,
            $id,
        );
    }

    public function tipo(): string
    {
        return 'livro';
    }

    public function descricaoTipo(): string
    {
        return 'Livro';
    }

    public function prazoBaseEmDias(): int
    {
        return 14;
    }

    public function isbn(): string
    {
        return $this->isbn;
    }

    public function detalhes(): array
    {
        return [
            'isbn' => $this->isbn,
            'editora' => $this->editora,
            'paginas' => $this->paginas,
        ];
    }

    protected function validarDetalhes(): array
    {
        $erros = [];
        $digitos = str_replace(['-', ' '], '', $this->isbn);

        if (!preg_match('/^(\d{9}[\dX]|\d{13})$/', $digitos)) {
            $erros['isbn'] = 'Informe um ISBN válido com 10 ou 13 dígitos.';
        }

        if (mb_strlen($this->editora) < 2 || mb_strlen($this->editora) > 120) {
            $erros['editora'] = 'Informe a editora (2 a 120 caracteres).';
        }

        if ($this->paginas < 1) {
            $erros['paginas'] = 'O número de páginas deve ser maior que zero.';
        }

        return $erros;
    }
}
