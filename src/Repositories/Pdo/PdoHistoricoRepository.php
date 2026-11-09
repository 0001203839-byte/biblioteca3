<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Enums\TipoMovimentacao;
use App\Domain\Filtros\FiltroHistorico;
use App\Domain\Models\Movimentacao;
use App\Repositories\Contracts\HistoricoRepositoryInterface;
use App\Support\ResultadoPaginado;
use DateTimeImmutable;

final class PdoHistoricoRepository extends PdoRepository implements HistoricoRepositoryInterface
{
    public function registrar(Movimentacao $movimentacao): void
    {
        $this->executar(
            'INSERT INTO historico_movimentacoes (tipo, descricao, usuario_id, material_id, emprestimo_id, conta_id, registrada_em)
             VALUES (:tipo, :descricao, :usuario_id, :material_id, :emprestimo_id, :conta_id, :registrada_em)',
            [
                'tipo' => $movimentacao->tipo()->value,
                'descricao' => $movimentacao->descricao(),
                'usuario_id' => $movimentacao->usuarioId(),
                'material_id' => $movimentacao->materialId(),
                'emprestimo_id' => $movimentacao->emprestimoId(),
                'conta_id' => $movimentacao->contaId(),
                'registrada_em' => $movimentacao->registradaEm(),
            ],
        );

        $movimentacao->definirId($this->ultimoId());
    }

    public function listar(FiltroHistorico $filtro): ResultadoPaginado
    {
        $condicoes = [];
        $parametros = [];

        if ($filtro->usuarioId !== null) {
            $condicoes[] = 'usuario_id = :usuario_id';
            $parametros['usuario_id'] = $filtro->usuarioId;
        }

        if ($filtro->materialId !== null) {
            $condicoes[] = 'material_id = :material_id';
            $parametros['material_id'] = $filtro->materialId;
        }

        if ($filtro->tipo !== null) {
            $condicoes[] = 'tipo = :tipo';
            $parametros['tipo'] = $filtro->tipo->value;
        }

        $where = $condicoes === [] ? '' : ' WHERE ' . implode(' AND ', $condicoes);

        $total = (int) $this->executar('SELECT COUNT(*) FROM historico_movimentacoes' . $where, $parametros)->fetchColumn();

        $linhas = $this->buscarLinhas(
            'SELECT id, tipo, descricao, usuario_id, material_id, emprestimo_id, conta_id, registrada_em
               FROM historico_movimentacoes' . $where . '
              ORDER BY registrada_em DESC, id DESC
              LIMIT :limite OFFSET :deslocamento',
            $parametros + [
                'limite' => $filtro->paginacao->porPagina,
                'deslocamento' => $filtro->paginacao->deslocamento(),
            ],
        );

        return new ResultadoPaginado(
            array_map(fn (array $linha): Movimentacao => $this->hidratar($linha), $linhas),
            $total,
            $filtro->paginacao,
        );
    }

    private function hidratar(array $linha): Movimentacao
    {
        $opcional = static fn (mixed $valor): ?int => $valor === null ? null : (int) $valor;

        return new Movimentacao(
            TipoMovimentacao::from($linha['tipo']),
            $linha['descricao'],
            new DateTimeImmutable($linha['registrada_em']),
            $opcional($linha['usuario_id']),
            $opcional($linha['material_id']),
            $opcional($linha['emprestimo_id']),
            $opcional($linha['conta_id']),
            (int) $linha['id'],
        );
    }
}
