<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Enums\Permissao;
use App\Domain\Enums\TipoMovimentacao;
use App\Domain\Factories\MaterialFactory;
use App\Domain\Filtros\FiltroMateriais;
use App\Domain\Models\ContaAcesso;
use App\Domain\Models\Material;
use App\Domain\Models\Movimentacao;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Repositories\Contracts\HistoricoRepositoryInterface;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Repositories\Contracts\TransacaoInterface;
use App\Support\RelogioInterface;
use App\Support\ResultadoPaginado;

final class MaterialService
{
    public function __construct(
        private readonly MaterialRepositoryInterface $materiais,
        private readonly HistoricoRepositoryInterface $historico,
        private readonly TransacaoInterface $transacao,
        private readonly RelogioInterface $relogio,
        private readonly MaterialFactory $fabrica = new MaterialFactory(),
    ) {
    }

    public function listar(array $parametros): ResultadoPaginado
    {
        return $this->materiais->listar(FiltroMateriais::daQuery($parametros));
    }

    public function buscar(int $id): Material
    {
        return $this->materiais->buscarPorId($id) ?? throw RecursoNaoEncontradoException::material($id);
    }

    public function cadastrar(array $dados, ContaAcesso $operador): Material
    {
        $operador->exigirPermissao(Permissao::CADASTRAR_MATERIAIS);

        $material = $this->fabrica->criar($dados);

        return $this->transacao->executar(function () use ($material, $operador): Material {
            $this->materiais->salvar($material);

            $this->historico->registrar(new Movimentacao(
                TipoMovimentacao::CADASTRO_MATERIAL,
                sprintf('%s "%s" cadastrado com %d unidade(s).', $material->descricaoTipo(), $material->titulo(), $material->quantidadeTotal()),
                $this->relogio->agora(),
                materialId: $material->id(),
                contaId: $operador->id(),
            ));

            return $material;
        });
    }

    public function atualizar(int $id, array $dados, ContaAcesso $operador): Material
    {
        $operador->exigirPermissao(Permissao::ATUALIZAR_MATERIAIS);

        return $this->transacao->executar(function () use ($id, $dados, $operador): Material {
            $atual = $this->materiais->buscarParaAtualizacao($id) ?? throw RecursoNaoEncontradoException::material($id);
            $atualizado = $this->fabrica->atualizar($atual, $dados);

            $this->materiais->salvar($atualizado);

            $this->historico->registrar(new Movimentacao(
                TipoMovimentacao::ATUALIZACAO_MATERIAL,
                sprintf('Dados do material "%s" atualizados.', $atualizado->titulo()),
                $this->relogio->agora(),
                materialId: $atualizado->id(),
                contaId: $operador->id(),
            ));

            return $atualizado;
        });
    }
}
