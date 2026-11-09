<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\ContaAcesso;
use App\Domain\Models\TokenAcesso;
use App\Exceptions\CredenciaisInvalidasException;
use App\Exceptions\NaoAutenticadoException;
use App\Exceptions\ValidacaoException;
use App\Repositories\Contracts\ContaAcessoRepositoryInterface;
use App\Repositories\Contracts\TokenAcessoRepositoryInterface;
use App\Support\RelogioInterface;

final class AuthService
{
    private const HASH_DE_REFERENCIA = '$2y$10$olarPD2gOSEX.n8mnsCrkOXAfNrMRbF1lnQ3vgaMw4qE0bTH2tWOW';

    public function __construct(
        private readonly ContaAcessoRepositoryInterface $contas,
        private readonly TokenAcessoRepositoryInterface $tokens,
        private readonly RelogioInterface $relogio,
        private readonly int $validadeEmMinutos = 120,
    ) {
    }

    public function login(string $email, string $senha): array
    {
        $erros = [];

        if (trim($email) === '') {
            $erros['email'] = 'Informe o e-mail.';
        }

        if ($senha === '') {
            $erros['senha'] = 'Informe a senha.';
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        $conta = $this->contas->buscarPorEmail($email);

        if ($conta === null) {
            password_verify($senha, self::HASH_DE_REFERENCIA);
            throw new CredenciaisInvalidasException();
        }

        if (!$conta->senhaConfere($senha) || !$conta->estaAtiva()) {
            throw new CredenciaisInvalidasException();
        }

        if ($conta->precisaAtualizarHash()) {
            $conta->regerarHash($senha);
            $this->contas->salvar($conta);
        }

        $agora = $this->relogio->agora();
        $valor = TokenAcesso::gerarValorAleatorio();
        $token = new TokenAcesso(
            $conta->id(),
            TokenAcesso::hashDe($valor),
            $agora,
            $agora->modify("+{$this->validadeEmMinutos} minutes"),
        );

        $this->tokens->salvar($token);

        return [
            'token' => $valor,
            'tipo' => 'Bearer',
            'expira_em' => $token->expiraEm()->format('Y-m-d H:i:s'),
            'conta' => $conta->toArray(),
        ];
    }

    public function autenticar(string $valorToken): ContaAcesso
    {
        $token = $this->tokens->buscarPorHash(TokenAcesso::hashDe($valorToken));

        if ($token === null || !$token->estaValido($this->relogio->agora())) {
            throw new NaoAutenticadoException('Token de acesso inválido, expirado ou revogado.', 'TOKEN_INVALIDO');
        }

        $conta = $this->contas->buscarPorId($token->contaId());

        if ($conta === null || !$conta->estaAtiva()) {
            throw new NaoAutenticadoException('A conta vinculada a este token não está ativa.', 'TOKEN_INVALIDO');
        }

        return $conta;
    }

    public function logout(string $valorToken): void
    {
        $token = $this->tokens->buscarPorHash(TokenAcesso::hashDe($valorToken));

        if ($token === null) {
            return;
        }

        $token->revogar($this->relogio->agora());
        $this->tokens->salvar($token);
    }
}
