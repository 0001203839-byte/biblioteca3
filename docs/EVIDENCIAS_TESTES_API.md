# Evidências dos testes da API

Testes executados com o servidor embutido do PHP (`php -S localhost:8000 -t public public/index.php`) logo após carregar `database/schema.sql` e `database/seed.sql`. As requisições também estão na coleção `docs/postman/biblioteca-digital.postman_collection.json`, na mesma ordem.

Contas usadas: administrador (`admin@biblioteca.local` / `Admin@123`), bibliotecária (`paula.ribeiro@biblioteca.local` / `Biblio@123`) e leitora Ana (`ana.souza@email.com` / `Leitor@123`). Os tokens foram omitidos nas respostas e as datas dependem do dia em que o seed foi carregado.

## Resumo

| Caso | Descrição | Método | Endpoint | Perfil | Esperado | Obtido | Resultado |
|---|---|---|---|---|---|---|---|
| T01 | Login do administrador | POST | `/api/auth/login` | - | 200 | 200 | Aprovado |
| T02 | Login com senha incorreta | POST | `/api/auth/login` | - | 401 | 401 | Aprovado |
| T03 | Acesso sem token | GET | `/api/materiais` | - | 401 | 401 | Aprovado |
| T04 | Cenário A - Ana solicita livro disponível | POST | `/api/emprestimos` | Bibliotecário | 201 | 201 | Aprovado |
| T05 | Cenário A - disponibilidade atualizada | GET | `/api/materiais/1` | Bibliotecário | 200 | 200 | Aprovado |
| T06 | Cenário B - Carlos solicita e-book | POST | `/api/emprestimos` | Bibliotecário | 201 | 201 | Aprovado |
| T07 | Cenário B - acessos simultâneos do e-book | GET | `/api/materiais/3` | Bibliotecário | 200 | 200 | Aprovado |
| T08 | Cenário C - Marina devolve 6 dias após o prazo | POST | `/api/emprestimos/5/devolucao` | Bibliotecário | 200 | 200 | Aprovado |
| T09 | Devolução duplicada | POST | `/api/emprestimos/5/devolucao` | Bibliotecário | 409 | 409 | Aprovado |
| T10 | Cenário D - material sem exemplares | POST | `/api/emprestimos` | Bibliotecário | 409 | 409 | Aprovado |
| T11 | Cenário E - leitor tenta cadastrar material | POST | `/api/materiais` | Leitor | 403 | 403 | Aprovado |
| T12 | Cenário F - relatório-resumo | GET | `/api/relatorios/resumo` | Administrador | 200 | 200 | Aprovado |
| T13 | Listagem com filtro, ordenação e paginação | GET | `/api/materiais?tipo=livro&disponivel=true&ordenar=titulo&direcao=desc&pagina=1&por_pagina=2` | Leitor | 200 | 200 | Aprovado |
| T14 | Cadastro de material válido | POST | `/api/materiais` | Bibliotecário | 201 | 201 | Aprovado |
| T15 | Cadastro com dados inválidos | POST | `/api/materiais` | Bibliotecário | 422 | 422 | Aprovado |
| T16 | Atualização por bibliotecário (sem permissão) | PUT | `/api/materiais/11` | Bibliotecário | 403 | 403 | Aprovado |
| T17 | Atualização pelo administrador | PUT | `/api/materiais/11` | Administrador | 200 | 200 | Aprovado |
| T18 | Listar usuários | GET | `/api/usuarios` | Administrador | 200 | 200 | Aprovado |
| T19 | Cadastrar usuário com conta de leitor | POST | `/api/usuarios` | Administrador | 201 | 201 | Aprovado |
| T20 | Cadastrar usuário com e-mail repetido | POST | `/api/usuarios` | Administrador | 409 | 409 | Aprovado |
| T21 | Leitor lista empréstimos (somente os próprios) | GET | `/api/emprestimos` | Leitor | 200 | 200 | Aprovado |
| T22 | Leitor consulta empréstimo de outra pessoa | GET | `/api/emprestimos/1` | Leitor | 403 | 403 | Aprovado |
| T23 | Consultar empréstimo | GET | `/api/emprestimos/1` | Bibliotecário | 200 | 200 | Aprovado |
| T24 | Empréstimos atrasados | GET | `/api/emprestimos?status=atrasado` | Bibliotecário | 200 | 200 | Aprovado |
| T25 | Multas pendentes | GET | `/api/multas?status=pendente` | Bibliotecário | 200 | 200 | Aprovado |
| T26 | Histórico de um usuário | GET | `/api/historico?usuario_id=3&por_pagina=5` | Bibliotecário | 200 | 200 | Aprovado |
| T27 | Empréstimos por tipo de material | GET | `/api/relatorios/emprestimos-por-tipo` | Administrador | 200 | 200 | Aprovado |
| T28 | Bloquear usuário | POST | `/api/usuarios/2/bloqueio` | Administrador | 200 | 200 | Aprovado |
| T29 | Empréstimo para usuário bloqueado | POST | `/api/emprestimos` | Bibliotecário | 409 | 409 | Aprovado |
| T30 | Desbloquear usuário | POST | `/api/usuarios/2/desbloqueio` | Administrador | 200 | 200 | Aprovado |
| T31 | Material inexistente | GET | `/api/materiais/999` | Leitor | 404 | 404 | Aprovado |
| T32 | JSON malformado | POST | `/api/emprestimos` | Bibliotecário | 400 | 400 | Aprovado |
| T33 | Bibliotecário tenta ver relatório | GET | `/api/relatorios/resumo` | Bibliotecário | 403 | 403 | Aprovado |
| T34 | Logout | POST | `/api/auth/logout` | Leitor | 200 | 200 | Aprovado |
| T35 | Uso de token revogado | GET | `/api/auth/me` | Leitor | 401 | 401 | Aprovado |

