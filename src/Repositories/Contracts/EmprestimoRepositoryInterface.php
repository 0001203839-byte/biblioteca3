<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Filtros\FiltroEmprestimos;
use App\Domain\Models\Emprestimo;

interface EmprestimoRepositoryInterface
{
    public function buscarPorId(int $id): ?Emprestimo;

    public function buscarParaAtualizacao(int $id): ?Emprestimo;

    public function listar(FiltroEmprestimos $filtro): array;

    public function contarAtivosDoUsuario(int $usuarioId): int;

    public function salvar(Emprestimo $emprestimo): void;
}
