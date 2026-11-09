<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Filtros\FiltroHistorico;
use App\Domain\Models\Movimentacao;
use App\Repositories\Contracts\HistoricoRepositoryInterface;
use App\Support\ResultadoPaginado;
use RuntimeException;

final class HistoricoRepositoryEmMemoria implements HistoricoRepositoryInterface
{
    private array $movimentacoes = [];
    private bool $deveFalhar = false;

    public function falharNaProximaGravacao(): void
    {
        $this->deveFalhar = true;
    }

    public function registrar(Movimentacao $movimentacao): void
    {
        if ($this->deveFalhar) {
            $this->deveFalhar = false;
            throw new RuntimeException('Falha simulada ao gravar o histórico.');
        }

        $movimentacao->definirId(count($this->movimentacoes) + 1);
        $this->movimentacoes[] = $movimentacao;
    }

    public function listar(FiltroHistorico $filtro): ResultadoPaginado
    {
        $itens = array_values(array_filter(
            $this->movimentacoes,
            static fn (Movimentacao $movimentacao): bool => ($filtro->usuarioId === null || $movimentacao->usuarioId() === $filtro->usuarioId)
                && ($filtro->tipo === null || $movimentacao->tipo() === $filtro->tipo),
        ));

        return new ResultadoPaginado($itens, count($itens), $filtro->paginacao);
    }

    public function todas(): array
    {
        return $this->movimentacoes;
    }
}
