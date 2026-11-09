<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum Perfil: string
{
    case ADMINISTRADOR = 'administrador';
    case BIBLIOTECARIO = 'bibliotecario';
    case LEITOR = 'leitor';

    public function permissoes(): array
    {
        return match ($this) {
            self::ADMINISTRADOR => Permissao::cases(),
            self::BIBLIOTECARIO => [
                Permissao::CONSULTAR_MATERIAIS,
                Permissao::CADASTRAR_MATERIAIS,
                Permissao::CONSULTAR_USUARIOS,
                Permissao::CONSULTAR_EMPRESTIMOS,
                Permissao::REGISTRAR_EMPRESTIMOS,
                Permissao::REGISTRAR_DEVOLUCOES,
                Permissao::CONSULTAR_MULTAS,
                Permissao::CONSULTAR_HISTORICO,
            ],
            self::LEITOR => [
                Permissao::CONSULTAR_MATERIAIS,
                Permissao::CONSULTAR_PROPRIOS_EMPRESTIMOS,
                Permissao::CONSULTAR_PROPRIAS_MULTAS,
            ],
        };
    }

    public function permite(Permissao $permissao): bool
    {
        return in_array($permissao, $this->permissoes(), true);
    }

    public function descricao(): string
    {
        return match ($this) {
            self::ADMINISTRADOR => 'Administrador',
            self::BIBLIOTECARIO => 'Bibliotecário',
            self::LEITOR => 'Leitor',
        };
    }
}
