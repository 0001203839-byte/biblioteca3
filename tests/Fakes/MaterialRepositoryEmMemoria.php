<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Filtros\FiltroMateriais;
use App\Domain\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Support\ResultadoPaginado;

final class MaterialRepositoryEmMemoria implements MaterialRepositoryInterface
{
    private array $materiais = [];
    private int $proximoId = 1;

    public function buscarPorId(int $id): ?Material
    {
        return $this->materiais[$id] ?? null;
    }

    public function buscarParaAtualizacao(int $id): ?Material
    {
        return $this->buscarPorId($id);
    }

    public function listar(FiltroMateriais $filtro): ResultadoPaginado
    {
        $itens = array_values(array_filter(
            $this->materiais,
            static fn (Material $material): bool => ($filtro->tipo === null || $material->tipo() === $filtro->tipo)
                && ($filtro->disponivel === null || $material->estaDisponivel() === $filtro->disponivel)
                && ($filtro->titulo === null || stripos($material->titulo(), $filtro->titulo) !== false)
                && ($filtro->autor === null || stripos($material->autor(), $filtro->autor) !== false),
        ));

        return new ResultadoPaginado(
            array_slice($itens, $filtro->paginacao->deslocamento(), $filtro->paginacao->porPagina),
            count($itens),
            $filtro->paginacao,
        );
    }

    public function salvar(Material $material): void
    {
        if ($material->id() === null) {
            $material->definirId($this->proximoId++);
        }

        $this->materiais[$material->id()] = $material;
    }

    public function atualizarDisponibilidade(Material $material): void
    {
        $this->materiais[$material->id()] = $material;
    }
}
