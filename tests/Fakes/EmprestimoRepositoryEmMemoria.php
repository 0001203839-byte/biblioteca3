<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Filtros\FiltroEmprestimos;
use App\Domain\Models\Emprestimo;
use App\Repositories\Contracts\EmprestimoRepositoryInterface;

final class EmprestimoRepositoryEmMemoria implements EmprestimoRepositoryInterface
{
    private array $emprestimos = [];
    private int $proximoId = 1;

    public function buscarPorId(int $id): ?Emprestimo
    {
        return $this->emprestimos[$id] ?? null;
    }

    public function buscarParaAtualizacao(int $id): ?Emprestimo
    {
        return $this->buscarPorId($id);
    }

    public function listar(FiltroEmprestimos $filtro): array
    {
        return array_values(array_filter(
            $this->emprestimos,
            static function (Emprestimo $emprestimo) use ($filtro): bool {
                if ($filtro->usuarioId !== null && $emprestimo->usuarioId() !== $filtro->usuarioId) {
                    return false;
                }

                if ($filtro->materialId !== null && $emprestimo->materialId() !== $filtro->materialId) {
                    return false;
                }

                return match ($filtro->status) {
                    'ativo' => $emprestimo->estaAtivo(),
                    'devolvido' => !$emprestimo->estaAtivo(),
                    'atrasado' => $emprestimo->estaAtrasado($filtro->referencia),
                    default => true,
                };
            },
        ));
    }

    public function contarAtivosDoUsuario(int $usuarioId): int
    {
        return count(array_filter(
            $this->emprestimos,
            static fn (Emprestimo $emprestimo): bool => $emprestimo->usuarioId() === $usuarioId && $emprestimo->estaAtivo(),
        ));
    }

    public function salvar(Emprestimo $emprestimo): void
    {
        if ($emprestimo->id() === null) {
            $emprestimo->definirId($this->proximoId++);
        }

        $this->emprestimos[$emprestimo->id()] = $emprestimo;
    }

    public function total(): int
    {
        return count($this->emprestimos);
    }
}
