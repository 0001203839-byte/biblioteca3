<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Models\Usuario;

interface UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarParaAtualizacao(int $id): ?Usuario;

    public function listar(?string $tipo = null): array;

    public function existeEmail(string $email): bool;

    public function existeDocumento(string $documento): bool;

    public function salvar(Usuario $usuario): void;
}
