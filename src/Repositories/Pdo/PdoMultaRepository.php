<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Enums\StatusMulta;
use App\Domain\Filtros\FiltroMultas;
use App\Domain\Models\Multa;
use App\Domain\ValueObjects\Dinheiro;
use App\Repositories\Contracts\MultaRepositoryInterface;
use DateTimeImmutable;

final class PdoMultaRepository extends PdoRepository implements MultaRepositoryInterface
{
    public function listar(FiltroMultas $filtro): array
    {
        $condicoes = [];
        $parametros = [];

        if ($filtro->status !== null) {
            $condicoes[] = 'status = :status';
            $parametros['status'] = $filtro->status->value;
        }

        if ($filtro->usuarioId !== null) {
            $condicoes[] = 'usuario_id = :usuario_id';
            $parametros['usuario_id'] = $filtro->usuarioId;
        }

        $where = $condicoes === [] ? '' : ' WHERE ' . implode(' AND ', $condicoes);

        $linhas = $this->buscarLinhas(
            'SELECT id, emprestimo_id, usuario_id, dias_atraso, valor, status, criada_em, paga_em
               FROM multas' . $where . ' ORDER BY criada_em DESC, id DESC',
            $parametros,
        );

        return array_map(fn (array $linha): Multa => $this->hidratar($linha), $linhas);
    }

    public function salvar(Multa $multa): void
    {
        if ($multa->id() === null) {
            $this->executar(
                'INSERT INTO multas (emprestimo_id, usuario_id, dias_atraso, valor, status, criada_em)
                 VALUES (:emprestimo_id, :usuario_id, :dias_atraso, :valor, :status, :criada_em)',
                [
                    'emprestimo_id' => $multa->emprestimoId(),
                    'usuario_id' => $multa->usuarioId(),
                    'dias_atraso' => $multa->diasAtraso(),
                    'valor' => $multa->valor()->emDecimal(),
                    'status' => $multa->status()->value,
                    'criada_em' => $multa->criadaEm(),
                ],
            );
            $multa->definirId($this->ultimoId());

            return;
        }

        $this->executar(
            'UPDATE multas SET status = :status, paga_em = :paga_em WHERE id = :id',
            ['status' => $multa->status()->value, 'paga_em' => $multa->pagaEm(), 'id' => $multa->id()],
        );
    }

    private function hidratar(array $linha): Multa
    {
        return new Multa(
            (int) $linha['emprestimo_id'],
            (int) $linha['usuario_id'],
            (int) $linha['dias_atraso'],
            Dinheiro::deDecimal((string) $linha['valor']),
            new DateTimeImmutable($linha['criada_em']),
            StatusMulta::from($linha['status']),
            $this->paraData($linha['paga_em']),
            (int) $linha['id'],
        );
    }
}
