# Diagrama de classes

O diagrama está em Mermaid (abre direto no GitHub, no VS Code com a extensão Markdown Preview Mermaid ou em https://mermaid.live). Foi dividido em três partes para ficar legível. As mesmas imagens, já geradas, estão em `docs/img`:

- `img/uml-dominio.png`
- `img/uml-repositorios-servicos.png`
- `img/uml-http.png`
- `img/modelo-banco.png`

## 1. Domínio

```mermaid
classDiagram
    direction TB

    class Material {
        <<abstract>>
        -int id
        -string titulo
        -string autor
        -int anoPublicacao
        -int quantidadeTotal
        -int quantidadeDisponivel
        +deEntrada(Entrada, int, int)$ Material
        +tipo()* string
        +descricaoTipo()* string
        +prazoBaseEmDias()* int
        +detalhes()* array
        #validarDetalhes()* array
        +estaDisponivel() bool
        +retirarExemplar() void
        +receberExemplar() void
        +quantidadeEmprestada() int
        +toArray() array
    }
    class Livro {
        -string isbn
        -string editora
        -int paginas
        +prazoBaseEmDias() int
    }
    class Revista {
        -int edicao
        -string issn
        +prazoBaseEmDias() int
    }
    class EBook {
        -string formato
        -string urlAcesso
        +prazoBaseEmDias() int
    }
    Material <|-- Livro
    Material <|-- Revista
    Material <|-- EBook
    note for Livro "prazo base de 14 dias"
    note for Revista "prazo base de 7 dias"
    note for EBook "quantidade = acessos simultâneos, prazo de 10 dias"

    class Usuario {
        <<abstract>>
        -int id
        -string nome
        -string email
        -string documento
        -bool bloqueado
        +tipo()* string
        +limiteEmprestimos()* int
        +diasExtrasPrazo()* int
        +multaPorDia()* Dinheiro
        +garantirQueNaoEstaBloqueado() void
        +garantirLimiteDeEmprestimos(int) void
        +bloquear() void
        +desbloquear() void
    }
    class Aluno {
        +limiteEmprestimos() int
        +diasExtrasPrazo() int
        +multaPorDia() Dinheiro
    }
    class Professor {
        +limiteEmprestimos() int
        +diasExtrasPrazo() int
        +multaPorDia() Dinheiro
    }
    class Pesquisador {
        +limiteEmprestimos() int
        +diasExtrasPrazo() int
        +multaPorDia() Dinheiro
    }
    Usuario <|-- Aluno
    Usuario <|-- Professor
    Usuario <|-- Pesquisador
    note for Aluno "limite 3, sem prazo extra, multa R$ 2,00/dia"
    note for Professor "limite 5, +7 dias, multa R$ 1,00/dia"
    note for Pesquisador "limite 7, +5 dias, multa R$ 1,50/dia"

    class Emprestimo {
        -int id
        -int usuarioId
        -int materialId
        -DateTimeImmutable dataEmprestimo
        -DateTimeImmutable dataPrevista
        -DateTimeImmutable dataDevolucao
        -StatusEmprestimo status
        +registrar(Usuario, Material, DateTimeImmutable)$ Emprestimo
        +registrarDevolucao(DateTimeImmutable) void
        +diasDeAtraso(DateTimeImmutable) int
        +estaAtrasado(DateTimeImmutable) bool
    }
    class Multa {
        -int emprestimoId
        -int usuarioId
        -int diasAtraso
        -Dinheiro valor
        -StatusMulta status
    }
    class Movimentacao {
        -TipoMovimentacao tipo
        -string descricao
        -DateTimeImmutable registradaEm
    }
    class ContaAcesso {
        -string email
        -string senhaHash
        -Perfil perfil
        -int usuarioId
        +criar(...)$ ContaAcesso
        +senhaConfere(string) bool
        +pode(Permissao) bool
        +exigirPermissao(Permissao) void
    }
    class TokenAcesso {
        -int contaId
        -string hash
        -DateTimeImmutable expiraEm
        -DateTimeImmutable revogadoEm
        +estaValido(DateTimeImmutable) bool
        +revogar(DateTimeImmutable) void
    }
    class Dinheiro {
        <<value object>>
        -int centavos
        +multiplicar(int) Dinheiro
        +emDecimal() string
    }
    class Perfil {
        <<enumeration>>
        ADMINISTRADOR
        BIBLIOTECARIO
        LEITOR
        +permissoes() Permissao[]
    }
    class Permissao {
        <<enumeration>>
    }

    class PoliticaMultaInterface {
        <<interface>>
        +calcular(Usuario, int) Dinheiro
    }
    class MultaPorDiaDeAtraso
    class MultaComValorMaximo {
        -PoliticaMultaInterface politicaBase
        -Dinheiro valorMaximo
    }
    PoliticaMultaInterface <|.. MultaPorDiaDeAtraso
    PoliticaMultaInterface <|.. MultaComValorMaximo
    MultaComValorMaximo o-- PoliticaMultaInterface : decora

    class MaterialFactory {
        +criar(array) Material
        +reconstituir(array) Material
        +atualizar(Material, array) Material
    }
    class UsuarioFactory {
        +criar(array) Usuario
        +montar(...) Usuario
    }

    Usuario "1" -- "0..*" Emprestimo : realiza
    Material "1" -- "0..*" Emprestimo : é emprestado em
    Emprestimo "1" -- "0..1" Multa : gera
    Usuario "1" -- "0..1" ContaAcesso : acessa por
    ContaAcesso "1" -- "0..*" TokenAcesso : possui
    ContaAcesso --> Perfil
    Perfil --> Permissao
    Multa *-- Dinheiro
    Usuario ..> Dinheiro
    MaterialFactory ..> Material : cria
    UsuarioFactory ..> Usuario : cria
    MultaPorDiaDeAtraso ..> Usuario : usa multaPorDia()
```

## 2. Repositórios e serviços

```mermaid
classDiagram
    direction LR

    class MaterialRepositoryInterface {
        <<interface>>
        +buscarPorId(int) Material
        +buscarParaAtualizacao(int) Material
        +listar(FiltroMateriais) ResultadoPaginado
        +salvar(Material) void
        +atualizarDisponibilidade(Material) void
    }
    class UsuarioRepositoryInterface {
        <<interface>>
        +buscarPorId(int) Usuario
        +buscarParaAtualizacao(int) Usuario
        +listar(string) array
        +existeEmail(string) bool
        +existeDocumento(string) bool
        +salvar(Usuario) void
    }
    class EmprestimoRepositoryInterface {
        <<interface>>
        +buscarPorId(int) Emprestimo
        +buscarParaAtualizacao(int) Emprestimo
        +listar(FiltroEmprestimos) array
        +contarAtivosDoUsuario(int) int
        +salvar(Emprestimo) void
    }
    class MultaRepositoryInterface {
        <<interface>>
    }
    class HistoricoRepositoryInterface {
        <<interface>>
    }
    class ContaAcessoRepositoryInterface {
        <<interface>>
    }
    class TokenAcessoRepositoryInterface {
        <<interface>>
    }
    class RelatorioRepositoryInterface {
        <<interface>>
    }
    class TransacaoInterface {
        <<interface>>
        +executar(callable) mixed
    }
    class RelogioInterface {
        <<interface>>
        +agora() DateTimeImmutable
    }

    class PdoRepository {
        <<abstract>>
        #PDO pdo
        #executar(string, array) PDOStatement
        #travaDeLinha() string
    }
    PdoRepository <|-- PdoMaterialRepository
    PdoRepository <|-- PdoUsuarioRepository
    PdoRepository <|-- PdoEmprestimoRepository
    PdoRepository <|-- PdoMultaRepository
    PdoRepository <|-- PdoHistoricoRepository
    PdoRepository <|-- PdoContaAcessoRepository
    PdoRepository <|-- PdoTokenAcessoRepository
    PdoRepository <|-- PdoRelatorioRepository

    MaterialRepositoryInterface <|.. PdoMaterialRepository
    UsuarioRepositoryInterface <|.. PdoUsuarioRepository
    EmprestimoRepositoryInterface <|.. PdoEmprestimoRepository
    MultaRepositoryInterface <|.. PdoMultaRepository
    HistoricoRepositoryInterface <|.. PdoHistoricoRepository
    ContaAcessoRepositoryInterface <|.. PdoContaAcessoRepository
    TokenAcessoRepositoryInterface <|.. PdoTokenAcessoRepository
    RelatorioRepositoryInterface <|.. PdoRelatorioRepository
    TransacaoInterface <|.. PdoTransacao
    RelogioInterface <|.. RelogioSistema

    class EmprestimoService {
        +emprestar(int, int, ContaAcesso) Emprestimo
        +devolver(int, ContaAcesso) ResultadoDevolucao
        +listar(array, ContaAcesso) array
        +buscar(int, ContaAcesso) Emprestimo
    }
    class MaterialService {
        +listar(array) ResultadoPaginado
        +buscar(int) Material
        +cadastrar(array, ContaAcesso) Material
        +atualizar(int, array, ContaAcesso) Material
    }
    class UsuarioService {
        +listar(array) array
        +cadastrar(array, ContaAcesso) Usuario
        +bloquear(int, ContaAcesso) Usuario
        +desbloquear(int, ContaAcesso) Usuario
    }
    class AuthService {
        +login(string, string) array
        +autenticar(string) ContaAcesso
        +logout(string) void
    }
    class MultaService
    class HistoricoService
    class RelatorioService

    EmprestimoService --> UsuarioRepositoryInterface
    EmprestimoService --> MaterialRepositoryInterface
    EmprestimoService --> EmprestimoRepositoryInterface
    EmprestimoService --> MultaRepositoryInterface
    EmprestimoService --> HistoricoRepositoryInterface
    EmprestimoService --> TransacaoInterface
    EmprestimoService --> PoliticaMultaInterface
    EmprestimoService --> RelogioInterface
    MaterialService --> MaterialRepositoryInterface
    MaterialService --> HistoricoRepositoryInterface
    MaterialService --> TransacaoInterface
    UsuarioService --> UsuarioRepositoryInterface
    UsuarioService --> ContaAcessoRepositoryInterface
    UsuarioService --> HistoricoRepositoryInterface
    AuthService --> ContaAcessoRepositoryInterface
    AuthService --> TokenAcessoRepositoryInterface
    MultaService --> MultaRepositoryInterface
    HistoricoService --> HistoricoRepositoryInterface
    RelatorioService --> RelatorioRepositoryInterface

    class PoliticaMultaInterface {
        <<interface>>
    }
```

## 3. Camada HTTP

```mermaid
classDiagram
    direction LR

    class Aplicacao {
        -Router router
        +criar(array)$ Aplicacao
        +processar(Request) Response
    }
    class Router {
        +get(string, callable, array) void
        +post(string, callable, array) void
        +put(string, callable, array) void
        +despachar(Request) Response
    }
    class Request {
        +capturar()$ Request
        +corpo() array
        +query() array
        +tokenBearer() string
        +conta() ContaAcesso
    }
    class Response {
        +sucesso(mixed, int, array)$ Response
        +criado(mixed)$ Response
        +erro(int, string, string, array)$ Response
        +enviar() void
    }
    class MiddlewareInterface {
        <<interface>>
        +processar(Request, callable) Response
    }
    class AutenticacaoMiddleware
    class AutorizacaoMiddleware {
        -Permissao[] permissoesAceitas
    }
    MiddlewareInterface <|.. AutenticacaoMiddleware
    MiddlewareInterface <|.. AutorizacaoMiddleware
    AutenticacaoMiddleware --> AuthService

    class AppException {
        <<abstract>>
        +statusHttp()* int
        +codigoErro() string
        +detalhes() array
    }
    AppException <|-- RequisicaoInvalidaException
    AppException <|-- NaoAutenticadoException
    NaoAutenticadoException <|-- CredenciaisInvalidasException
    AppException <|-- AcessoNegadoException
    AppException <|-- RecursoNaoEncontradoException
    AppException <|-- MetodoNaoPermitidoException
    AppException <|-- ConflitoException
    AppException <|-- ValidacaoException
    ConflitoException <|-- MaterialIndisponivelException
    ConflitoException <|-- LimiteEmprestimosException
    ConflitoException <|-- DevolucaoDuplicadaException
    ConflitoException <|-- UsuarioBloqueadoException

    Aplicacao *-- Router
    Aplicacao ..> AppException : converte em JSON
    Router o-- MiddlewareInterface
    Router ..> Request
    Router ..> Response

    MaterialController --> MaterialService
    UsuarioController --> UsuarioService
    EmprestimoController --> EmprestimoService
    MultaController --> MultaService
    HistoricoController --> HistoricoService
    RelatorioController --> RelatorioService
    AuthController --> AuthService
    Router --> MaterialController
    Router --> UsuarioController
    Router --> EmprestimoController
    Router --> MultaController
    Router --> HistoricoController
    Router --> RelatorioController
    Router --> AuthController
```

## Modelo do banco

```mermaid
erDiagram
    usuarios ||--o{ emprestimos : faz
    materiais ||--o{ emprestimos : "é emprestado"
    materiais ||--o| livros : especializa
    materiais ||--o| revistas : especializa
    materiais ||--o| ebooks : especializa
    emprestimos ||--o| multas : gera
    usuarios ||--o{ multas : deve
    usuarios |o--o| contas_acesso : "tem login"
    contas_acesso ||--o{ tokens_acesso : emite
    usuarios |o--o{ historico_movimentacoes : registra
    materiais |o--o{ historico_movimentacoes : registra
    emprestimos |o--o{ historico_movimentacoes : registra
    contas_acesso |o--o{ historico_movimentacoes : "operou"
```
