<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RecursoNaoEncontradoException extends AppException
{
    public function __construct(string $mensagem = 'Recurso não encontrado.', string $codigo = 'NAO_ENCONTRADO')
    {
        parent::__construct($mensagem, $codigo);
    }

    public static function material(int $id): self
    {
        return new self("Material {$id} não encontrado.", 'MATERIAL_NAO_ENCONTRADO');
    }

    public static function usuario(int $id): self
    {
        return new self("Usuário {$id} não encontrado.", 'USUARIO_NAO_ENCONTRADO');
    }

    public static function emprestimo(int $id): self
    {
        return new self("Empréstimo {$id} não encontrado.", 'EMPRESTIMO_NAO_ENCONTRADO');
    }

    public static function rota(): self
    {
        return new self('Rota não encontrada.', 'ROTA_NAO_ENCONTRADA');
    }

    public function statusHttp(): int
    {
        return 404;
    }
}
