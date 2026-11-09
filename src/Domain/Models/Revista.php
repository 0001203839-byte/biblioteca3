<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Support\Entrada;

final class Revista extends Material
{
    private int $edicao;
    private ?string $issn;

    public function __construct(
        string $titulo,
        string $autor,
        int $anoPublicacao,
        int $quantidadeTotal,
        int $edicao,
        ?string $issn = null,
        ?int $quantidadeDisponivel = null,
        ?int $id = null,
    ) {
        $this->edicao = $edicao;
        $this->issn = $issn === null || trim($issn) === '' ? null : strtoupper(trim($issn));

        parent::__construct($titulo, $autor, $anoPublicacao, $quantidadeTotal, $quantidadeDisponivel, $id);
    }

    public static function deEntrada(Entrada $dados, ?int $quantidadeDisponivel = null, ?int $id = null): static
    {
        return new self(
            $dados->texto('titulo'),
            $dados->texto('autor'),
            $dados->inteiro('ano_publicacao'),
            $dados->inteiro('quantidade_total'),
            $dados->inteiro('edicao'),
            $dados->textoOuNulo('issn'),
            $quantidadeDisponivel,
            $id,
        );
    }

    public function tipo(): string
    {
        return 'revista';
    }

    public function descricaoTipo(): string
    {
        return 'Revista';
    }

    public function prazoBaseEmDias(): int
    {
        return 7;
    }

    public function detalhes(): array
    {
        return [
            'edicao' => $this->edicao,
            'issn' => $this->issn,
        ];
    }

    protected function validarDetalhes(): array
    {
        $erros = [];

        if ($this->edicao < 1) {
            $erros['edicao'] = 'A edição deve ser um número maior que zero.';
        }

        if ($this->issn !== null && !preg_match('/^\d{4}-\d{3}[\dX]$/', $this->issn)) {
            $erros['issn'] = 'O ISSN deve seguir o formato 0000-0000.';
        }

        return $erros;
    }
}
