<?php

declare(strict_types=1);

namespace App\Domain\Filtros;

use App\Exceptions\ValidacaoException;
use App\Support\Entrada;
use App\Support\Paginacao;

final class FiltroMateriais
{
    public const ORDENACOES = ['id', 'titulo', 'autor', 'ano', 'disponibilidade'];
    public const TIPOS = ['livro', 'revista', 'ebook'];

    public readonly Paginacao $paginacao;

    public function __construct(
        public readonly ?string $titulo = null,
        public readonly ?string $autor = null,
        public readonly ?string $tipo = null,
        public readonly ?bool $disponivel = null,
        public readonly string $ordenarPor = 'titulo',
        public readonly string $direcao = 'asc',
        ?Paginacao $paginacao = null,
    ) {
        $this->paginacao = $paginacao ?? new Paginacao();
    }

    public static function daQuery(array $query): self
    {
        $entrada = new Entrada($query);
        $erros = [];

        $tipo = $entrada->textoOuNulo('tipo');
        if ($tipo !== null) {
            $tipo = mb_strtolower($tipo);
            if (!in_array($tipo, self::TIPOS, true)) {
                $erros['tipo'] = 'Use livro, revista ou ebook.';
            }
        }

        $disponivel = null;
        if ($entrada->tem('disponivel')) {
            $disponivel = is_scalar($query['disponivel'])
                ? filter_var($query['disponivel'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null;
            if ($disponivel === null) {
                $erros['disponivel'] = 'Use true ou false.';
            }
        }

        $ordenarPor = mb_strtolower($entrada->textoOuNulo('ordenar') ?? 'titulo');
        if (!in_array($ordenarPor, self::ORDENACOES, true)) {
            $erros['ordenar'] = 'Ordenações aceitas: ' . implode(', ', self::ORDENACOES) . '.';
        }

        $direcao = mb_strtolower($entrada->textoOuNulo('direcao') ?? 'asc');
        if (!in_array($direcao, ['asc', 'desc'], true)) {
            $erros['direcao'] = 'Use asc ou desc.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros, 'Parâmetros de consulta inválidos.');
        }

        return new self(
            $entrada->textoOuNulo('titulo'),
            $entrada->textoOuNulo('autor'),
            $tipo,
            $disponivel,
            $ordenarPor,
            $direcao,
            Paginacao::daQuery($query),
        );
    }
}
