<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Models\Usuario;
use App\Repositories\Contracts\UsuarioRepositoryInterface;

final class UsuarioRepositoryEmMemoria implements UsuarioRepositoryInterface
{
    private array $usuarios = [];
    private int $proximoId = 1;

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->usuarios[$id] ?? null;
    }

    public function buscarParaAtualizacao(int $id): ?Usuario
    {
        return $this->buscarPorId($id);
    }

    public function listar(?string $tipo = null): array
    {
        return array_values(array_filter(
            $this->usuarios,
            static fn (Usuario $usuario): bool => $tipo === null || $usuario->tipo() === $tipo,
        ));
    }

    public function existeEmail(string $email): bool
    {
        foreach ($this->usuarios as $usuario) {
            if ($usuario->email() === mb_strtolower($email)) {
                return true;
            }
        }

        return false;
    }

    public function existeDocumento(string $documento): bool
    {
        foreach ($this->usuarios as $usuario) {
            if ($usuario->documento() === $documento) {
                return true;
            }
        }

        return false;
    }

    public function salvar(Usuario $usuario): void
    {
        if ($usuario->id() === null) {
            $usuario->definirId($this->proximoId++);
        }

        $this->usuarios[$usuario->id()] = $usuario;
    }
}
