<?php

declare(strict_types=1);

namespace App;

use App\Controllers\AuthController;
use App\Controllers\EmprestimoController;
use App\Controllers\HistoricoController;
use App\Controllers\MaterialController;
use App\Controllers\MultaController;
use App\Controllers\RelatorioController;
use App\Controllers\UsuarioController;
use App\Domain\Enums\Permissao;
use App\Domain\Politicas\MultaComValorMaximo;
use App\Domain\Politicas\MultaPorDiaDeAtraso;
use App\Domain\Politicas\PoliticaMultaInterface;
use App\Domain\ValueObjects\Dinheiro;
use App\Exceptions\AppException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Middleware\AutenticacaoMiddleware;
use App\Middleware\AutorizacaoMiddleware;
use App\Repositories\Pdo\PdoContaAcessoRepository;
use App\Repositories\Pdo\PdoEmprestimoRepository;
use App\Repositories\Pdo\PdoHistoricoRepository;
use App\Repositories\Pdo\PdoMaterialRepository;
use App\Repositories\Pdo\PdoMultaRepository;
use App\Repositories\Pdo\PdoRelatorioRepository;
use App\Repositories\Pdo\PdoTokenAcessoRepository;
use App\Repositories\Pdo\PdoTransacao;
use App\Repositories\Pdo\PdoUsuarioRepository;
use App\Services\AuthService;
use App\Services\EmprestimoService;
use App\Services\HistoricoService;
use App\Services\MaterialService;
use App\Services\MultaService;
use App\Services\RelatorioService;
use App\Services\UsuarioService;
use App\Support\Conexao;
use App\Support\RelogioInterface;
use App\Support\RelogioSistema;
use PDO;
use Throwable;

final class Aplicacao
{
    private Router $router;
    private bool $debug;

    public function __construct(PDO $pdo, array $config = [], ?RelogioInterface $relogio = null)
    {
        $this->debug = (bool) ($config['debug'] ?? false);
        $relogio ??= new RelogioSistema();

        $transacao = new PdoTransacao($pdo);
        $usuarios = new PdoUsuarioRepository($pdo);
        $materiais = new PdoMaterialRepository($pdo);
        $emprestimos = new PdoEmprestimoRepository($pdo);
        $multas = new PdoMultaRepository($pdo);
        $historico = new PdoHistoricoRepository($pdo);
        $contas = new PdoContaAcessoRepository($pdo);

        $autenticacao = new AuthService(
            $contas,
            new PdoTokenAcessoRepository($pdo),
            $relogio,
            (int) ($config['token_validade_minutos'] ?? 120),
        );

        $emprestimoService = new EmprestimoService(
            $usuarios,
            $materiais,
            $emprestimos,
            $multas,
            $historico,
            $transacao,
            $this->politicaDeMulta($config),
            $relogio,
        );

        $this->router = new Router();
        $this->registrarRotas(
            new AutenticacaoMiddleware($autenticacao),
            new AuthController($autenticacao),
            new MaterialController(new MaterialService($materiais, $historico, $transacao, $relogio)),
            new UsuarioController(new UsuarioService($usuarios, $contas, $historico, $transacao, $relogio)),
            new EmprestimoController($emprestimoService, $relogio),
            new MultaController(new MultaService($multas)),
            new HistoricoController(new HistoricoService($historico)),
            new RelatorioController(new RelatorioService(new PdoRelatorioRepository($pdo), $relogio)),
        );
    }

    public static function criar(array $config): self
    {
        return new self(Conexao::mysql($config['banco']), $config);
    }

    public function processar(Request $request): Response
    {
        try {
            return $this->router->despachar($request);
        } catch (AppException $erro) {
            return Response::erro($erro->statusHttp(), $erro->codigoErro(), $erro->getMessage(), $erro->detalhes());
        } catch (Throwable $erro) {
            error_log((string) $erro);

            return Response::erro(
                500,
                'ERRO_INTERNO',
                $this->debug ? $erro->getMessage() : 'Ocorreu um erro interno. Tente novamente mais tarde.',
            );
        }
    }

    private function politicaDeMulta(array $config): PoliticaMultaInterface
    {
        $politica = new MultaPorDiaDeAtraso();
        $valorMaximo = trim((string) ($config['multa_valor_maximo'] ?? ''));

        if ($valorMaximo === '') {
            return $politica;
        }

        return new MultaComValorMaximo($politica, Dinheiro::deDecimal($valorMaximo));
    }

    private function registrarRotas(
        AutenticacaoMiddleware $autenticado,
        AuthController $auth,
        MaterialController $materiais,
        UsuarioController $usuarios,
        EmprestimoController $emprestimos,
        MultaController $multas,
        HistoricoController $historico,
        RelatorioController $relatorios,
    ): void {
        $exige = static fn (Permissao ...$permissoes): array => [$autenticado, new AutorizacaoMiddleware(...$permissoes)];
        $r = $this->router;

        $r->post('/api/auth/login', $auth->login(...));
        $r->post('/api/auth/logout', $auth->logout(...), [$autenticado]);
        $r->get('/api/auth/me', $auth->perfil(...), [$autenticado]);

        $r->get('/api/materiais', $materiais->listar(...), $exige(Permissao::CONSULTAR_MATERIAIS));
        $r->get('/api/materiais/{id}', $materiais->buscar(...), $exige(Permissao::CONSULTAR_MATERIAIS));
        $r->post('/api/materiais', $materiais->cadastrar(...), $exige(Permissao::CADASTRAR_MATERIAIS));
        $r->put('/api/materiais/{id}', $materiais->atualizar(...), $exige(Permissao::ATUALIZAR_MATERIAIS));

        $r->get('/api/usuarios', $usuarios->listar(...), $exige(Permissao::CONSULTAR_USUARIOS));
        $r->get('/api/usuarios/{id}', $usuarios->buscar(...), $exige(Permissao::CONSULTAR_USUARIOS));
        $r->post('/api/usuarios', $usuarios->cadastrar(...), $exige(Permissao::GERENCIAR_USUARIOS));
        $r->post('/api/usuarios/{id}/bloqueio', $usuarios->bloquear(...), $exige(Permissao::GERENCIAR_USUARIOS));
        $r->post('/api/usuarios/{id}/desbloqueio', $usuarios->desbloquear(...), $exige(Permissao::GERENCIAR_USUARIOS));

        $consultaEmprestimos = $exige(Permissao::CONSULTAR_EMPRESTIMOS, Permissao::CONSULTAR_PROPRIOS_EMPRESTIMOS);
        $r->get('/api/emprestimos', $emprestimos->listar(...), $consultaEmprestimos);
        $r->get('/api/emprestimos/{id}', $emprestimos->buscar(...), $consultaEmprestimos);
        $r->post('/api/emprestimos', $emprestimos->registrar(...), $exige(Permissao::REGISTRAR_EMPRESTIMOS));
        $r->post('/api/emprestimos/{id}/devolucao', $emprestimos->devolver(...), $exige(Permissao::REGISTRAR_DEVOLUCOES));

        $r->get('/api/multas', $multas->listar(...), $exige(Permissao::CONSULTAR_MULTAS, Permissao::CONSULTAR_PROPRIAS_MULTAS));
        $r->get('/api/historico', $historico->listar(...), $exige(Permissao::CONSULTAR_HISTORICO));

        $r->get('/api/relatorios/resumo', $relatorios->resumo(...), $exige(Permissao::CONSULTAR_RELATORIOS));
        $r->get('/api/relatorios/emprestimos-por-tipo', $relatorios->emprestimosPorTipo(...), $exige(Permissao::CONSULTAR_RELATORIOS));
    }
}