## Detalhamento

### T01 - Login do administrador

- **Requisição:** `POST /api/auth/login`
- **Perfil:** -
- **Status esperado:** 200
- **Status obtido:** 200

Corpo enviado:

```json
{
    "email": "admin@biblioteca.local",
    "senha": "Admin@123"
}
```

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "token": "<token omitido>",
        "tipo": "Bearer",
        "expira_em": "2026-11-09 11:57:27",
        "conta": {
            "id": 1,
            "nome": "Administrador do Sistema",
            "email": "admin@biblioteca.local",
            "perfil": "administrador",
            "perfil_descricao": "Administrador",
            "usuario_id": null,
            "ativa": true
        }
    }
}
```

### T02 - Login com senha incorreta

- **Requisição:** `POST /api/auth/login`
- **Perfil:** -
- **Status esperado:** 401
- **Status obtido:** 401

Corpo enviado:

```json
{
    "email": "admin@biblioteca.local",
    "senha": "senhaerrada1"
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "CREDENCIAIS_INVALIDAS",
        "mensagem": "E-mail ou senha inválidos."
    }
}
```

### T03 - Acesso sem token

- **Requisição:** `GET /api/materiais`
- **Perfil:** -
- **Status esperado:** 401
- **Status obtido:** 401

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "TOKEN_AUSENTE",
        "mensagem": "Envie o token no cabeçalho Authorization no formato \"Bearer <token>\"."
    }
}
```

### T04 - Cenário A - Ana solicita livro disponível

- **Requisição:** `POST /api/emprestimos`
- **Perfil:** Bibliotecário
- **Status esperado:** 201
- **Status obtido:** 201

Corpo enviado:

```json
{
    "usuario_id": 1,
    "material_id": 1
}
```

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 7,
        "usuario_id": 1,
        "material_id": 1,
        "data_emprestimo": "2026-11-09 09:57:28",
        "data_prevista": "2026-11-23",
        "data_devolucao": null,
        "status": "ativo",
        "atrasado": false,
        "dias_atraso": 0
    }
}
```

### T05 - Cenário A - disponibilidade atualizada

- **Requisição:** `GET /api/materiais/1`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 1,
        "tipo": "livro",
        "tipo_descricao": "Livro",
        "titulo": "PHP Orientado a Objetos",
        "autor": "Ricardo Almeida",
        "ano_publicacao": 2023,
        "quantidade_total": 3,
        "quantidade_disponivel": 2,
        "disponivel": true,
        "prazo_base_dias": 14,
        "detalhes": {
            "isbn": "978-85-7522-610-4",
            "editora": "Novatec",
            "paginas": 384
        }
    }
}
```

