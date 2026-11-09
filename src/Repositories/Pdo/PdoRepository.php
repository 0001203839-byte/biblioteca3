<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Exceptions\ConflitoException;
use DateTimeImmutable;
use PDO;
use PDOException;
use PDOStatement;

abstract class PdoRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    protected function executar(string $sql, array $parametros = []): PDOStatement
    {
        try {
            $comando = $this->pdo->prepare($sql);

            foreach ($parametros as $nome => $valor) {
                $comando->bindValue(':' . $nome, ...$this->valorParaBind($valor));
            }

            $comando->execute();

            return $comando;
        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {
                throw $this->traduzirViolacaoDeIntegridade($erro);
            }

            throw $erro;
        }
    }

    protected function buscarLinha(string $sql, array $parametros = []): ?array
    {
        $linha = $this->executar($sql, $parametros)->fetch(PDO::FETCH_ASSOC);

        return $linha === false ? null : $linha;
    }

    protected function buscarLinhas(string $sql, array $parametros = []): array
    {
        return $this->executar($sql, $parametros)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function ultimoId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    protected function travaDeLinha(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    }

    protected function paraData(?string $valor): ?DateTimeImmutable
    {
        return $valor === null ? null : new DateTimeImmutable($valor);
    }

    protected function paraLike(string $valor): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $valor) . '%';
    }

    private function valorParaBind(mixed $valor): array
    {
        return match (true) {
            $valor === null => [null, PDO::PARAM_NULL],
            is_bool($valor) => [(int) $valor, PDO::PARAM_INT],
            is_int($valor) => [$valor, PDO::PARAM_INT],
            $valor instanceof DateTimeImmutable => [$valor->format('Y-m-d H:i:s'), PDO::PARAM_STR],
            default => [(string) $valor, PDO::PARAM_STR],
        };
    }

    private function traduzirViolacaoDeIntegridade(PDOException $erro): ConflitoException
    {
        $mensagem = $erro->getMessage();

        if (str_contains($mensagem, 'Duplicate') || str_contains($mensagem, 'UNIQUE')) {
            return new ConflitoException('Já existe um registro com estes dados.', 'REGISTRO_DUPLICADO');
        }

        return new ConflitoException('A operação viola uma regra de integridade dos dados.', 'VIOLACAO_INTEGRIDADE');
    }
}
