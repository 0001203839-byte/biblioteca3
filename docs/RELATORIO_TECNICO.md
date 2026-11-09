# Relatório técnico: arquitetura, POO e SOLID

## 1. Visão geral

A segunda versão do sistema deixou de guardar os dados em memória e passou a ser uma API REST em PHP 8.2+ com MySQL. O código foi reorganizado em camadas, cada uma com um papel:

| Camada | Pasta | O que faz |
|---|---|---|
| Domínio | `src/Domain` | Entidades, regras de negócio, enums, value objects, fábricas, filtros e políticas de multa. Não conhece banco nem HTTP |
| Repositórios | `src/Repositories` | Interfaces (`Contracts`) e implementações com PDO (`Pdo`) |
| Serviços | `src/Services` | Casos de uso: emprestar, devolver, cadastrar, autenticar, relatórios |
| Controllers | `src/Controllers` | Leem a requisição, chamam o serviço e montam a resposta JSON |
| Middleware | `src/Middleware` | Autenticação por token e autorização por permissão |
| HTTP | `src/Http` | `Request`, `Response` e `Router` |
| Exceções | `src/Exceptions` | Hierarquia de exceções com status HTTP e código de erro |
| Suporte | `src/Support` | Leitura do `.env`, conexão PDO, relógio, paginação |

Fluxo de uma requisição:

```
public/index.php -> Aplicacao -> Router -> AutenticacaoMiddleware -> AutorizacaoMiddleware
                 -> Controller -> Service -> Repository (PDO) -> MySQL
```

Se qualquer etapa lança uma `AppException`, a `Aplicacao` transforma a exceção na resposta padronizada `{ "sucesso": false, "erro": {...} }` com o status correto. Exceções não previstas viram 500 e são registradas com `error_log`, sem expor detalhes ao cliente (a não ser com `APP_DEBUG=true`).

Nenhum controller acessa o banco. A única classe que monta os objetos concretos é a `Aplicacao` (composition root); o resto do sistema recebe dependências pelo construtor.

## 2. Programação orientada a objetos

**Encapsulamento.** Os atributos das entidades são privados e só mudam por métodos com regra. A disponibilidade de um material, por exemplo, só é alterada por `Material::retirarExemplar()` e `Material::receberExemplar()`, que impedem valores negativos ou acima do total. Um empréstimo só muda de status por `Emprestimo::registrarDevolucao()`, que bloqueia a devolução duplicada.

**Herança e abstração.** `Material` é abstrata e define o que todo material tem (título, autor, ano, quantidades) e o que cada tipo precisa informar (`tipo()`, `prazoBaseEmDias()`, `detalhes()`, `validarDetalhes()`). `Livro`, `Revista` e `EBook` estendem a classe. O construtor de `Material` usa o método `validarDetalhes()` da subclasse, então os erros de campos comuns e específicos voltam juntos em uma única resposta 422. `Usuario` segue a mesma ideia com `Aluno`, `Professor` e `Pesquisador`. `AppException` e `PdoRepository` também são abstratas.

**Polimorfismo.** O serviço de empréstimos nunca pergunta qual é o tipo do usuário ou do material. O prazo é `material->prazoBaseEmDias() + usuario->diasExtrasPrazo()` e a multa vem de `usuario->multaPorDia()`. Cada subclasse responde do seu jeito. Foi assim que o `Pesquisador` entrou no sistema: foi criada a classe, registrada no mapa da `UsuarioFactory`, e nenhum serviço foi alterado.

**Composição.** `MultaComValorMaximo` recebe outra política de multa e limita o resultado (decorator). `Multa` é composta por um `Dinheiro`. Os serviços são compostos por repositórios, transação, relógio e política de multa recebidos no construtor.

**Interfaces.** Todos os repositórios, a transação (`TransacaoInterface`), o relógio (`RelogioInterface`), a política de multa (`PoliticaMultaInterface`) e os middlewares (`MiddlewareInterface`) são contratos. Isso é o que permite testar os serviços com implementações em memória.

