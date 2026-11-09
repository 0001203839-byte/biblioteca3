CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    documento TEXT NOT NULL UNIQUE,
    tipo TEXT NOT NULL CHECK (tipo IN ('aluno', 'professor', 'pesquisador')),
    bloqueado INTEGER NOT NULL DEFAULT 0,
    criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE materiais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL CHECK (tipo IN ('livro', 'revista', 'ebook')),
    titulo TEXT NOT NULL,
    autor TEXT NOT NULL,
    ano_publicacao INTEGER NOT NULL,
    quantidade_total INTEGER NOT NULL CHECK (quantidade_total > 0),
    quantidade_disponivel INTEGER NOT NULL CHECK (quantidade_disponivel >= 0 AND quantidade_disponivel <= quantidade_total),
    criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE livros (
    material_id INTEGER PRIMARY KEY REFERENCES materiais (id) ON DELETE CASCADE,
    isbn TEXT NOT NULL UNIQUE,
    editora TEXT NOT NULL,
    paginas INTEGER NOT NULL
);

CREATE TABLE revistas (
    material_id INTEGER PRIMARY KEY REFERENCES materiais (id) ON DELETE CASCADE,
    edicao INTEGER NOT NULL,
    issn TEXT NULL
);

CREATE TABLE ebooks (
    material_id INTEGER PRIMARY KEY REFERENCES materiais (id) ON DELETE CASCADE,
    formato TEXT NOT NULL CHECK (formato IN ('pdf', 'epub', 'mobi')),
    url_acesso TEXT NULL
);

CREATE TABLE contas_acesso (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NULL UNIQUE REFERENCES usuarios (id),
    nome TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL,
    perfil TEXT NOT NULL CHECK (perfil IN ('administrador', 'bibliotecario', 'leitor')),
    ativa INTEGER NOT NULL DEFAULT 1,
    criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (perfil <> 'leitor' OR usuario_id IS NOT NULL)
);

CREATE TABLE tokens_acesso (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conta_id INTEGER NOT NULL REFERENCES contas_acesso (id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL UNIQUE,
    criado_em TEXT NOT NULL,
    expira_em TEXT NOT NULL,
    revogado_em TEXT NULL
);

CREATE TABLE emprestimos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL REFERENCES usuarios (id),
    material_id INTEGER NOT NULL REFERENCES materiais (id),
    data_emprestimo TEXT NOT NULL,
    data_prevista TEXT NOT NULL,
    data_devolucao TEXT NULL,
    status TEXT NOT NULL DEFAULT 'ativo' CHECK (status IN ('ativo', 'devolvido')),
    criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK ((status = 'ativo' AND data_devolucao IS NULL) OR (status = 'devolvido' AND data_devolucao IS NOT NULL))
);

CREATE TABLE multas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    emprestimo_id INTEGER NOT NULL UNIQUE REFERENCES emprestimos (id),
    usuario_id INTEGER NOT NULL REFERENCES usuarios (id),
    dias_atraso INTEGER NOT NULL CHECK (dias_atraso > 0),
    valor NUMERIC NOT NULL CHECK (valor >= 0),
    status TEXT NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente', 'paga')),
    criada_em TEXT NOT NULL,
    paga_em TEXT NULL
);

CREATE TABLE historico_movimentacoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL,
    descricao TEXT NOT NULL,
    usuario_id INTEGER NULL REFERENCES usuarios (id),
    material_id INTEGER NULL REFERENCES materiais (id),
    emprestimo_id INTEGER NULL REFERENCES emprestimos (id),
    conta_id INTEGER NULL REFERENCES contas_acesso (id),
    registrada_em TEXT NOT NULL
);