### T06 - Cenário B - Carlos solicita e-book

- **Requisição:** `POST /api/emprestimos`
- **Perfil:** Bibliotecário
- **Status esperado:** 201
- **Status obtido:** 201

Corpo enviado:

```json
{
    "usuario_id": 2,
    "material_id": 3
}
```

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 8,
        "usuario_id": 2,
        "material_id": 3,
        "data_emprestimo": "2026-11-09 09:57:28",
        "data_prevista": "2026-11-26",
        "data_devolucao": null,
        "status": "ativo",
        "atrasado": false,
        "dias_atraso": 0
    }
}
```

### T07 - Cenário B - acessos simultâneos do e-book

- **Requisição:** `GET /api/materiais/3`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 3,
        "tipo": "ebook",
        "tipo_descricao": "E-book",
        "titulo": "APIs REST com PHP",
        "autor": "Juliana Prado",
        "ano_publicacao": 2024,
        "quantidade_total": 5,
        "quantidade_disponivel": 4,
        "disponivel": true,
        "prazo_base_dias": 10,
        "detalhes": {
            "formato": "pdf",
            "url_acesso": "https://acervo.biblioteca.local/ebooks/apis-rest-com-php"
        },
        "acessos_simultaneos": 5,
        "acessos_em_uso": 1
    }
}
```

### T08 - Cenário C - Marina devolve 6 dias após o prazo

- **Requisição:** `POST /api/emprestimos/5/devolucao`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "emprestimo": {
            "id": 5,
            "usuario_id": 3,
            "material_id": 6,
            "data_emprestimo": "2026-10-15 09:57:27",
            "data_prevista": "2026-11-03",
            "data_devolucao": "2026-11-09 09:57:28",
            "status": "devolvido",
            "atrasado": false,
            "dias_atraso": 6
        },
        "multa": {
            "id": 4,
            "emprestimo_id": 5,
            "usuario_id": 3,
            "dias_atraso": 6,
            "valor": "9.00",
            "valor_formatado": "R$ 9,00",
            "status": "pendente",
            "criada_em": "2026-11-09 09:57:28",
            "paga_em": null
        }
    }
}
```

### T09 - Devolução duplicada

- **Requisição:** `POST /api/emprestimos/5/devolucao`
- **Perfil:** Bibliotecário
- **Status esperado:** 409
- **Status obtido:** 409

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "EMPRESTIMO_JA_DEVOLVIDO",
        "mensagem": "Este empréstimo já foi devolvido."
    }
}
```

### T10 - Cenário D - material sem exemplares

- **Requisição:** `POST /api/emprestimos`
- **Perfil:** Bibliotecário
- **Status esperado:** 409
- **Status obtido:** 409

Corpo enviado:

```json
{
    "usuario_id": 1,
    "material_id": 4
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "MATERIAL_INDISPONIVEL",
        "mensagem": "O material não está disponível."
    }
}
```

### T11 - Cenário E - leitor tenta cadastrar material

- **Requisição:** `POST /api/materiais`
- **Perfil:** Leitor
- **Status esperado:** 403
- **Status obtido:** 403

Corpo enviado:

```json
{
    "tipo": "livro",
    "titulo": "Meu livro",
    "autor": "Ana Souza",
    "ano_publicacao": 2024,
    "quantidade_total": 1,
    "isbn": "9788500000001",
    "editora": "Teste",
    "paginas": 100
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "ACESSO_NEGADO",
        "mensagem": "Seu perfil não tem permissão para realizar esta operação."
    }
}
```

### T12 - Cenário F - relatório-resumo