**Value object e enums.** `Dinheiro` guarda valores em centavos (inteiro) para não ter erro de arredondamento com float; no banco o tipo é `DECIMAL(10,2)`. Perfis, permissões e status são `enum` do PHP 8.1, o que evita strings soltas pelo código.

## 3. Princípios SOLID no código

### SRP - Responsabilidade única

- `EmprestimoController` só converte HTTP em chamada de serviço e o resultado em JSON. A regra do empréstimo está em `EmprestimoService` e o SQL em `PdoEmprestimoRepository`.
- `AuthService` cuida de login, validação e revogação de token. Quem decide se o perfil pode acessar a rota é o `AutorizacaoMiddleware`, e quem sabe as permissões de cada perfil é o enum `Perfil`.
- `FiltroMateriais` só valida e normaliza os parâmetros de busca; o repositório só traduz o filtro em SQL.

### OCP - Aberto/fechado

- **Políticas de multa:** `EmprestimoService` depende de `PoliticaMultaInterface`. A política padrão é `MultaPorDiaDeAtraso`. Para limitar o valor da multa foi criada `MultaComValorMaximo`, que é ligada só pela configuração (`MULTA_VALOR_MAXIMO` no `.env`), sem mexer no serviço. Uma política nova (desconto para primeira ocorrência, carência de dias, etc.) é só mais uma classe que implementa a interface.
- **Novo tipo de usuário:** `Pesquisador` foi adicionado como subclasse de `Usuario`. Limite, prazo extra e multa ficam na própria classe, sem `if ($tipo === 'pesquisador')` espalhado.

### LSP - Substituição de Liskov

- Qualquer `Livro`, `Revista` ou `EBook` pode ser usado onde se espera `Material`: `Emprestimo::registrar()`, `MaterialRepositoryInterface::salvar()` e `MaterialFactory::atualizar()` trabalham com o tipo base. As subclasses não exigem pré-condições extras nem mudam o significado dos métodos herdados. O `EBook` interpreta `quantidade_total` como acessos simultâneos, mas mantém o mesmo contrato de retirar e devolver.
- Os repositórios em memória dos testes (`tests/Fakes`) substituem os repositórios PDO sem que os serviços percebam.

### ISP - Segregação de interfaces

- Em vez de um repositório genérico com dezenas de métodos, cada entidade tem uma interface pequena. `HistoricoRepositoryInterface` tem só `registrar` e `listar`; `TokenAcessoRepositoryInterface` tem só `buscarPorHash` e `salvar`.
- As consultas do relatório ficaram em `RelatorioRepositoryInterface`, separadas de `MaterialRepositoryInterface` e `EmprestimoRepositoryInterface`. Assim, quem só empresta não depende de métodos de relatório, e o `RelatorioService` não depende de métodos de escrita.
- `TransacaoInterface` e `RelogioInterface` têm um método cada.

### DIP - Inversão de dependência

- Os serviços recebem interfaces no construtor (`UsuarioRepositoryInterface`, `TransacaoInterface`, `PoliticaMultaInterface`, `RelogioInterface`...). Nenhum serviço faz `new PdoAlgumaCoisa` nem conhece o PDO.
- A `Aplicacao` é o único ponto que escolhe as implementações concretas. Nos testes, `EmprestimoServiceTest` monta o mesmo serviço com repositórios em memória e um relógio fixo, e `TransacaoTest` monta com repositórios PDO sobre SQLite em memória, sem mudar uma linha do serviço.

## 4. Persistência e integridade

- Todas as consultas usam `PDO::prepare` com parâmetros nomeados e `bindValue` com o tipo correto. `PDO::ATTR_EMULATE_PREPARES` fica desligado.
- Os únicos trechos de SQL montados dinamicamente são nomes de colunas e tabelas, sempre escolhidos de listas fixas no código (`COLUNAS_ORDENACAO`, `TABELAS_DETALHE`), nunca do cliente. Buscas com `LIKE` escapam `%` e `_`.
- Violações de unicidade e de chave estrangeira (SQLSTATE 23000) são convertidas em `ConflitoException` (409).
- O banco tem chaves primárias e estrangeiras, `UNIQUE` em e-mail, documento, ISBN, token e multa por empréstimo, e `CHECK` para quantidade disponível menor ou igual ao total, valor de multa não negativo, coerência entre status e data de devolução e conta de leitor sempre vinculada a um usuário.
- Valores monetários em `DECIMAL(10,2)`, datas em `DATE`/`DATETIME`.

