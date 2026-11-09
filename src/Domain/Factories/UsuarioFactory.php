<?php

declare(strict_types=1);

namespace App\Domain\Factories;

use App\Domain\Models\Aluno;
use App\Domain\Models\Pesquisador;
use App\Domain\Models\Professor;
use App\Domain\Models\Usuario;
use App\Exceptions\ValidacaoException;
use App\Support\Entrada;

final class UsuarioFactory
{
    private const TIPOS = [
        'aluno' => Aluno::class,
        'professor' => Professor::class,
        'pesquisador' => Pesquisador::class,
    ];

    public function tipos(): array
    {
        return array_keys(self::TIPOS);
    }

    public function criar(array $dados): Usuario
    {
        $entrada = new Entrada($dados);

        return $this->montar(
            $entrada->texto('tipo'),
            $entrada->texto('nome'),
            $entrada->texto('email'),
            $entrada->texto('documento'),
        );
    }

    public function montar(
        string $tipo,
        string $nome,
        string $email,
        string $documento,
        bool $bloqueado = false,
        ?int $id = null,
    ): Usuario {
        $tipo = mb_strtolower(trim($tipo));

        if (!isset(self::TIPOS[$tipo])) {
            throw new ValidacaoException([
                'tipo' => 'Tipo de usuário inválido. Use: ' . implode(', ', $this->tipos()) . '.',
            ]);
        }

        $classe = self::TIPOS[$tipo];

        return new $classe($nome, $email, $documento, $bloqueado, $id);
    }
}
