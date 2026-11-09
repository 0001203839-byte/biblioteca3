<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\ValueObjects\Dinheiro;
use App\Repositories\Contracts\RelatorioRepositoryInterface;
use DateTimeImmutable;

final class PdoRelatorioRepository extends PdoRepository implements RelatorioRepositoryInterface
{
    public function totaisDeMateriais(): array
    {
        $linha = $this->buscarLinha(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN quantidade_disponivel > 0 THEN 1 ELSE 0 END), 0) AS disponiveis
               FROM materiais',
        );

        return [
            'total' => (int) $linha['total'],
            'disponiveis' => (int) $linha['disponiveis'],
            'indisponiveis' => (int) $linha['total'] - (int) $linha['disponiveis'],
        ];
    }

    public function totalDeUsuarios(): int
    {
        return (int) $this->executar('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public function totaisDeEmprestimos(DateTimeImmutable $referencia): array
    {
        $linha = $this->buscarLinha(
            "SELECT COALESCE(SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END), 0) AS ativos,
                    COALESCE(SUM(CASE WHEN status = 'ativo' AND data_prevista < :hoje THEN 1 ELSE 0 END), 0) AS atrasados,
                    COALESCE(SUM(CASE WHEN status = 'devolvido' THEN 1 ELSE 0 END), 0) AS devolvidos
               FROM emprestimos",
            ['hoje' => $referencia->format('Y-m-d')],
        );

        return [
            'ativos' => (int) $linha['ativos'],
            'atrasados' => (int) $linha['atrasados'],
            'devolvidos' => (int) $linha['devolvidos'],
        ];
    }

    public function multasPendentes(): array
    {
        $linha = $this->buscarLinha(
            "SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor), 0) AS total FROM multas WHERE status = 'pendente'",
        );

        return [
            'quantidade' => (int) $linha['quantidade'],
            'valor_total' => Dinheiro::deDecimal((string) $linha['total']),
        ];
    }

    public function emprestimosPorTipoDeMaterial(): array
    {
        $linhas = $this->buscarLinhas(
            'SELECT m.tipo, COUNT(e.id) AS total
               FROM materiais m
               LEFT JOIN emprestimos e ON e.material_id = m.id
              GROUP BY m.tipo
              ORDER BY m.tipo',
        );

        $totais = [];

        foreach ($linhas as $linha) {
            $totais[$linha['tipo']] = (int) $linha['total'];
        }

        return $totais;
    }
}
