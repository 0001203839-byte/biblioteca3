<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Support\Entrada;

final class EBook extends Material
{
    public const FORMATOS = ['pdf', 'epub', 'mobi'];

    private string $formato;
    private ?string $urlAcesso;

    public function __construct(
        string $titulo,
        string $autor,
        int $anoPublicacao,
        int $acessosSimultaneos,
        string $formato,
        ?string $urlAcesso = null,
        ?int $acessosDisponiveis = null,
        ?int $id = null,
    ) {
        $this->formato = strtolower(trim($formato));
        $this->urlAcesso = $urlAcesso === null || trim($urlAcesso) === '' ? null : trim($urlAcesso);

        parent::__construct($titulo, $autor, $anoPublicacao, $acessosSimultaneos, $acessosDisponiveis, $id);
    }

    public static function deEntrada(Entrada $dados, ?int $quantidadeDisponivel = null, ?int $id = null): static
    {
        return new self(
            $dados->texto('titulo'),
            $dados->texto('autor'),
            $dados->inteiro('ano_publicacao'),
            $dados->inteiro('quantidade_total'),
            $dados->texto('formato'),
            $dados->textoOuNulo('url_acesso'),
            $quantidadeDisponivel,
            $id,
        );
    }

    public function tipo(): string
    {
        return 'ebook';
    }

    public function descricaoTipo(): string
    {
        return 'E-book';
    }

    public function prazoBaseEmDias(): int
    {
        return 10;
    }

    public function detalhes(): array
    {
        return [
            'formato' => $this->formato,
            'url_acesso' => $this->urlAcesso,
        ];
    }

    public function toArray(): array
    {
        return parent::toArray() + [
            'acessos_simultaneos' => $this->quantidadeTotal(),
            'acessos_em_uso' => $this->quantidadeEmprestada(),
        ];
    }

    protected function validarDetalhes(): array
    {
        $erros = [];

        if (!in_array($this->formato, self::FORMATOS, true)) {
            $erros['formato'] = 'Formato inválido. Use: ' . implode(', ', self::FORMATOS) . '.';
        }

        if ($this->urlAcesso !== null && filter_var($this->urlAcesso, FILTER_VALIDATE_URL) === false) {
            $erros['url_acesso'] = 'Informe uma URL de acesso válida.';
        }

        return $erros;
    }
}
