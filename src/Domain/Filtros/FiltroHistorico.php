<?php

declare(strict_types=1);

namespace App\Domain\Filtros;

use App\Domain\Enums\TipoMovimentacao;
use App\Exceptions\ValidacaoException;
use App\Support\Entrada;
use App\Support\Paginacao;

final class FiltroHistorico
{
    public readonly Paginacao $paginacao;

    public function __construct(
        public readonly ?int $usuarioId = null,
        public readonly ?int $materialId = null,
        public readonly ?TipoMovimentacao $tipo = null,
        ?Paginacao $paginacao = null,
    ) {
        $this->paginacao = $paginacao ?? new Paginacao(1, 20);
    }

    public static function daQuery(array $query): self
    {
        $entrada = new Entrada($query);
        $erros = [];

        $tipo = null;
        $tipoInformado = $entrada->textoOuNulo('tipo');
        if ($tipoInformado !== null) {
            $tipo = TipoMovimentacao::tryFrom(mb_strtolower($tipoInformado));
            if ($tipo === null) {
                $erros['tipo'] = 'Tipos aceitos: ' . implode(', ', array_column(TipoMovimentacao::cases(), 'value')) . '.';
            }
        }

        foreach (['usuario_id', 'material_id'] as $campo) {
            if ($entrada->tem($campo) && ($entrada->inteiroOuNulo($campo) ?? 0) < 1) {
                $erros[$campo] = 'Informe um identificador válido.';
            }
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros, 'Parâmetros de consulta inválidos.');
        }

        return new self(
            $entrada->inteiroOuNulo('usuario_id'),
            $entrada->inteiroOuNulo('material_id'),
            $tipo,
            new Paginacao(
                $entrada->tem('pagina') ? $entrada->inteiro('pagina') : 1,
                $entrada->tem('por_pagina') ? $entrada->inteiro('por_pagina') : 20,
            ),
        );
    }
}
