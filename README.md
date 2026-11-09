# Biblioteca Digital Integrada

API REST para gestão de biblioteca, segunda versão do sistema de empréstimos. Controla materiais (livros, revistas e e-books), usuários (alunos, professores e pesquisadores), empréstimos, devoluções, multas e histórico de movimentações, com autenticação por token e permissões por perfil.

Feito em PHP puro, orientado a objetos, sem framework.

## Tecnologias

- PHP 8.2 ou superior (extensões `pdo`, `pdo_mysql`, `mbstring`, `json`)
- MySQL 8 ou MariaDB 10.4+
- PDO com prepared statements
- PHPUnit 11 para os testes
- Composer (apenas para autoload e PHPUnit)

## Estrutura

```
biblioteca-digital/
├── config/app.php               configurações lidas do .env
├── database/
│   ├── schema.sql               criação do banco e das tabelas
│   └── seed.sql                 dados iniciais
├── docs/
│   ├── API.md                   documentação dos endpoints
│   ├── UML.md                   diagrama de classes e do banco
│   ├── RELATORIO_TECNICO.md     arquitetura, POO e SOLID
│   ├── EVIDENCIAS_TESTES_API.md testes feitos na API
│   └── postman/                 coleção para importar no Postman/Insomnia
├── public/
│   ├── index.php                ponto de entrada
│   └── .htaccess                reescrita de URL para Apache
├── src/
│   ├── Aplicacao.php            monta as dependências e registra as rotas
│   ├── Controllers/
│   ├── Domain/
│   │   ├── Enums/
│   │   ├── Factories/
│   │   ├── Filtros/
│   │   ├── Models/
│   │   ├── Politicas/
│   │   └── ValueObjects/
│   ├── Exceptions/
│   ├── Http/                    Request, Response e Router
│   ├── Middleware/
│   ├── Repositories/
│   │   ├── Contracts/           interfaces
│   │   └── Pdo/                 implementações com PDO
│   ├── Services/
│   └── Support/
├── tests/
│   ├── Fakes/                   repositórios em memória e relógio fixo
│   ├── Integration/
│   └── Unit/
├── bootstrap.php
├── composer.json
└── phpunit.xml
```

Requisição → `Router` → middlewares (autenticação e autorização) → `Controller` → `Service` → `Repository` → banco. O detalhamento está em `docs/RELATORIO_TECNICO.md`.

## Instalação

1. Instale as dependências:

   ```bash
   composer install
   ```

   O `composer install` é necessário para rodar os testes. A API funciona mesmo sem a pasta `vendor`, porque o `bootstrap.php` tem um autoload próprio.

2. Crie o arquivo de configuração:

   ```bash
   cp .env.example .env
   ```

   Ajuste `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`. O `.env` não vai para o repositório.

3. Crie o banco e carregue os dados iniciais:

   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```

   No phpMyAdmin ou no MySQL Workbench, basta executar os dois arquivos nessa ordem. O `schema.sql` apaga e recria as tabelas, então ele também serve para zerar o banco.

4. Suba a API:

   ```bash
   php -S localhost:8000 -t public public/index.php
   ```

   No XAMPP/Apache, aponte o navegador para a pasta `public`, por exemplo `http://localhost/biblioteca-digital/public/api/materiais`. O `.htaccess` já repassa o cabeçalho `Authorization`.

## Variáveis do .env

| Variável | Padrão | Uso |
|---|---|---|
| `APP_DEBUG` | `false` | Mostra a mensagem real em erros 500 |
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso usado nas datas |
| `DB_*` | - | Conexão com o MySQL |
| `TOKEN_VALIDADE_MINUTOS` | `120` | Tempo de vida do token |
| `MULTA_VALOR_MAXIMO` | vazio | Se preenchido (ex.: `50.00`), limita o valor de cada multa |

## Contas e dados iniciais

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `admin@biblioteca.local` | `Admin@123` |
| Bibliotecário | `paula.ribeiro@biblioteca.local` | `Biblio@123` |
| Leitor (Ana, aluna) | `ana.souza@email.com` | `Leitor@123` |
| Leitor (Carlos, professor) | `carlos.mendes@email.com` | `Leitor@123` |
| Leitor (Marina, pesquisadora) | `marina.costa@email.com` | `Leitor@123` |

| Usuário | Tipo | Limite | Prazo extra | Multa/dia |
|---|---|---|---|---|
| Ana Souza (id 1) | Aluno | 3 | - | R$ 2,00 |
| Carlos Mendes (id 2) | Professor | 5 | +7 dias | R$ 1,00 |
| Marina Costa (id 3) | Pesquisador | 7 | +5 dias | R$ 1,50 |

