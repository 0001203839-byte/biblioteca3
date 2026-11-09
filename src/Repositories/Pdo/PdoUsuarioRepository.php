<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Factories\UsuarioFactory;
use App\Domain\Models\Usuario;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use PDO;

final class PdoUsuarioRepository extends PdoRepository implements UsuarioRepositoryInterface
{
    private const COLUNAS = 'id, nome, email, documento, tipo, bloqueado';

    public function __construct(PDO $pdo, private readonly UsuarioFactory $fabrica = new UsuarioFactory())
    {
        parent::__construct($pdo);
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $linha = $this->buscarLinha('SELECT ' . self::COLUNAS . ' FROM usuarios WHERE id = :id', ['id' => $id]);

        return $linha === null ? null : $this->hidratar($linha);
    }

    public function buscarParaAtualizacao(int $id): ?Usuario
    {
        $linha = $this->buscarLinha(
            'SELECT ' . self::COLUNAS . ' FROM usuarios WHERE id = :id' . $this->travaDeLinha(),
            ['id' => $id],
        );

        return $linha === null ? null : $this->hidratar($linha);
    }

    public function listar(?string $tipo = null): array
    {
        $sql = 'SELECT ' . self::COLUNAS . ' FROM usuarios';
        $parametros = [];

        if ($tipo !== null) {
            $sql .= ' WHERE tipo = :tipo';
            $parametros['tipo'] = $tipo;
        }

        return array_map(
            fn (array $linha): Usuario => $this->hidratar($linha),
            $this->buscarLinhas($sql . ' ORDER BY nome', $parametros),
        );
    }

    public function existeEmail(string $email): bool
    {
        return $this->buscarLinha('SELECT 1 FROM usuarios WHERE email = :email', ['email' => mb_strtolower($email)]) !== null;
    }

    public function existeDocumento(string $documento): bool
    {
        return $this->buscarLinha('SELECT 1 FROM usuarios WHERE documento = :documento', ['documento' => $documento]) !== null;
    }

    public function salvar(Usuario $usuario): void
    {
        $dados = [
            'nome' => $usuario->nome(),
            'email' => $usuario->email(),
            'documento' => $usuario->documento(),
            'tipo' => $usuario->tipo(),
            'bloqueado' => $usuario->estaBloqueado(),
        ];

        if ($usuario->id() === null) {
            $this->executar(
                'INSERT INTO usuarios (nome, email, documento, tipo, bloqueado)
                 VALUES (:nome, :email, :documento, :tipo, :bloqueado)',
                $dados,
            );
            $usuario->definirId($this->ultimoId());

            return;
        }

        $this->executar(
            'UPDATE usuarios
                SET nome = :nome, email = :email, documento = :documento, tipo = :tipo, bloqueado = :bloqueado
              WHERE id = :id',
            $dados + ['id' => $usuario->id()],
        );
    }

    private function hidratar(array $linha): Usuario
    {
        return $this->fabrica->montar(
            $linha['tipo'],
            $linha['nome'],
            $linha['email'],
            $linha['documento'],
            (bool) $linha['bloqueado'],
            (int) $linha['id'],
        );
    }
}
