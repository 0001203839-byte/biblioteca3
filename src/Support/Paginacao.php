<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\ValidacaoException;

final class Paginacao
{
    public const POR_PAGINA_PADRAO = 10;
    public const POR_PAGINA_MAXIMO = 100;

    public function __construct(
        public readonly int $pagina = 1,
        public readonly int $porPagina = self::POR_PAGINA_PADRAO,
    ) {
        $erros = [];

        if ($pagina < 1) {
            $erros['pagina'] = 'A página deve ser um número inteiro maior ou igual a 1.';
        }

        if ($porPagina < 1 || $porPagina > self::POR_PAGINA_MAXIMO) {
            $erros['por_pagina'] = 'Informe entre 1 e ' . self::POR_PAGINA_MAXIMO . ' itens por página.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros, 'Parâmetros de consulta inválidos.');
        }
    }

    public static function daQuery(array $query): self
    {
        $entrada = new Entrada($query);

        return new self(
            $entrada->tem('pagina') ? $entrada->inteiro('pagina') : 1,
            $entrada->tem('por_pagina') ? $entrada->inteiro('por_pagina') : self::POR_PAGINA_PADRAO,
        );
    }

    public function deslocamento(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }
}
