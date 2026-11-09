# Documentação da API

URL base local: `http://localhost:8000`

Todas as requisições e respostas usam JSON (`Content-Type: application/json`). Com exceção do login, todas as rotas exigem o cabeçalho:

```
Authorization: Bearer <token>
```

## Formato das respostas

Sucesso:

```json
{
    "sucesso": true,
    "dados": { }
}
```

Listagens paginadas (materiais e histórico) trazem também:

```json
"paginacao": { "pagina": 1, "por_pagina": 10, "total_itens": 10, "total_paginas": 1 }
```

Erro:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "MATERIAL_INDISPONIVEL",
        "mensagem": "O material não está disponível."
    }
}
```

Erros de validação (422) trazem `erro.detalhes` com a mensagem de cada campo.

## Códigos HTTP usados

| Status | Quando acontece |
|---|---|
| 200 | Consulta ou operação concluída |
| 201 | Registro criado (material, usuário, empréstimo) |
| 400 | JSON malformado ou corpo que não é objeto |
| 401 | Token ausente, inválido, expirado ou revogado; credenciais inválidas |
| 403 | Perfil sem permissão para a operação |
| 404 | Recurso ou rota inexistente |
| 405 | Método HTTP não aceito na rota |
| 409 | Conflito de regra de negócio (material indisponível, limite atingido, devolução repetida, usuário bloqueado, e-mail duplicado) |
| 422 | Dados inválidos |
| 500 | Erro interno |

## Códigos de erro

| Código | Status |
|---|---|
| `JSON_INVALIDO` | 400 |
| `TOKEN_AUSENTE`, `TOKEN_INVALIDO`, `CREDENCIAIS_INVALIDAS` | 401 |
| `ACESSO_NEGADO` | 403 |
| `MATERIAL_NAO_ENCONTRADO`, `USUARIO_NAO_ENCONTRADO`, `EMPRESTIMO_NAO_ENCONTRADO`, `ROTA_NAO_ENCONTRADA` | 404 |
| `METODO_NAO_PERMITIDO` | 405 |
| `MATERIAL_INDISPONIVEL`, `LIMITE_EMPRESTIMOS_ATINGIDO`, `EMPRESTIMO_JA_DEVOLVIDO`, `USUARIO_BLOQUEADO`, `EMAIL_JA_CADASTRADO`, `DOCUMENTO_JA_CADASTRADO`, `REGISTRO_DUPLICADO` | 409 |
| `DADOS_INVALIDOS` | 422 |
| `ERRO_INTERNO` | 500 |

## Permissões por perfil

| Rota | Administrador | Bibliotecário | Leitor |
|---|:-:|:-:|:-:|
| `GET /api/materiais`, `GET /api/materiais/{id}` | sim | sim | sim |
| `POST /api/materiais` | sim | sim | não |
| `PUT /api/materiais/{id}` | sim | não | não |
| `GET /api/usuarios`, `GET /api/usuarios/{id}` | sim | sim | não |
| `POST /api/usuarios`, bloqueio e desbloqueio | sim | não | não |
| `GET /api/emprestimos`, `GET /api/emprestimos/{id}` | todos | todos | só os próprios |
| `POST /api/emprestimos`, `POST /api/emprestimos/{id}/devolucao` | sim | sim | não |
| `GET /api/multas` | todas | todas | só as próprias |
| `GET /api/historico` | sim | sim | não |
| `GET /api/relatorios/*` | sim | não | não |

---

## Autenticação

### POST /api/auth/login

Gera um token de acesso. Não exige autenticação.

Corpo:

```json
{ "email": "admin@biblioteca.local", "senha": "Admin@123" }
```

Sucesso (200):

```json
{
    "sucesso": true,
    "dados": {
        "token": "64f2b053a15eaf707b02557a41540847a18ba166206ad8ad3eb6e018ded55497",
        "tipo": "Bearer",
        "expira_em": "2026-11-09 11:56:46",
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

Erro (401):

```json
{ "sucesso": false, "erro": { "codigo": "CREDENCIAIS_INVALIDAS", "mensagem": "E-mail ou senha inválidos." } }
```

O token vale pelo tempo definido em `TOKEN_VALIDADE_MINUTOS` (padrão 120 minutos). No banco fica gravado apenas o hash SHA-256 do token.

### POST /api/auth/logout

Revoga o token enviado no cabeçalho. Exige autenticação. Depois do logout, o mesmo token passa a receber 401.

```json
{ "sucesso": true, "dados": { "mensagem": "Sessão encerrada. O token foi revogado." } }
```

### GET /api/auth/me

Retorna a conta autenticada e a lista de permissões do perfil.

---

## Materiais

### GET /api/materiais

Lista materiais com filtros, paginação e ordenação. Qualquer perfil autenticado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `titulo` | texto | Busca parcial no título |
| `autor` | texto | Busca parcial no autor |
| `tipo` | `livro`, `revista`, `ebook` | Filtra pelo tipo |
| `disponivel` | `true`, `false` | `true` = com exemplares livres; `false` = indisponíveis |
| `ordenar` | `id`, `titulo`, `autor`, `ano`, `disponibilidade` | Padrão `titulo` |
| `direcao` | `asc`, `desc` | Padrão `asc` |
| `pagina` | inteiro | Padrão 1 |
| `por_pagina` | inteiro (1 a 100) | Padrão 10 |

Exemplo: `GET /api/materiais?tipo=livro&disponivel=true&ordenar=titulo&direcao=desc&pagina=1&por_pagina=2`

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
            "detalhes": { "isbn": "978-85-7522-610-4", "editora": "Novatec", "paginas": 384 }
        }
    ],
    "paginacao": { "pagina": 1, "por_pagina": 2, "total_itens": 3, "total_paginas": 2 }
}
```

Erro (422) com parâmetro inválido, por exemplo `?disponivel=talvez`:

```json
{
    "sucesso": false,
    "erro": {
        "codigo": "DADOS_INVALIDOS",
        "mensagem": "Parâmetros de consulta inválidos.",
        "detalhes": { "disponivel": "Use true ou false." }
    }
}
```

### GET /api/materiais/{id}

Consulta um material. E-books trazem ainda `acessos_simultaneos` e `acessos_em_uso`.

Erro (404): `MATERIAL_NAO_ENCONTRADO`.

### POST /api/materiais

Cadastra um material. Administrador ou bibliotecário.

Campos comuns (obrigatórios): `tipo`, `titulo`, `autor`, `ano_publicacao`, `quantidade_total`.

| Tipo | Campos próprios |
|---|---|
| `livro` | `isbn` (10 ou 13 dígitos), `editora`, `paginas` |
| `revista` | `edicao`, `issn` (opcional, formato `0000-0000`) |
| `ebook` | `formato` (`pdf`, `epub`, `mobi`), `url_acesso` (opcional). Em e-books, `quantidade_total` é o número de acessos simultâneos |

Corpo:

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

Sucesso (201): o material criado, no mesmo formato da consulta.

Erros: 403 (`ACESSO_NEGADO`, ex.: leitor), 422 (`DADOS_INVALIDOS`), 409 (`REGISTRO_DUPLICADO`, ex.: ISBN repetido).

### PUT /api/materiais/{id}

Atualiza um material. Somente administrador. Os campos são os mesmos do cadastro e podem ser enviados parcialmente. O tipo não pode ser trocado. Ao mudar `quantidade_total`, a disponibilidade é recalculada mantendo os exemplares que estão emprestados; o total não pode ficar menor que a quantidade emprestada.

```json
{ "quantidade_total": 6 }
```

Sucesso (200): material atualizado. Erros: 403, 404, 422.

---

## Usuários

### GET /api/usuarios

Lista os usuários da biblioteca. Filtro opcional `?tipo=aluno|professor|pesquisador`. Administrador ou bibliotecário.

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
        }
    ]
}
```

### GET /api/usuarios/{id}

Consulta um usuário. Erro 404: `USUARIO_NAO_ENCONTRADO`.

### POST /api/usuarios

Cadastra um usuário. Somente administrador. Se `senha` for informada, também é criada uma conta de acesso com perfil leitor usando o mesmo e-mail.

```json
{
    "nome": "João Pereira",
    "email": "joao.pereira@email.com",
    "documento": "ALU-2024077",
    "tipo": "aluno",
    "senha": "Joao@2026"
}
```

Sucesso (201): usuário criado. Erros: 403, 409 (`EMAIL_JA_CADASTRADO`, `DOCUMENTO_JA_CADASTRADO`), 422.

### POST /api/usuarios/{id}/bloqueio e POST /api/usuarios/{id}/desbloqueio

Bloqueia ou libera um usuário. Somente administrador. Usuário bloqueado não consegue novos empréstimos (409 `USUARIO_BLOQUEADO`). Sem corpo.

---

## Empréstimos

### GET /api/emprestimos

Lista empréstimos. Para o leitor, a lista vem sempre filtrada pelos próprios empréstimos.

| Parâmetro | Descrição |
|---|---|
| `status` | `ativo`, `atrasado` ou `devolvido` |
| `usuario_id` | Histórico de empréstimos de um usuário |
| `material_id` | Empréstimos de um material |

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

### GET /api/emprestimos/{id}

Consulta um empréstimo. Leitor que tenta abrir empréstimo de outra pessoa recebe 403. Inexistente: 404.

### POST /api/emprestimos

Registra um empréstimo. Administrador ou bibliotecário.

```json
{ "usuario_id": 1, "material_id": 1 }
```

A operação roda em uma transação, nesta ordem: verifica a permissão de quem opera e se o usuário não está bloqueado, trava e consulta o material, confere o limite do usuário, grava o empréstimo, atualiza a disponibilidade e registra o histórico. Qualquer falha desfaz tudo.

Prazo de devolução = prazo base do material (livro 14, revista 7, e-book 10 dias) + dias extras do usuário (aluno 0, professor 7, pesquisador 5).

Sucesso (201):

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

Erros: 404 (usuário ou material), 409 (`MATERIAL_INDISPONIVEL`, `LIMITE_EMPRESTIMOS_ATINGIDO`, `USUARIO_BLOQUEADO`), 422 (ids ausentes).

### POST /api/emprestimos/{id}/devolucao

Registra a devolução. Administrador ou bibliotecário. Sem corpo. Se houver atraso, a multa é calculada pela política configurada e gravada na mesma transação.

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

Sem atraso, `multa` vem `null`. Devolver de novo o mesmo empréstimo retorna 409 `EMPRESTIMO_JA_DEVOLVIDO`.

---

## Multas

### GET /api/multas

Lista multas. Filtros: `status=pendente|paga` e `usuario_id`. Leitor vê apenas as próprias.

---

## Histórico

### GET /api/historico

Movimentações registradas, da mais recente para a mais antiga. Administrador ou bibliotecário.

| Parâmetro | Descrição |
|---|---|
| `usuario_id` | Movimentações de um usuário |
| `material_id` | Movimentações de um material |
| `tipo` | `emprestimo`, `devolucao`, `multa`, `cadastro_material`, `atualizacao_material`, `cadastro_usuario`, `bloqueio_usuario`, `desbloqueio_usuario` |
| `pagina`, `por_pagina` | Paginação (padrão 20 por página) |

---

## Relatórios

### GET /api/relatorios/resumo

Somente administrador.

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
        "emprestimos_por_tipo": { "livro": 4, "revista": 1, "ebook": 3 }
    }
}
```

Bibliotecário ou leitor recebem 403.

### GET /api/relatorios/emprestimos-por-tipo

Total de empréstimos (de todos os tempos) agrupado por tipo de material. Somente administrador.

```json
{ "sucesso": true, "dados": { "livro": 4, "revista": 1, "ebook": 3 } }
```
