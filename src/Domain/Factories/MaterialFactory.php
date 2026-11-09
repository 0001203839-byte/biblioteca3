<?php

declare(strict_types=1);

namespace App\Domain\Factories;

use App\Domain\Models\EBook;
use App\Domain\Models\Livro;
use App\Domain\Models\Material;
use App\Domain\Models\Revista;
use App\Exceptions\ValidacaoException;
use App\Support\Entrada;

final class MaterialFactory
{
    private const TIPOS = [
        'livro' => Livro::class,
        'revista' => Revista::class,
        'ebook' => EBook::class,
    ];

    public function tipos(): array
    {
        return array_keys(self::TIPOS);
    }

    public function criar(array $dados): Material
    {
        $entrada = new Entrada($dados);

        return $this->classeDoTipo($entrada->texto('tipo'))::deEntrada($entrada);
    }

    public function reconstituir(array $registro): Material
    {
        $entrada = new Entrada($registro);

        return $this->classeDoTipo($entrada->texto('tipo'))::deEntrada(
            $entrada,
            $entrada->inteiro('quantidade_disponivel'),
            $entrada->inteiro('id'),
        );
    }

    public function atualizar(Material $atual, array $dados): Material
    {
        $recebidos = new Entrada($dados);

        if ($recebidos->tem('tipo') && mb_strtolower($recebidos->texto('tipo')) !== $atual->tipo()) {
            throw new ValidacaoException(['tipo' => 'O tipo do material não pode ser alterado.']);
        }

        $editaveis = $atual->dadosEditaveis();
        $entrada = new Entrada(array_replace($editaveis, array_intersect_key($dados, $editaveis)));

        $novoTotal = $entrada->inteiro('quantidade_total');
        $emprestados = $atual->quantidadeEmprestada();

        if ($novoTotal >= 1 && $novoTotal < $emprestados) {
            throw new ValidacaoException([
                'quantidade_total' => "Há {$emprestados} exemplar(es) emprestado(s); a quantidade total não pode ser menor que isso.",
            ]);
        }

        return $atual::deEntrada($entrada, max(0, $novoTotal - $emprestados), $atual->id());
    }

    private function classeDoTipo(string $tipo): string
    {
        $tipo = mb_strtolower($tipo);

        if (!isset(self::TIPOS[$tipo])) {
            throw new ValidacaoException([
                'tipo' => 'Tipo de material inválido. Use: ' . implode(', ', $this->tipos()) . '.',
            ]);
        }

        return self::TIPOS[$tipo];
    }
}
