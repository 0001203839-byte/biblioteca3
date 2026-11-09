USE biblioteca_digital;

INSERT INTO usuarios (id, nome, email, documento, tipo, bloqueado) VALUES
    (1, 'Ana Souza', 'ana.souza@email.com', 'ALU-2024001', 'aluno', 0),
    (2, 'Carlos Mendes', 'carlos.mendes@email.com', 'PROF-1187', 'professor', 0),
    (3, 'Marina Costa', 'marina.costa@email.com', 'PESQ-0452', 'pesquisador', 0);

INSERT INTO materiais (id, tipo, titulo, autor, ano_publicacao, quantidade_total, quantidade_disponivel) VALUES
    (1, 'livro', 'PHP Orientado a Objetos', 'Ricardo Almeida', 2023, 3, 3),
    (2, 'revista', 'Tecnologia Hoje', 'Editora Tech Brasil', 2025, 2, 2),
    (3, 'ebook', 'APIs REST com PHP', 'Juliana Prado', 2024, 5, 5),
    (4, 'livro', 'Banco de Dados Relacional', 'Fernanda Rocha', 2021, 1, 0),
    (5, 'livro', 'Engenharia de Software na Prática', 'Luciana Teixeira', 2020, 2, 2),
    (6, 'livro', 'Arquitetura Limpa na Prática', 'Eduardo Martins', 2022, 2, 1),
    (7, 'revista', 'Ciência & Dados', 'Instituto de Pesquisa Aplicada', 2025, 3, 3),
    (8, 'revista', 'Revista Brasileira de Computação', 'Sociedade de Computação', 2024, 2, 2),
    (9, 'ebook', 'Código Limpo em PHP', 'Bruno Carvalho', 2023, 4, 4),
    (10, 'ebook', 'Estruturas de Dados com PHP', 'Patrícia Lopes', 2022, 3, 3);

INSERT INTO livros (material_id, isbn, editora, paginas) VALUES
    (1, '978-85-7522-610-4', 'Novatec', 384),
    (4, '978-85-352-9034-7', 'Elsevier', 512),
    (5, '978-65-00-01950-6', 'Independente', 408),
    (6, '978-85-508-0460-6', 'Alta Books', 296);

INSERT INTO revistas (material_id, edicao, issn) VALUES
    (2, 148, '2318-4590'),
    (7, 52, '2675-1123'),
    (8, 37, '0104-6500');

INSERT INTO ebooks (material_id, formato, url_acesso) VALUES
    (3, 'pdf', 'https://acervo.biblioteca.local/ebooks/apis-rest-com-php'),
    (9, 'epub', 'https://acervo.biblioteca.local/ebooks/codigo-limpo-em-php'),
    (10, 'pdf', 'https://acervo.biblioteca.local/ebooks/estruturas-de-dados-com-php');

INSERT INTO contas_acesso (id, usuario_id, nome, email, senha_hash, perfil, ativa) VALUES
    (1, NULL, 'Administrador do Sistema', 'admin@biblioteca.local', '$2y$10$j4gN3Mws4skhA50IIEtP1eWaq6zmnPKOhe3Uh9MzWMBAxReieQMhy', 'administrador', 1),
    (2, NULL, 'Paula Ribeiro', 'paula.ribeiro@biblioteca.local', '$2y$10$Ekxa0qINc2l/zxHlEwvGM.e296KY25OJrs7U9g1sOpegd38qI8/1m', 'bibliotecario', 1),
    (3, 1, 'Ana Souza', 'ana.souza@email.com', '$2y$10$MG/G0lLAvRUoXyFiLyONs.eepNjVC5le36MmnsR7oKUTGjZ61poVe', 'leitor', 1),
    (4, 2, 'Carlos Mendes', 'carlos.mendes@email.com', '$2y$10$MG/G0lLAvRUoXyFiLyONs.eepNjVC5le36MmnsR7oKUTGjZ61poVe', 'leitor', 1),
    (5, 3, 'Marina Costa', 'marina.costa@email.com', '$2y$10$MG/G0lLAvRUoXyFiLyONs.eepNjVC5le36MmnsR7oKUTGjZ61poVe', 'leitor', 1);