- **Requisição:** `GET /api/relatorios/resumo`
- **Perfil:** Administrador
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "gerado_em": "2026-11-09 09:57:28",
        "total_materiais": 10,
        "materiais_disponiveis": 9,
        "materiais_indisponiveis": 1,
        "total_usuarios": 3,
        "emprestimos_ativos": 3,
        "emprestimos_atrasados": 1,
        "total_devolucoes": 5,
        "multas_pendentes": {
            "quantidade": 3,
            "valor_total": "14.00",
            "valor_total_formatado": "R$ 14,00"
        },
        "emprestimos_por_tipo": {
            "livro": 4,
            "revista": 1,
            "ebook": 3
        }
    }
}
```

### T13 - Listagem com filtro, ordenação e paginação

- **Requisição:** `GET /api/materiais?tipo=livro&disponivel=true&ordenar=titulo&direcao=desc&pagina=1&por_pagina=2`
- **Perfil:** Leitor
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": [
        {
            "id": 1,
            "tipo": "livro",
            "tipo_descricao": "Livro",
            "titulo": "PHP Orientado a Objetos",
            "autor": "Ricardo Almeida",
            "ano_publicacao": 2023,
            "quantidade_total": 3,
            "quantidade_disponivel": 2,
            "disponivel": true,
            "prazo_base_dias": 14,
            "detalhes": {
                "isbn": "978-85-7522-610-4",
                "editora": "Novatec",
                "paginas": 384
            }
        },
        {
            "id": 5,
            "tipo": "livro",
            "tipo_descricao": "Livro",
            "titulo": "Engenharia de Software na Prática",
            "autor": "Luciana Teixeira",
            "ano_publicacao": 2020,
            "quantidade_total": 2,
            "quantidade_disponivel": 2,
            "disponivel": true,
            "prazo_base_dias": 14,
            "detalhes": {
                "isbn": "978-65-00-01950-6",
                "editora": "Independente",
                "paginas": 408
            }
        }
    ],
    "paginacao": {
        "pagina": 1,
        "por_pagina": 2,
        "total_itens": 3,
        "total_paginas": 2
    }
}
```

### T14 - Cadastro de material válido

- **Requisição:** `POST /api/materiais`
- **Perfil:** Bibliotecário
- **Status esperado:** 201
- **Status obtido:** 201

Corpo enviado:

```json
{
    "tipo": "revista",
    "titulo": "Mundo Digital",
    "autor": "Editora Conecta",
    "ano_publicacao": 2026,
    "quantidade_total": 4,
    "edicao": 12,
    "issn": "1980-3311"
}
```

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 11,
        "tipo": "revista",
        "tipo_descricao": "Revista",
        "titulo": "Mundo Digital",
        "autor": "Editora Conecta",
        "ano_publicacao": 2026,
        "quantidade_total": 4,
        "quantidade_disponivel": 4,
        "disponivel": true,
        "prazo_base_dias": 7,
        "detalhes": {
            "edicao": 12,
            "issn": "1980-3311"
        }
    }
}
```

### T15 - Cadastro com dados inválidos

- **Requisição:** `POST /api/materiais`
- **Perfil:** Bibliotecário
- **Status esperado:** 422
- **Status obtido:** 422

Corpo enviado:

```json
{
    "tipo": "livro",
    "titulo": "A",
    "ano_publicacao": 3000,
    "quantidade_total": 0
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "DADOS_INVALIDOS",
        "mensagem": "Os dados enviados são inválidos.",
        "detalhes": {
            "titulo": "O título deve ter entre 2 e 200 caracteres.",
            "autor": "O autor deve ter entre 2 e 150 caracteres.",
            "ano_publicacao": "O ano de publicação deve estar entre 1450 e 2027.",
            "quantidade_total": "A quantidade total deve ser de pelo menos 1.",
            "isbn": "Informe um ISBN válido com 10 ou 13 dígitos.",
            "editora": "Informe a editora (2 a 120 caracteres).",
            "paginas": "O número de páginas deve ser maior que zero."
        }
    }
}
```

### T16 - Atualização por bibliotecário (sem permissão)

- **Requisição:** `PUT /api/materiais/11`
- **Perfil:** Bibliotecário
- **Status esperado:** 403
- **Status obtido:** 403

Corpo enviado:

```json
{
    "quantidade_total": 6
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "ACESSO_NEGADO",
        "mensagem": "Seu perfil não tem permissão para realizar esta operação."
    }
}
```

### T17 - Atualização pelo administrador

- **Requisição:** `PUT /api/materiais/11`
- **Perfil:** Administrador
- **Status esperado:** 200
- **Status obtido:** 200

Corpo enviado:

```json
{
    "quantidade_total": 6
}
```

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 11,
        "tipo": "revista",
        "tipo_descricao": "Revista",
        "titulo": "Mundo Digital",
        "autor": "Editora Conecta",
        "ano_publicacao": 2026,
        "quantidade_total": 6,
        "quantidade_disponivel": 6,
        "disponivel": true,
        "prazo_base_dias": 7,
        "detalhes": {
            "edicao": 12,
            "issn": "1980-3311"
        }
    }
}
```

