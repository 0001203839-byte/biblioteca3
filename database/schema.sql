CREATE DATABASE IF NOT EXISTS biblioteca_digital
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biblioteca_digital;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS tokens_acesso;
DROP TABLE IF EXISTS historico_movimentacoes;
DROP TABLE IF EXISTS multas;
DROP TABLE IF EXISTS emprestimos;
DROP TABLE IF EXISTS contas_acesso;
DROP TABLE IF EXISTS ebooks;
DROP TABLE IF EXISTS revistas;
DROP TABLE IF EXISTS livros;
DROP TABLE IF EXISTS materiais;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    documento VARCHAR(30) NOT NULL,
    tipo ENUM('aluno', 'professor', 'pesquisador') NOT NULL,
    bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios PRIMARY KEY (id),
    CONSTRAINT uq_usuarios_email UNIQUE (email),
    CONSTRAINT uq_usuarios_documento UNIQUE (documento),
    INDEX idx_usuarios_tipo (tipo)
) ENGINE = InnoDB;

CREATE TABLE materiais (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('livro', 'revista', 'ebook') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    ano_publicacao SMALLINT UNSIGNED NOT NULL,
    quantidade_total INT UNSIGNED NOT NULL,
    quantidade_disponivel INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_materiais PRIMARY KEY (id),
    CONSTRAINT ck_materiais_total CHECK (quantidade_total > 0),
    CONSTRAINT ck_materiais_disponivel CHECK (quantidade_disponivel <= quantidade_total),
    INDEX idx_materiais_titulo (titulo),
    INDEX idx_materiais_autor (autor),
    INDEX idx_materiais_tipo (tipo)
) ENGINE = InnoDB;

CREATE TABLE livros (
    material_id INT UNSIGNED NOT NULL,
    isbn VARCHAR(17) NOT NULL,
    editora VARCHAR(120) NOT NULL,
    paginas SMALLINT UNSIGNED NOT NULL,
    CONSTRAINT pk_livros PRIMARY KEY (material_id),
    CONSTRAINT uq_livros_isbn UNIQUE (isbn),
    CONSTRAINT fk_livros_material FOREIGN KEY (material_id) REFERENCES materiais (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE revistas (
    material_id INT UNSIGNED NOT NULL,
    edicao INT UNSIGNED NOT NULL,
    issn CHAR(9) NULL,
    CONSTRAINT pk_revistas PRIMARY KEY (material_id),
    CONSTRAINT fk_revistas_material FOREIGN KEY (material_id) REFERENCES materiais (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE ebooks (
    material_id INT UNSIGNED NOT NULL,
    formato ENUM('pdf', 'epub', 'mobi') NOT NULL,
    url_acesso VARCHAR(255) NULL,
    CONSTRAINT pk_ebooks PRIMARY KEY (material_id),
    CONSTRAINT fk_ebooks_material FOREIGN KEY (material_id) REFERENCES materiais (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE contas_acesso (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('administrador', 'bibliotecario', 'leitor') NOT NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_contas_acesso PRIMARY KEY (id),
    CONSTRAINT uq_contas_email UNIQUE (email),
    CONSTRAINT uq_contas_usuario UNIQUE (usuario_id),
    CONSTRAINT fk_contas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT ck_contas_leitor_vinculado CHECK (perfil <> 'leitor' OR usuario_id IS NOT NULL)
) ENGINE = InnoDB;

CREATE TABLE tokens_acesso (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conta_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    criado_em DATETIME NOT NULL,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,
    CONSTRAINT pk_tokens_acesso PRIMARY KEY (id),
    CONSTRAINT uq_tokens_hash UNIQUE (token_hash),
    CONSTRAINT fk_tokens_conta FOREIGN KEY (conta_id) REFERENCES contas_acesso (id) ON DELETE CASCADE,
    INDEX idx_tokens_expiracao (expira_em)
) ENGINE = InnoDB;

CREATE TABLE emprestimos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    data_emprestimo DATETIME NOT NULL,
    data_prevista DATE NOT NULL,
    data_devolucao DATETIME NULL,
    status ENUM('ativo', 'devolvido') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_emprestimos PRIMARY KEY (id),
    CONSTRAINT fk_emprestimos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_emprestimos_material FOREIGN KEY (material_id) REFERENCES materiais (id),
    CONSTRAINT ck_emprestimos_prazo CHECK (data_prevista >= DATE(data_emprestimo)),
    CONSTRAINT ck_emprestimos_status CHECK (
        (status = 'ativo' AND data_devolucao IS NULL)
        OR (status = 'devolvido' AND data_devolucao IS NOT NULL)
    ),
    INDEX idx_emprestimos_usuario_status (usuario_id, status),
    INDEX idx_emprestimos_status_prazo (status, data_prevista)
) ENGINE = InnoDB;

CREATE TABLE multas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    emprestimo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    dias_atraso INT UNSIGNED NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'paga') NOT NULL DEFAULT 'pendente',
    criada_em DATETIME NOT NULL,
    paga_em DATETIME NULL,
    CONSTRAINT pk_multas PRIMARY KEY (id),
    CONSTRAINT uq_multas_emprestimo UNIQUE (emprestimo_id),
    CONSTRAINT fk_multas_emprestimo FOREIGN KEY (emprestimo_id) REFERENCES emprestimos (id),
    CONSTRAINT fk_multas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT ck_multas_valor CHECK (valor >= 0),
    CONSTRAINT ck_multas_dias CHECK (dias_atraso > 0),
    INDEX idx_multas_status (status)
) ENGINE = InnoDB;

CREATE TABLE historico_movimentacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM(
        'emprestimo', 'devolucao', 'multa',
        'cadastro_material', 'atualizacao_material',
        'cadastro_usuario', 'bloqueio_usuario', 'desbloqueio_usuario'
    ) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    usuario_id INT UNSIGNED NULL,
    material_id INT UNSIGNED NULL,
    emprestimo_id INT UNSIGNED NULL,
    conta_id INT UNSIGNED NULL,
    registrada_em DATETIME NOT NULL,
    CONSTRAINT pk_historico PRIMARY KEY (id),
    CONSTRAINT fk_historico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_historico_material FOREIGN KEY (material_id) REFERENCES materiais (id),
    CONSTRAINT fk_historico_emprestimo FOREIGN KEY (emprestimo_id) REFERENCES emprestimos (id),
    CONSTRAINT fk_historico_conta FOREIGN KEY (conta_id) REFERENCES contas_acesso (id),
    INDEX idx_historico_usuario (usuario_id),
    INDEX idx_historico_tipo (tipo),
    INDEX idx_historico_data (registrada_em)
) ENGINE = InnoDB;