INSERT INTO emprestimos (id, usuario_id, material_id, data_emprestimo, data_prevista, data_devolucao, status) VALUES
    (1, 2, 4, NOW() - INTERVAL 30 DAY, CURDATE() - INTERVAL 9 DAY, NULL, 'ativo'),
    (2, 1, 5, NOW() - INTERVAL 40 DAY, CURDATE() - INTERVAL 26 DAY, NOW() - INTERVAL 24 DAY, 'devolvido'),
    (3, 3, 7, NOW() - INTERVAL 35 DAY, CURDATE() - INTERVAL 23 DAY, NOW() - INTERVAL 20 DAY, 'devolvido'),
    (4, 2, 9, NOW() - INTERVAL 50 DAY, CURDATE() - INTERVAL 33 DAY, NOW() - INTERVAL 32 DAY, 'devolvido'),
    (5, 3, 6, NOW() - INTERVAL 25 DAY, CURDATE() - INTERVAL 6 DAY, NULL, 'ativo'),
    (6, 1, 10, NOW() - INTERVAL 15 DAY, CURDATE() - INTERVAL 5 DAY, NOW() - INTERVAL 7 DAY, 'devolvido');

INSERT INTO multas (id, emprestimo_id, usuario_id, dias_atraso, valor, status, criada_em, paga_em) VALUES
    (1, 2, 1, 2, 4.00, 'pendente', NOW() - INTERVAL 24 DAY, NULL),
    (2, 3, 3, 3, 4.50, 'paga', NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 18 DAY),
    (3, 4, 2, 1, 1.00, 'pendente', NOW() - INTERVAL 32 DAY, NULL);

INSERT INTO historico_movimentacoes (tipo, descricao, usuario_id, material_id, emprestimo_id, conta_id, registrada_em) VALUES
    ('emprestimo', 'Empréstimo de "Código Limpo em PHP" para Carlos Mendes.', 2, 9, 4, 2, NOW() - INTERVAL 50 DAY),
    ('emprestimo', 'Empréstimo de "Engenharia de Software na Prática" para Ana Souza.', 1, 5, 2, 2, NOW() - INTERVAL 40 DAY),
    ('emprestimo', 'Empréstimo de "Ciência & Dados" para Marina Costa.', 3, 7, 3, 2, NOW() - INTERVAL 35 DAY),
    ('devolucao', 'Devolução de "Código Limpo em PHP" por Carlos Mendes com 1 dia(s) de atraso.', 2, 9, 4, 2, NOW() - INTERVAL 32 DAY),
    ('multa', 'Multa de R$ 1,00 aplicada a Carlos Mendes por 1 dia(s) de atraso.', 2, 9, 4, 2, NOW() - INTERVAL 32 DAY),
    ('emprestimo', 'Empréstimo de "Banco de Dados Relacional" para Carlos Mendes.', 2, 4, 1, 2, NOW() - INTERVAL 30 DAY),
    ('emprestimo', 'Empréstimo de "Arquitetura Limpa na Prática" para Marina Costa.', 3, 6, 5, 2, NOW() - INTERVAL 25 DAY),
    ('devolucao', 'Devolução de "Engenharia de Software na Prática" por Ana Souza com 2 dia(s) de atraso.', 1, 5, 2, 2, NOW() - INTERVAL 24 DAY),
    ('multa', 'Multa de R$ 4,00 aplicada a Ana Souza por 2 dia(s) de atraso.', 1, 5, 2, 2, NOW() - INTERVAL 24 DAY),
    ('devolucao', 'Devolução de "Ciência & Dados" por Marina Costa com 3 dia(s) de atraso.', 3, 7, 3, 2, NOW() - INTERVAL 20 DAY),
    ('multa', 'Multa de R$ 4,50 aplicada a Marina Costa por 3 dia(s) de atraso.', 3, 7, 3, 2, NOW() - INTERVAL 20 DAY),
    ('emprestimo', 'Empréstimo de "Estruturas de Dados com PHP" para Ana Souza.', 1, 10, 6, 2, NOW() - INTERVAL 15 DAY),
    ('devolucao', 'Devolução de "Estruturas de Dados com PHP" por Ana Souza dentro do prazo.', 1, 10, 6, 2, NOW() - INTERVAL 7 DAY);