### T18 - Listar usuários

- **Requisição:** `GET /api/usuarios`
- **Perfil:** Administrador
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": [
        {
            "id": 1,
            "nome": "Ana Souza",
            "email": "ana.souza@email.com",
            "documento": "ALU-2024001",
            "tipo": "aluno",
            "tipo_descricao": "Aluno",
            "bloqueado": false,
            "limite_emprestimos": 3,
            "dias_extras_prazo": 0,
            "multa_por_dia": "2.00"
        },
        {
            "id": 2,
            "nome": "Carlos Mendes",
            "email": "carlos.mendes@email.com",
            "documento": "PROF-1187",
            "tipo": "professor",
            "tipo_descricao": "Professor",
            "bloqueado": false,
            "limite_emprestimos": 5,
            "dias_extras_prazo": 7,
            "multa_por_dia": "1.00"
        },
        {
            "id": 3,
            "nome": "Marina Costa",
            "email": "marina.costa@email.com",
            "documento": "PESQ-0452",
            "tipo": "pesquisador",
            "tipo_descricao": "Pesquisador",
            "bloqueado": false,
            "limite_emprestimos": 7,
            "dias_extras_prazo": 5,
            "multa_por_dia": "1.50"
        }
    ]
}
```

### T19 - Cadastrar usuário com conta de leitor

- **Requisição:** `POST /api/usuarios`
- **Perfil:** Administrador
- **Status esperado:** 201
- **Status obtido:** 201

Corpo enviado:

```json
{
    "nome": "João Pereira",
    "email": "joao.pereira@email.com",
    "documento": "ALU-2024077",
    "tipo": "aluno",
    "senha": "Joao@2026"
}
```

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 4,
        "nome": "João Pereira",
        "email": "joao.pereira@email.com",
        "documento": "ALU-2024077",
        "tipo": "aluno",
        "tipo_descricao": "Aluno",
        "bloqueado": false,
        "limite_emprestimos": 3,
        "dias_extras_prazo": 0,
        "multa_por_dia": "2.00"
    }
}
```

### T20 - Cadastrar usuário com e-mail repetido

- **Requisição:** `POST /api/usuarios`
- **Perfil:** Administrador
- **Status esperado:** 409
- **Status obtido:** 409

Corpo enviado:

```json
{
    "nome": "João Pereira",
    "email": "joao.pereira@email.com",
    "documento": "ALU-2024078",
    "tipo": "aluno"
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "EMAIL_JA_CADASTRADO",
        "mensagem": "Já existe um usuário com este e-mail."
    }
}
```

### T21 - Leitor lista empréstimos (somente os próprios)