| Material | Tipo | Disponível |
|---|---|---|
| PHP Orientado a Objetos (id 1) | Livro | 3 exemplares |
| Tecnologia Hoje (id 2) | Revista | 2 exemplares |
| APIs REST com PHP (id 3) | E-book | 5 acessos simultâneos |
| Banco de Dados Relacional (id 4) | Livro | 0 de 1 (emprestado ao Carlos, em atraso) |
| Arquitetura Limpa na Prática (id 6) | Livro | 1 de 2 (emprestado à Marina, vencido há 6 dias) |

O seed tem ainda mais livros, revistas e e-books, seis empréstimos (ativos e devolvidos), três multas e o histórico correspondente. As datas são relativas ao dia em que o seed é carregado, então o empréstimo 5 da Marina sempre vence 6 dias antes.

## Testes

```bash
composer test
```

ou

```bash
vendor/bin/phpunit
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
```

Os testes de domínio e de serviço usam repositórios em memória. Os de integração usam SQLite em memória (extensão `pdo_sqlite`), então nenhum teste precisa do MySQL.

## Exemplos de requisição

Login:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"paula.ribeiro@biblioteca.local","senha":"Biblio@123"}'
```

Guarde o valor de `dados.token` e envie nas próximas chamadas.

Cenário A, Ana pega o livro PHP Orientado a Objetos:

```bash
curl -X POST http://localhost:8000/api/emprestimos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"usuario_id":1,"material_id":1}'
```

Cenário C, Marina devolve o empréstimo 5 (6 dias de atraso, multa de R$ 9,00):

```bash
curl -X POST http://localhost:8000/api/emprestimos/5/devolucao \
  -H "Authorization: Bearer SEU_TOKEN"
```

Materiais com filtros:

```bash
curl "http://localhost:8000/api/materiais?tipo=livro&disponivel=true&ordenar=titulo&direcao=asc&pagina=1&por_pagina=5" \
  -H "Authorization: Bearer SEU_TOKEN"
```

Relatório (só administrador):

```bash
curl http://localhost:8000/api/relatorios/resumo -H "Authorization: Bearer TOKEN_DO_ADMIN"
```

Para o Postman ou Insomnia, importe `docs/postman/biblioteca-digital.postman_collection.json`. Rode primeiro as três requisições de login; elas guardam os tokens em variáveis da coleção. Rodando a coleção inteira na ordem, com o banco recém-carregado, todos os casos passam.

## Endpoints

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/logout` | Revoga o token |
| GET | `/api/auth/me` | Conta autenticada |
| GET | `/api/materiais` | Listar materiais (filtros e paginação) |
| GET | `/api/materiais/{id}` | Consultar material |
| POST | `/api/materiais` | Cadastrar material |
| PUT | `/api/materiais/{id}` | Atualizar material |
| GET | `/api/usuarios` | Listar usuários |
| GET | `/api/usuarios/{id}` | Consultar usuário |
| POST | `/api/usuarios` | Cadastrar usuário |
| POST | `/api/usuarios/{id}/bloqueio` | Bloquear usuário |
| POST | `/api/usuarios/{id}/desbloqueio` | Desbloquear usuário |
| GET | `/api/emprestimos` | Listar empréstimos (`status`, `usuario_id`) |
| POST | `/api/emprestimos` | Registrar empréstimo |
| GET | `/api/emprestimos/{id}` | Consultar empréstimo |
| POST | `/api/emprestimos/{id}/devolucao` | Registrar devolução |
| GET | `/api/multas` | Listar multas (`status`) |
| GET | `/api/historico` | Movimentações (`usuario_id`, `tipo`, paginação) |
| GET | `/api/relatorios/resumo` | Relatório gerencial |
| GET | `/api/relatorios/emprestimos-por-tipo` | Empréstimos por tipo de material |

Detalhes de parâmetros, corpo, permissões e exemplos de resposta em `docs/API.md`.

## Princípios SOLID aplicados

- **SRP:** controller só trata HTTP, serviço só regra de negócio, repositório só SQL (ex.: `EmprestimoController`, `EmprestimoService`, `PdoEmprestimoRepository`).
- **OCP:** novas políticas de multa entram como classes que implementam `PoliticaMultaInterface` (ex.: `MultaComValorMaximo`), sem alterar o `EmprestimoService`. O `Pesquisador` entrou como subclasse de `Usuario`, sem `if` de tipo.
- **LSP:** `Livro`, `Revista` e `EBook` são usados como `Material` em todo o sistema; `Aluno`, `Professor` e `Pesquisador` como `Usuario`.
- **ISP:** uma interface pequena por necessidade; consultas de relatório ficam em `RelatorioRepositoryInterface`, separadas dos repositórios de escrita.
- **DIP:** serviços recebem interfaces no construtor; só a `Aplicacao` conhece as classes PDO. Os testes trocam tudo por implementações em memória.

Explicação completa em `docs/RELATORIO_TECNICO.md`.


Desenvolvido por Felipe.
