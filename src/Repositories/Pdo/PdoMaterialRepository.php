<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Domain\Factories\MaterialFactory;
use App\Domain\Filtros\FiltroMateriais;
use App\Domain\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Support\ResultadoPaginado;
use PDO;

final class PdoMaterialRepository extends PdoRepository implements MaterialRepositoryInterface
{
    private const CONSULTA_BASE = 'SELECT m.id, m.tipo, m.titulo, m.autor, m.ano_publicacao,
            m.quantidade_total, m.quantidade_disponivel,
            l.isbn, l.editora, l.paginas,
            r.edicao, r.issn,
            e.formato, e.url_acesso
        FROM materiais m
        LEFT JOIN livros l ON l.material_id = m.id
        LEFT JOIN revistas r ON r.material_id = m.id
        LEFT JOIN ebooks e ON e.material_id = m.id';

    private const TABELAS_DETALHE = [
        'livro' => 'livros',
        'revista' => 'revistas',
        'ebook' => 'ebooks',
    ];

    private const COLUNAS_ORDENACAO = [
        'id' => 'm.id',
        'titulo' => 'm.titulo',
        'autor' => 'm.autor',
        'ano' => 'm.ano_publicacao',
        'disponibilidade' => 'm.quantidade_disponivel',
    ];

    public function __construct(PDO $pdo, private readonly MaterialFactory $fabrica = new MaterialFactory())
    {
        parent::__construct($pdo);
    }

    public function buscarPorId(int $id): ?Material
    {
        $linha = $this->buscarLinha(self::CONSULTA_BASE . ' WHERE m.id = :id', ['id' => $id]);

        return $linha === null ? null : $this->fabrica->reconstituir($linha);
    }

    public function buscarParaAtualizacao(int $id): ?Material
    {
        $existe = $this->buscarLinha('SELECT id FROM materiais WHERE id = :id' . $this->travaDeLinha(), ['id' => $id]);

        return $existe === null ? null : $this->buscarPorId($id);
    }

    public function listar(FiltroMateriais $filtro): ResultadoPaginado
    {
        $condicoes = [];
        $parametros = [];

        if ($filtro->titulo !== null) {
            $condicoes[] = "m.titulo LIKE :titulo ESCAPE '!'";
            $parametros['titulo'] = $this->paraLike($filtro->titulo);
        }

        if ($filtro->autor !== null) {
            $condicoes[] = "m.autor LIKE :autor ESCAPE '!'";
            $parametros['autor'] = $this->paraLike($filtro->autor);
        }

        if ($filtro->tipo !== null) {
            $condicoes[] = 'm.tipo = :tipo';
            $parametros['tipo'] = $filtro->tipo;
        }

        if ($filtro->disponivel !== null) {
            $condicoes[] = $filtro->disponivel ? 'm.quantidade_disponivel > 0' : 'm.quantidade_disponivel = 0';
        }

        $where = $condicoes === [] ? '' : ' WHERE ' . implode(' AND ', $condicoes);

        $total = (int) $this->executar('SELECT COUNT(*) FROM materiais m' . $where, $parametros)->fetchColumn();

        $coluna = self::COLUNAS_ORDENACAO[$filtro->ordenarPor] ?? 'm.titulo';
        $direcao = $filtro->direcao === 'desc' ? 'DESC' : 'ASC';

        $linhas = $this->buscarLinhas(
            self::CONSULTA_BASE . $where . " ORDER BY {$coluna} {$direcao}, m.id ASC LIMIT :limite OFFSET :deslocamento",
            $parametros + [
                'limite' => $filtro->paginacao->porPagina,
                'deslocamento' => $filtro->paginacao->deslocamento(),
            ],
        );

        return new ResultadoPaginado(
            array_map(fn (array $linha): Material => $this->fabrica->reconstituir($linha), $linhas),
            $total,
            $filtro->paginacao,
        );
    }

    public function salvar(Material $material): void
    {
        if ($material->id() === null) {
            $this->inserir($material);

            return;
        }

        $this->executar(
            'UPDATE materiais
                SET titulo = :titulo, autor = :autor, ano_publicacao = :ano_publicacao,
                    quantidade_total = :quantidade_total, quantidade_disponivel = :quantidade_disponivel
              WHERE id = :id',
            $this->dadosPrincipais($material) + ['id' => $material->id()],
        );

        $detalhes = $material->detalhes();
        $atribuicoes = implode(', ', array_map(fn (string $coluna): string => "{$coluna} = :{$coluna}", array_keys($detalhes)));

        $this->executar(
            "UPDATE {$this->tabelaDetalhe($material)} SET {$atribuicoes} WHERE material_id = :material_id",
            $detalhes + ['material_id' => $material->id()],
        );
    }

    public function atualizarDisponibilidade(Material $material): void
    {
        $this->executar(
            'UPDATE materiais SET quantidade_disponivel = :quantidade_disponivel WHERE id = :id',
            ['quantidade_disponivel' => $material->quantidadeDisponivel(), 'id' => $material->id()],
        );
    }

    private function inserir(Material $material): void
    {
        $this->executar(
            'INSERT INTO materiais (tipo, titulo, autor, ano_publicacao, quantidade_total, quantidade_disponivel)
             VALUES (:tipo, :titulo, :autor, :ano_publicacao, :quantidade_total, :quantidade_disponivel)',
            ['tipo' => $material->tipo()] + $this->dadosPrincipais($material),
        );

        $material->definirId($this->ultimoId());

        $detalhes = $material->detalhes();
        $colunas = implode(', ', array_keys($detalhes));
        $marcadores = implode(', ', array_map(fn (string $coluna): string => ':' . $coluna, array_keys($detalhes)));

        $this->executar(
            "INSERT INTO {$this->tabelaDetalhe($material)} (material_id, {$colunas}) VALUES (:material_id, {$marcadores})",
            ['material_id' => $material->id()] + $detalhes,
        );
    }

    private function dadosPrincipais(Material $material): array
    {
        return [
            'titulo' => $material->titulo(),
            'autor' => $material->autor(),
            'ano_publicacao' => $material->anoPublicacao(),
            'quantidade_total' => $material->quantidadeTotal(),
            'quantidade_disponivel' => $material->quantidadeDisponivel(),
        ];
    }

    private function tabelaDetalhe(Material $material): string
    {
        return self::TABELAS_DETALHE[$material->tipo()];
    }
}
