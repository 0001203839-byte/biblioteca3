<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Enums\Perfil;
use App\Domain\Enums\Permissao;
use App\Domain\Enums\TipoMovimentacao;
use App\Domain\Factories\UsuarioFactory;
use App\Domain\Models\ContaAcesso;
use App\Domain\Models\Movimentacao;
use App\Domain\Models\Usuario;
use App\Exceptions\ConflitoException;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Repositories\Contracts\ContaAcessoRepositoryInterface;
use App\Repositories\Contracts\HistoricoRepositoryInterface;
use App\Repositories\Contracts\TransacaoInterface;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use App\Support\Entrada;
use App\Support\RelogioInterface;

final class UsuarioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly ContaAcessoRepositoryInterface $contas,
        private readonly HistoricoRepositoryInterface $historico,
        private readonly TransacaoInterface $transacao,
        private readonly RelogioInterface $relogio,
        private readonly UsuarioFactory $fabrica = new UsuarioFactory(),
    ) {
    }

    public function listar(array $parametros): array
    {
        $tipo = (new Entrada($parametros))->textoOuNulo('tipo');

        if ($tipo !== null && !in_array(mb_strtolower($tipo), $this->fabrica->tipos(), true)) {
            throw new ValidacaoException(
                ['tipo' => 'Use: ' . implode(', ', $this->fabrica->tipos()) . '.'],
                'Parâmetros de consulta inválidos.',
            );
        }

        return $this->usuarios->listar($tipo === null ? null : mb_strtolower($tipo));
    }

    public function buscar(int $id): Usuario
    {
        return $this->usuarios->buscarPorId($id) ?? throw RecursoNaoEncontradoException::usuario($id);
    }

    public function cadastrar(array $dados, ContaAcesso $operador): Usuario
    {
        $operador->exigirPermissao(Permissao::GERENCIAR_USUARIOS);

        $usuario = $this->fabrica->criar($dados);
        $senha = (new Entrada($dados))->texto('senha');

        if ($senha !== '' && ($erro = ContaAcesso::validarSenha($senha))) {
            throw new ValidacaoException(['senha' => $erro]);
        }

        return $this->transacao->executar(function () use ($usuario, $senha, $operador): Usuario {
            if ($this->usuarios->existeEmail($usuario->email())) {
                throw new ConflitoException('Já existe um usuário com este e-mail.', 'EMAIL_JA_CADASTRADO');
            }

            if ($this->usuarios->existeDocumento($usuario->documento())) {
                throw new ConflitoException('Já existe um usuário com este documento.', 'DOCUMENTO_JA_CADASTRADO');
            }

            $this->usuarios->salvar($usuario);

            if ($senha !== '') {
                if ($this->contas->buscarPorEmail($usuario->email()) !== null) {
                    throw new ConflitoException('Já existe uma conta de acesso com este e-mail.', 'EMAIL_JA_CADASTRADO');
                }

                $this->contas->salvar(
                    ContaAcesso::criar($usuario->nome(), $usuario->email(), $senha, Perfil::LEITOR, $usuario->id()),
                );
            }

            $this->registrar(TipoMovimentacao::CADASTRO_USUARIO, "Usuário {$usuario->nome()} cadastrado como {$usuario->descricaoTipo()}.", $usuario, $operador);

            return $usuario;
        });
    }

    public function bloquear(int $id, ContaAcesso $operador): Usuario
    {
        return $this->alterarBloqueio($id, true, $operador);
    }

    public function desbloquear(int $id, ContaAcesso $operador): Usuario
    {
        return $this->alterarBloqueio($id, false, $operador);
    }

    private function alterarBloqueio(int $id, bool $bloquear, ContaAcesso $operador): Usuario
    {
        $operador->exigirPermissao(Permissao::GERENCIAR_USUARIOS);

        return $this->transacao->executar(function () use ($id, $bloquear, $operador): Usuario {
            $usuario = $this->usuarios->buscarParaAtualizacao($id) ?? throw RecursoNaoEncontradoException::usuario($id);

            if ($bloquear) {
                $usuario->bloquear();
            } else {
                $usuario->desbloquear();
            }

            $this->usuarios->salvar($usuario);

            $this->registrar(
                $bloquear ? TipoMovimentacao::BLOQUEIO_USUARIO : TipoMovimentacao::DESBLOQUEIO_USUARIO,
                sprintf('Usuário %s %s.', $usuario->nome(), $bloquear ? 'bloqueado' : 'desbloqueado'),
                $usuario,
                $operador,
            );

            return $usuario;
        });
    }

    private function registrar(TipoMovimentacao $tipo, string $descricao, Usuario $usuario, ContaAcesso $operador): void
    {
        $this->historico->registrar(new Movimentacao(
            $tipo,
            $descricao,
            $this->relogio->agora(),
            usuarioId: $usuario->id(),
            contaId: $operador->id(),
        ));
    }
}
