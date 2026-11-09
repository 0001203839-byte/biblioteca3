<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;

final class BancoEmMemoria
{
    public static function criar(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec((string) file_get_contents(__DIR__ . '/schema.sqlite.sql'));

        return $pdo;
    }

    public static function popular(PDO $pdo): void
    {
        $pdo->exec("INSERT INTO usuarios (id, nome, email, documento, tipo) VALUES
            (1, 'Ana Souza', 'ana.souza@email.com', 'ALU-2024001', 'aluno'),
            (2, 'Carlos Mendes', 'carlos.mendes@email.com', 'PROF-1187', 'professor'),
            (3, 'Marina Costa', 'marina.costa@email.com', 'PESQ-0452', 'pesquisador')");

        $pdo->exec("INSERT INTO materiais (id, tipo, titulo, autor, ano_publicacao, quantidade_total, quantidade_disponivel) VALUES
            (1, 'livro', 'PHP Orientado a Objetos', 'Ricardo Almeida', 2023, 3, 3),
            (2, 'revista', 'Tecnologia Hoje', 'Editora Tech Brasil', 2025, 2, 2),
            (3, 'ebook', 'APIs REST com PHP', 'Juliana Prado', 2024, 5, 5),
            (4, 'livro', 'Banco de Dados Relacional', 'Fernanda Rocha', 2021, 1, 0)");

        $pdo->exec("INSERT INTO livros (material_id, isbn, editora, paginas) VALUES
            (1, '978-85-7522-610-4', 'Novatec', 384),
            (4, '978-85-352-9034-7', 'Elsevier', 512)");
        $pdo->exec("INSERT INTO revistas (material_id, edicao, issn) VALUES (2, 148, '2318-4590')");
        $pdo->exec("INSERT INTO ebooks (material_id, formato, url_acesso) VALUES (3, 'pdf', NULL)");

        $inserirConta = $pdo->prepare(
            'INSERT INTO contas_acesso (usuario_id, nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?, ?)',
        );
        $hashLeitor = password_hash('Leitor@123', PASSWORD_DEFAULT);

        foreach ([
            [null, 'Administrador', 'admin@biblioteca.local', password_hash('Admin@123', PASSWORD_DEFAULT), 'administrador'],
            [null, 'Paula Ribeiro', 'paula.ribeiro@biblioteca.local', password_hash('Biblio@123', PASSWORD_DEFAULT), 'bibliotecario'],
            [1, 'Ana Souza', 'ana.souza@email.com', $hashLeitor, 'leitor'],
            [2, 'Carlos Mendes', 'carlos.mendes@email.com', $hashLeitor, 'leitor'],
            [3, 'Marina Costa', 'marina.costa@email.com', $hashLeitor, 'leitor'],
        ] as $conta) {
            $inserirConta->execute($conta);
        }

        $pdo->exec("INSERT INTO emprestimos (id, usuario_id, material_id, data_emprestimo, data_prevista, status) VALUES
            (1, 2, 4, '2026-02-01 09:00:00', '2026-02-22', 'ativo')");
    }

    public static function contar(PDO $pdo, string $tabela): int
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
    }
}