- **Requisição:** `GET /api/emprestimos`
- **Perfil:** Leitor
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": [
        {
            "id": 7,
            "usuario_id": 1,
            "material_id": 1,
            "data_emprestimo": "2026-11-09 09:57:28",
            "data_prevista": "2026-11-23",
            "data_devolucao": null,
            "status": "ativo",
            "atrasado": false,
            "dias_atraso": 0
        },
        {
            "id": 6,
            "usuario_id": 1,
            "material_id": 10,
            "data_emprestimo": "2026-10-25 09:57:27",
            "data_prevista": "2026-11-04",
            "data_devolucao": "2026-11-02 09:57:27",
            "status": "devolvido",
            "atrasado": false,
            "dias_atraso": 0
        },
        {
            "id": 2,
            "usuario_id": 1,
            "material_id": 5,
            "data_emprestimo": "2026-09-30 09:57:27",
            "data_prevista": "2026-10-14",
            "data_devolucao": "2026-10-16 09:57:27",
            "status": "devolvido",
            "atrasado": false,
            "dias_atraso": 2
        }
    ]
}
```

### T22 - Leitor consulta empréstimo de outra pessoa

- **Requisição:** `GET /api/emprestimos/1`
- **Perfil:** Leitor
- **Status esperado:** 403
- **Status obtido:** 403

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "ACESSO_NEGADO",
        "mensagem": "Você só pode consultar os seus próprios empréstimos."
    }
}
```

### T23 - Consultar empréstimo

- **Requisição:** `GET /api/emprestimos/1`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 1,
        "usuario_id": 2,
        "material_id": 4,
        "data_emprestimo": "2026-10-10 09:57:27",
        "data_prevista": "2026-10-31",
        "data_devolucao": null,
        "status": "ativo",
        "atrasado": true,
        "dias_atraso": 9
    }
}
```

### T24 - Empréstimos atrasados

- **Requisição:** `GET /api/emprestimos?status=atrasado`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": [
        {
            "id": 1,
            "usuario_id": 2,
            "material_id": 4,
            "data_emprestimo": "2026-10-10 09:57:27",
            "data_prevista": "2026-10-31",
            "data_devolucao": null,
            "status": "ativo",
            "atrasado": true,
            "dias_atraso": 9
        }
    ]
}
```

### T25 - Multas pendentes

- **Requisição:** `GET /api/multas?status=pendente`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": [
        {
            "id": 4,
            "emprestimo_id": 5,
            "usuario_id": 3,
            "dias_atraso": 6,
            "valor": "9.00",
            "valor_formatado": "R$ 9,00",
            "status": "pendente",
            "criada_em": "2026-11-09 09:57:28",
            "paga_em": null
        },
        {
            "id": 1,
            "emprestimo_id": 2,
            "usuario_id": 1,
            "dias_atraso": 2,
            "valor": "4.00",
            "valor_formatado": "R$ 4,00",
            "status": "pendente",
            "criada_em": "2026-10-16 09:57:27",
            "paga_em": null
        },
        {
            "id": 3,
            "emprestimo_id": 4,
            "usuario_id": 2,
            "dias_atraso": 1,
            "valor": "1.00",
            "valor_formatado": "R$ 1,00",
            "status": "pendente",
            "criada_em": "2026-10-08 09:57:27",
            "paga_em": null
        }
    ]
}
```

### T26 - Histórico de um usuário

- **Requisição:** `GET /api/historico?usuario_id=3&por_pagina=5`
- **Perfil:** Bibliotecário
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": [
        {
            "id": 17,
            "tipo": "multa",
            "descricao": "Multa de R$ 9,00 aplicada a Marina Costa por 6 dia(s) de atraso.",
            "usuario_id": 3,
            "material_id": 6,
            "emprestimo_id": 5,
            "conta_id": 2,
            "registrada_em": "2026-11-09 09:57:28"
        },
        {
            "id": 16,
            "tipo": "devolucao",
            "descricao": "Devolução de \"Arquitetura Limpa na Prática\" por Marina Costa com 6 dia(s) de atraso.",
            "usuario_id": 3,
            "material_id": 6,
            "emprestimo_id": 5,
            "conta_id": 2,
            "registrada_em": "2026-11-09 09:57:28"
        },
        {
            "id": 11,
            "tipo": "multa",
            "descricao": "Multa de R$ 4,50 aplicada a Marina Costa por 3 dia(s) de atraso.",
            "usuario_id": 3,
            "material_id": 7,
            "emprestimo_id": 3,
            "conta_id": 2,
            "registrada_em": "2026-10-20 09:57:27"
        },
        {
            "id": 10,
            "tipo": "devolucao",
            "descricao": "Devolução de \"Ciência & Dados\" por Marina Costa com 3 dia(s) de atraso.",
            "usuario_id": 3,
            "material_id": 7,
            "emprestimo_id": 3,
            "conta_id": 2,
            "registrada_em": "2026-10-20 09:57:27"
        },
        {
            "id": 7,
            "tipo": "emprestimo",
            "descricao": "Empréstimo de \"Arquitetura Limpa na Prática\" para Marina Costa.",
            "usuario_id": 3,
            "material_id": 6,
            "emprestimo_id": 5,
            "conta_id": 2,
            "registrada_em": "2026-10-15 09:57:27"
        }
    ],
    "paginacao": {
        "pagina": 1,
        "por_pagina": 5,
        "total_itens": 6,
        "total_paginas": 2
    }
}
```

