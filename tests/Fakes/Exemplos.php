<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Enums\Perfil;
use App\Domain\Models\Aluno;
use App\Domain\Models\ContaAcesso;
use App\Domain\Models\EBook;
use App\Domain\Models\Livro;
use App\Domain\Models\Pesquisador;
use App\Domain\Models\Professor;
use App\Domain\Models\Revista;

final class Exemplos
{
    public static function livro(int $exemplares = 3, ?int $disponiveis = null): Livro
    {
        return new Livro('PHP Orientado a Objetos', 'Ricardo Almeida', 2023, $exemplares, '978-85-7522-610-4', 'Novatec', 384, $disponiveis);
    }

    public static function revista(int $exemplares = 2): Revista
    {
        return new Revista('Tecnologia Hoje', 'Editora Tech Brasil', 2025, $exemplares, 148, '2318-4590');
    }

    public static function ebook(int $acessos = 5): EBook
    {
        return new EBook('APIs REST com PHP', 'Juliana Prado', 2024, $acessos, 'pdf');
    }

    public static function ana(): Aluno
    {
        return new Aluno('Ana Souza', 'ana.souza@email.com', 'ALU-2024001');
    }

    public static function carlos(): Professor
    {
        return new Professor('Carlos Mendes', 'carlos.mendes@email.com', 'PROF-1187');
    }

    public static function marina(): Pesquisador
    {
        return new Pesquisador('Marina Costa', 'marina.costa@email.com', 'PESQ-0452');
    }

    public static function conta(Perfil $perfil, ?int $usuarioId = null, int $id = 1): ContaAcesso
    {
        return new ContaAcesso('Conta de teste', "conta{$id}@biblioteca.local", 'hash-nao-utilizado', $perfil, $usuarioId, true, $id);
    }
}