## 5. Transações e concorrência

`EmprestimoService::emprestar()` executa dentro de `TransacaoInterface::executar()`, na ordem pedida:

1. Verifica a permissão do operador e se o usuário não está bloqueado.
2. Consulta o material com `SELECT ... FOR UPDATE`.
3. Valida o limite de empréstimos ativos do usuário.
4. Grava o empréstimo.
5. Atualiza a disponibilidade.
6. Registra o histórico.
7. Confirma (commit).

Se algo falhar, `PdoTransacao` faz rollback e repassa a exceção.

O bloqueio de linha (`FOR UPDATE`) é o que impede que duas requisições levem o último exemplar. A segunda requisição fica esperando a primeira terminar; quando consegue ler a linha, a disponibilidade já está em zero e ela recebe 409 `MATERIAL_INDISPONIVEL`. O usuário também é lido com `FOR UPDATE`, o que impede que duas requisições simultâneas do mesmo usuário passem do limite.

A devolução segue a mesma lógica: trava o empréstimo, impede devolução repetida, calcula atraso e multa, atualiza disponibilidade e status e grava o histórico na mesma transação.

## 6. Segurança

- Senhas com `password_hash()` (bcrypt) e conferidas com `password_verify()`. Se o algoritmo padrão do PHP mudar, o hash é refeito no próximo login (`password_needs_rehash`).
- Quando o e-mail não existe, o login ainda executa um `password_verify` contra um hash fixo, para o tempo de resposta não revelar quais e-mails estão cadastrados. A mensagem de erro é a mesma nos dois casos.
- O token é gerado com `random_bytes(32)` e só o hash SHA-256 fica no banco. Tem data de expiração (`TOKEN_VALIDADE_MINUTOS`) e pode ser revogado pelo logout.
- A autorização é feita em duas camadas: o middleware barra a rota pelo perfil e os serviços conferem de novo a permissão nas operações de escrita e filtram os dados do leitor.
- Credenciais do banco ficam no `.env`, que está no `.gitignore`. O repositório traz apenas o `.env.example`.

## 7. Testes

São 78 testes PHPUnit em `tests/`:

- `Unit/Domain`: criação e validação de materiais e usuários, limites por perfil, bloqueio, prazos, atraso, devolução duplicada, multas de cada tipo de usuário e política com teto.
- `Unit/Services`: cenários A a E, limites de empréstimo de aluno, professor e pesquisador, rollback quando o histórico falha, leitor vendo só os próprios empréstimos, login, expiração e revogação de token. Tudo com repositórios em memória (`tests/Fakes`), sem banco.
- `Unit/Middleware`: acesso sem token, token inventado, leitor e bibliotecário barrados, administrador liberado.
- `Integration`: transação real com PDO sobre SQLite em memória (rollback desfaz as alterações) e a API inteira chamada em processo (login, filtros, 401, 403, 404, 405, 409, 422, cenário F).

Os testes da API pela rede estão em `docs/EVIDENCIAS_TESTES_API.md` e na coleção do Postman.

## 8. Decisões e limitações

- Foi usado token opaco guardado no banco em vez de JWT porque ele permite revogar o acesso no logout sem lista negra.
- Não foi usado framework; o roteador e a injeção de dependências são simples e ficam em `src/Http/Router.php` e `src/Aplicacao.php`.
- Não há endpoint de pagamento de multa, porque a atividade não pede. A tabela já tem `status` e `paga_em` para isso.
- O teste de concorrência real (duas conexões disputando o último exemplar) depende do MySQL; os testes automatizados cobrem a regra e o rollback, e o bloqueio é garantido pelo `FOR UPDATE`.
