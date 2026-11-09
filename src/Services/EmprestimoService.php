<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Enums\Permissao;
use App\Domain\Enums\TipoMovimentacao;
use App\Domain\Filtros\FiltroEmprestimos;
use App\Domain\Models\ContaAcesso;
use App\Domain\Models\Emprestimo;
use App\Domain\Models\Movimentacao;
use App\Domain\Models\Multa;
use App\Domain\Politicas\PoliticaMultaInterface;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\MaterialIndisponivelException;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Repositories\Contracts\EmprestimoRepositoryInterface;
use App\Repositories\Contracts\HistoricoRepositoryInterface;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Repositories\Contracts\MultaRepositoryInterface;
use App\Repositories\Contracts\TransacaoInterface;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use App\Support\RelogioInterface;

final class EmprestimoService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly MaterialRepositoryInterface $materiais,
        private readonly EmprestimoRepositoryInterface $emprestimos,
        private readonly MultaRepositoryInterface $multas,
        private readonly HistoricoRepositoryInterface $historico,
        private readonly TransacaoInterface $transacao,
        private readonly PoliticaMultaInterface $politicaMulta,
        private readonly RelogioInterface $relogio,
    ) {
    }

    public function emprestar(int $usuarioId, int $materialId, ContaAcesso $operador): Emprestimo
    {
        return $this->transacao->executar(function () use ($usuarioId, $materialId, $operador): Emprestimo {
            $operador->exigirPermissao(Permissao::REGISTRAR_EMPRESTIMOS);

            $usuario = $this->usuarios->buscarParaAtualizacao($usuarioId)
                ?? throw RecursoNaoEncontradoException::usuario($usuarioId);
            $usuario->garantirQueNaoEstaBloqueado();

            $material = $this->materiais->buscarParaAtualizacao($materialId)
                ?? throw RecursoNaoEncontradoException::material($materialId);

            if (!$material->estaDisponivel()) {
                throw new MaterialIndisponivelException();
            }

            $usuario->garantirLimiteDeEmprestimos($this->emprestimos->contarAtivosDoUsuario($usuarioId));

            $agora = $this->relogio->agora();
            $emprestimo = Emprestimo::registrar($usuario, $material, $agora);
            $this->emprestimos->salvar($emprestimo);

            $material->retirarExemplar();
            $this->materiais->atualizarDisponibilidade($material);

            $this->historico->registrar(new Movimentacao(
                TipoMovimentacao::EMPRESTIMO,
                sprintf(
                    'Empréstimo de "%s" para %s, com devolução prevista para %s.',
                    $material->titulo(),
                    $usuario->nome(),
                    $emprestimo->dataPrevista()->format('d/m/Y'),
                ),
                $agora,
                $usuario->id(),
                $material->id(),
                $emprestimo->id(),
                $operador->id(),
            ));

            return $emprestimo;
        });
    }

    public function devolver(int $emprestimoId, ContaAcesso $operador): ResultadoDevolucao
    {
        return $this->transacao->executar(function () use ($emprestimoId, $operador): ResultadoDevolucao {
            $operador->exigirPermissao(Permissao::REGISTRAR_DEVOLUCOES);

            $emprestimo = $this->emprestimos->buscarParaAtualizacao($emprestimoId)
                ?? throw RecursoNaoEncontradoException::emprestimo($emprestimoId);

            $agora = $this->relogio->agora();
            $emprestimo->registrarDevolucao($agora);

            $usuario = $this->usuarios->buscarPorId($emprestimo->usuarioId())
                ?? throw RecursoNaoEncontradoException::usuario($emprestimo->usuarioId());
            $material = $this->materiais->buscarParaAtualizacao($emprestimo->materialId())
                ?? throw RecursoNaoEncontradoException::material($emprestimo->materialId());

            $this->emprestimos->salvar($emprestimo);

            $material->receberExemplar();
            $this->materiais->atualizarDisponibilidade($material);

            $diasAtraso = $emprestimo->diasDeAtraso($agora);

            $this->historico->registrar(new Movimentacao(
                TipoMovimentacao::DEVOLUCAO,
                sprintf(
                    'Devolução de "%s" por %s %s.',
                    $material->titulo(),
                    $usuario->nome(),
                    $diasAtraso > 0 ? "com {$diasAtraso} dia(s) de atraso" : 'dentro do prazo',
                ),
                $agora,
                $usuario->id(),
                $material->id(),
                $emprestimo->id(),
                $operador->id(),
            ));

            $valor = $this->politicaMulta->calcular($usuario, $diasAtraso);

            if ($diasAtraso === 0 || $valor->ehZero()) {
                return new ResultadoDevolucao($emprestimo);
            }

            $multa = new Multa($emprestimo->id(), $usuario->id(), $diasAtraso, $valor, $agora);
            $this->multas->salvar($multa);

            $this->historico->registrar(new Movimentacao(
                TipoMovimentacao::MULTA,
                sprintf('Multa de %s aplicada a %s por %d dia(s) de atraso.', $valor->formatado(), $usuario->nome(), $diasAtraso),
                $agora,
                $usuario->id(),
                $material->id(),
                $emprestimo->id(),
                $operador->id(),
            ));

            return new ResultadoDevolucao($emprestimo, $multa);
        });
    }

    public function listar(array $parametros, ContaAcesso $solicitante): array
    {
        $filtro = FiltroEmprestimos::daQuery($parametros, $this->relogio->agora());

        if (!$solicitante->pode(Permissao::CONSULTAR_EMPRESTIMOS)) {
            $filtro = $filtro->restritoAoUsuario($this->usuarioDoLeitor($solicitante));
        }

        return $this->emprestimos->listar($filtro);
    }

    public function buscar(int $id, ContaAcesso $solicitante): Emprestimo
    {
        $emprestimo = $this->emprestimos->buscarPorId($id) ?? throw RecursoNaoEncontradoException::emprestimo($id);

        if (!$solicitante->pode(Permissao::CONSULTAR_EMPRESTIMOS)
            && $emprestimo->usuarioId() !== $this->usuarioDoLeitor($solicitante)) {
            throw new AcessoNegadoException('Você só pode consultar os seus próprios empréstimos.');
        }

        return $emprestimo;
    }

    private function usuarioDoLeitor(ContaAcesso $conta): int
    {
        if (!$conta->pode(Permissao::CONSULTAR_PROPRIOS_EMPRESTIMOS) || $conta->usuarioId() === null) {
            throw new AcessoNegadoException();
        }

        return $conta->usuarioId();
    }
}