### T27 - Empréstimos por tipo de material

- **Requisição:** `GET /api/relatorios/emprestimos-por-tipo`
- **Perfil:** Administrador
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "livro": 4,
        "revista": 1,
        "ebook": 3
    }
}
```

### T28 - Bloquear usuário

- **Requisição:** `POST /api/usuarios/2/bloqueio`
- **Perfil:** Administrador
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 2,
        "nome": "Carlos Mendes",
        "email": "carlos.mendes@email.com",
        "documento": "PROF-1187",
        "tipo": "professor",
        "tipo_descricao": "Professor",
        "bloqueado": true,
        "limite_emprestimos": 5,
        "dias_extras_prazo": 7,
        "multa_por_dia": "1.00"
    }
}
```

### T29 - Empréstimo para usuário bloqueado

- **Requisição:** `POST /api/emprestimos`
- **Perfil:** Bibliotecário
- **Status esperado:** 409
- **Status obtido:** 409

Corpo enviado:

```json
{
    "usuario_id": 2,
    "material_id": 1
}
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "USUARIO_BLOQUEADO",
        "mensagem": "O usuário está bloqueado e não pode realizar empréstimos."
    }
}
```

### T30 - Desbloquear usuário

- **Requisição:** `POST /api/usuarios/2/desbloqueio`
- **Perfil:** Administrador
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "id": 2,
        "nome": "Carlos Mendes",
        "email": "carlos.mendes@email.com",
        "documento": "PROF-1187",
        "tipo": "professor",
        "tipo_descricao": "Professor",
        "bloqueado": false,
        "limite_emprestimos": 5,
        "dias_extras_prazo": 7,
        "multa_por_dia": "1.00"
    }
}
```

### T31 - Material inexistente

- **Requisição:** `GET /api/materiais/999`
- **Perfil:** Leitor
- **Status esperado:** 404
- **Status obtido:** 404

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "MATERIAL_NAO_ENCONTRADO",
        "mensagem": "Material 999 não encontrado."
    }
}
```

### T32 - JSON malformado

- **Requisição:** `POST /api/emprestimos`
- **Perfil:** Bibliotecário
- **Status esperado:** 400
- **Status obtido:** 400

Corpo enviado:

```json
{"usuario_id": 1,
```

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "JSON_INVALIDO",
        "mensagem": "O corpo da requisição não é um JSON válido."
    }
}
```

### T33 - Bibliotecário tenta ver relatório

- **Requisição:** `GET /api/relatorios/resumo`
- **Perfil:** Bibliotecário
- **Status esperado:** 403
- **Status obtido:** 403

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "ACESSO_NEGADO",
        "mensagem": "Seu perfil não tem permissão para realizar esta operação."
    }
}
```

### T34 - Logout

- **Requisição:** `POST /api/auth/logout`
- **Perfil:** Leitor
- **Status esperado:** 200
- **Status obtido:** 200

Resposta:

```json
{
    "sucesso": true,
    "dados": {
        "mensagem": "Sessão encerrada. O token foi revogado."
    }
}
```

### T35 - Uso de token revogado

- **Requisição:** `GET /api/auth/me`
- **Perfil:** Leitor
- **Status esperado:** 401
- **Status obtido:** 401

Resposta:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "TOKEN_INVALIDO",
        "mensagem": "Token de acesso inválido, expirado ou revogado."
    }
}
```
