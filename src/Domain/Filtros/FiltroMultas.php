<?php

declare(strict_types=1);

namespace App\Domain\Filtros;

use App\Domain\Enums\StatusMulta;
use App\Exceptions\ValidacaoException;
use App\Support\Entrada;

final class FiltroMultas
{
    public function __construct(
        public readonly ?StatusMulta $status = null,
        public readonly ?int $usuarioId = null,
    ) {
    }

    public static function daQuery(array $query): self
    {
        $entrada = new Entrada($query);
        $erros = [];

        $status = null;
        $statusInformado = $entrada->textoOuNulo('status');
        if ($statusInformado !== null) {
            $status = StatusMulta::tryFrom(mb_strtolower($statusInformado));
            if ($status === null) {
                $erros['status'] = 'Use pendente ou paga.';
            }
        }

        if ($entrada->tem('usuario_id') && ($entrada->inteiroOuNulo('usuario_id') ?? 0) < 1) {
            $erros['usuario_id'] = 'Informe um identificador de usuário válido.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros, 'Parâmetros de consulta inválidos.');
        }

        return new self($status, $entrada->inteiroOuNulo('usuario_id'));
    }

    public function restritoAoUsuario(int $usuarioId): self
    {
        return new self($this->status, $usuarioId);
    }
}
