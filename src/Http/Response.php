<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(
        private readonly int $status,
        private readonly array $corpo,
    ) {
    }

    public static function sucesso(mixed $dados, int $status = 200, array $extras = []): self
    {
        return new self($status, ['sucesso' => true, 'dados' => $dados] + $extras);
    }

    public static function criado(mixed $dados): self
    {
        return self::sucesso($dados, 201);
    }

    public static function erro(int $status, string $codigo, string $mensagem, array $detalhes = []): self
    {
        $erro = ['codigo' => $codigo, 'mensagem' => $mensagem];

        if ($detalhes !== []) {
            $erro['detalhes'] = $detalhes;
        }

        return new self($status, ['sucesso' => false, 'erro' => $erro]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function corpo(): array
    {
        return $this->corpo;
    }

    public function json(): string
    {
        return json_encode(
            $this->corpo,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
        );
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        echo $this->json();
    }
}
