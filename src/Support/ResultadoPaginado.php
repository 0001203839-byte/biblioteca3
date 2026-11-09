<?php

declare(strict_types=1);

namespace App\Support;

final class ResultadoPaginado
{
    public function __construct(
        public readonly array $itens,
        public readonly int $total,
        public readonly Paginacao $paginacao,
    ) {
    }

    public function mapear(callable $transformacao): array
    {
        return array_map($transformacao, $this->itens);
    }

    public function meta(): array
    {
        return [
            'pagina' => $this->paginacao->pagina,
            'por_pagina' => $this->paginacao->porPagina,
            'total_itens' => $this->total,
            'total_paginas' => max(1, (int) ceil($this->total / $this->paginacao->porPagina)),
        ];
    }
}
