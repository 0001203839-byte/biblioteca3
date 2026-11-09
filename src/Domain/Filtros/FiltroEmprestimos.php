<?php

declare(strict_types=1);

namespace App\Domain\Filtros;

use App\Exceptions\ValidacaoException;
use App\Support\Entrada;
use DateTimeImmutable;

final class FiltroEmprestimos
{
    public const STATUS = ['ativo', 'atrasado', 'devolvido'];

    public function __construct(
        public readonly DateTimeImmutable $referencia,
        public readonly ?int $usuarioId = null,
        public readonly ?int $materialId = null,
        public readonly ?string $status = null,
    ) {
    }

    public static function daQuery(array $query, DateTimeImmutable $referencia): self
    {
        $entrada = new Entrada($query);
        $erros = [];

        $status = $entrada->textoOuNulo('status');
        if ($status !== null && !in_array(mb_strtolower($status), self::STATUS, true)) {
            $erros['status'] = 'Use ativo, atrasado ou devolvido.';
        }

        if ($entrada->tem('usuario_id') && ($entrada->inteiroOuNulo('usuario_id') ?? 0) < 1) {
            $erros['usuario_id'] = 'Informe um identificador de usuário válido.';
        }

        if ($entrada->tem('material_id') && ($entrada->inteiroOuNulo('material_id') ?? 0) < 1) {
            $erros['material_id'] = 'Informe um identificador de material válido.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros, 'Parâmetros de consulta inválidos.');
        }

        return new self(
            $referencia,
            $entrada->inteiroOuNulo('usuario_id'),
            $entrada->inteiroOuNulo('material_id'),
            $status === null ? null : mb_strtolower($status),
        );
    }

    public function restritoAoUsuario(int $usuarioId): self
    {
        return new self($this->referencia, $usuarioId, $this->materialId, $this->status);
    }
}
