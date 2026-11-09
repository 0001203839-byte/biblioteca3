<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Models\ContaAcesso;
use App\Exceptions\NaoAutenticadoException;
use App\Exceptions\RequisicaoInvalidaException;
use JsonException;

final class Request
{
    private array $cabecalhos;
    private array $parametrosDeRota = [];
    private ?array $corpo = null;
    private ?ContaAcesso $conta = null;

    public function __construct(
        private readonly string $metodo,
        private readonly string $caminho,
        private readonly array $query = [],
        private readonly string $corpoBruto = '',
        array $cabecalhos = [],
    ) {
        $this->cabecalhos = array_change_key_case($cabecalhos, CASE_LOWER);
    }

    public static function capturar(): self
    {
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        if ($base !== '' && str_starts_with($caminho, $base . '/')) {
            $caminho = substr($caminho, strlen($base));
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $caminho,
            $_GET,
            (string) file_get_contents('php://input'),
            self::cabecalhosDoServidor(),
        );
    }

    private static function cabecalhosDoServidor(): array
    {
        $cabecalhos = function_exists('getallheaders') ? array_change_key_case(getallheaders() ?: [], CASE_LOWER) : [];

        foreach ($_SERVER as $chave => $valor) {
            if (str_starts_with($chave, 'HTTP_')) {
                $cabecalhos[str_replace('_', '-', strtolower(substr($chave, 5)))] ??= $valor;
            }
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $cabecalhos['authorization'] ??= $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return $cabecalhos;
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function caminho(): string
    {
        $caminho = rtrim($this->caminho, '/');

        return $caminho === '' ? '/' : $caminho;
    }

    public function query(): array
    {
        return $this->query;
    }

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    public function corpo(): array
    {
        if ($this->corpo !== null) {
            return $this->corpo;
        }

        if (trim($this->corpoBruto) === '') {
            return $this->corpo = [];
        }

        try {
            $dados = json_decode($this->corpoBruto, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RequisicaoInvalidaException('O corpo da requisição não é um JSON válido.', 'JSON_INVALIDO');
        }

        if (!is_array($dados) || ($dados !== [] && array_is_list($dados))) {
            throw new RequisicaoInvalidaException('O corpo da requisição deve ser um objeto JSON.', 'JSON_INVALIDO');
        }

        return $this->corpo = $dados;
    }

    public function tokenBearer(): ?string
    {
        $valor = $this->cabecalho('authorization');

        if ($valor === null || !preg_match('/^Bearer\s+(\S+)$/i', trim($valor), $partes)) {
            return null;
        }

        return $partes[1];
    }

    public function definirParametrosDeRota(array $parametros): void
    {
        $this->parametrosDeRota = $parametros;
    }

    public function parametroInteiro(string $nome): int
    {
        return (int) ($this->parametrosDeRota[$nome] ?? 0);
    }

    public function autenticarComo(ContaAcesso $conta): void
    {
        $this->conta = $conta;
    }

    public function contaAutenticada(): ?ContaAcesso
    {
        return $this->conta;
    }

    public function conta(): ContaAcesso
    {
        return $this->conta ?? throw new NaoAutenticadoException();
    }
}
