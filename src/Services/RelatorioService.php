<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Filtros\FiltroMateriais;
use App\Repositories\Contracts\RelatorioRepositoryInterface;
use App\Support\RelogioInterface;

final class RelatorioService
{
    public function __construct(
        private readonly RelatorioRepositoryInterface $relatorios,
        private readonly RelogioInterface $relogio,
    ) {
    }

    public function resumo(): array
    {
        $agora = $this->relogio->agora();
        $materiais = $this->relatorios->totaisDeMateriais();
        $emprestimos = $this->relatorios->totaisDeEmprestimos($agora);
        $multas = $this->relatorios->multasPendentes();

        return [
            'gerado_em' => $agora->format('Y-m-d H:i:s'),
            'total_materiais' => $materiais['total'],
            'materiais_disponiveis' => $materiais['disponiveis'],
            'materiais_indisponiveis' => $materiais['indisponiveis'],
            'total_usuarios' => $this->relatorios->totalDeUsuarios(),
            'emprestimos_ativos' => $emprestimos['ativos'],
            'emprestimos_atrasados' => $emprestimos['atrasados'],
            'total_devolucoes' => $emprestimos['devolvidos'],
            'multas_pendentes' => [
                'quantidade' => $multas['quantidade'],
                'valor_total' => $multas['valor_total']->emDecimal(),
                'valor_total_formatado' => $multas['valor_total']->formatado(),
            ],
            'emprestimos_por_tipo' => $this->emprestimosPorTipo(),
        ];
    }

    public function emprestimosPorTipo(): array
    {
        $totais = $this->relatorios->emprestimosPorTipoDeMaterial();
        $resultado = [];

        foreach (FiltroMateriais::TIPOS as $tipo) {
            $resultado[$tipo] = $totais[$tipo] ?? 0;
        }

        return $resultado;
    }
}
