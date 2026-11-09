<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Enums\StatusEmprestimo;
use App\Domain\Filtros\FiltroEmprestimos;
use App\Domain\Models\Emprestimo;
use App\Repositories\Contracts\EmprestimoRepositoryInterface;
use DateTimeImmutable;

final class PdoEmprestimoRepository extends PdoRepository implements EmprestimoRepositoryInterface
{
    private const COLUNAS = 'id, usuario_id, material_id, data_emprestimo, data_prevista, data_devolucao, status';

    public function buscarPorId(int $id): ?Emprestimo
    {
        $linha = $this->buscarLinha('SELECT ' . self::COLUNAS . ' FROM emprestimos WHERE id = :id', ['id' => $id]);

        return $linha === null ? null : $this->hidratar($linha);
    }

    public function buscarParaAtualizacao(int $id): ?Emprestimo
    {
        $linha = $this->buscarLinha(
            'SELECT ' . self::COLUNAS . ' FROM emprestimos WHERE id = :id' . $this->travaDeLinha(),
            ['id' => $id],
        );

        return $linha === null ? null : $this->hidratar($linha);
    }

    public function listar(FiltroEmprestimos $filtro): array
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

        switch ($filtro->status) {
            case 'ativo':
                $condicoes[] = "status = 'ativo'";
                break;
            case 'devolvido':
                $condicoes[] = "status = 'devolvido'";
                break;
            case 'atrasado':
                $condicoes[] = "status = 'ativo' AND data_prevista < :hoje";
                $parametros['hoje'] = $filtro->referencia->format('Y-m-d');
                break;
        }

        $where = $condicoes === [] ? '' : ' WHERE ' . implode(' AND ', $condicoes);

        return array_map(
            fn (array $linha): Emprestimo => $this->hidratar($linha),
            $this->buscarLinhas('SELECT ' . self::COLUNAS . ' FROM emprestimos' . $where . ' ORDER BY data_emprestimo DESC, id DESC', $parametros),
        );
    }

    public function contarAtivosDoUsuario(int $usuarioId): int
    {
        return (int) $this->executar(
            "SELECT COUNT(*) FROM emprestimos WHERE usuario_id = :usuario_id AND status = 'ativo'",
            ['usuario_id' => $usuarioId],
        )->fetchColumn();
    }

    public function salvar(Emprestimo $emprestimo): void
    {
        if ($emprestimo->id() === null) {
            $this->executar(
                'INSERT INTO emprestimos (usuario_id, material_id, data_emprestimo, data_prevista, status)
                 VALUES (:usuario_id, :material_id, :data_emprestimo, :data_prevista, :status)',
                [
                    'usuario_id' => $emprestimo->usuarioId(),
                    'material_id' => $emprestimo->materialId(),
                    'data_emprestimo' => $emprestimo->dataEmprestimo(),
                    'data_prevista' => $emprestimo->dataPrevista()->format('Y-m-d'),
                    'status' => $emprestimo->status()->value,
                ],
            );
            $emprestimo->definirId($this->ultimoId());

            return;
        }

        $this->executar(
            'UPDATE emprestimos SET status = :status, data_devolucao = :data_devolucao WHERE id = :id',
            [
                'status' => $emprestimo->status()->value,
                'data_devolucao' => $emprestimo->dataDevolucao(),
                'id' => $emprestimo->id(),
            ],
        );
    }

    private function hidratar(array $linha): Emprestimo
    {
        return new Emprestimo(
            (int) $linha['usuario_id'],
            (int) $linha['material_id'],
            new DateTimeImmutable($linha['data_emprestimo']),
            new DateTimeImmutable($linha['data_prevista']),
            StatusEmprestimo::from($linha['status']),
            $this->paraData($linha['data_devolucao']),
            (int) $linha['id'],
        );
    }
}
