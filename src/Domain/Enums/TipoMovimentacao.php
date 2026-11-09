<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum TipoMovimentacao: string
{
    case EMPRESTIMO = 'emprestimo';
    case DEVOLUCAO = 'devolucao';
    case MULTA = 'multa';
    case CADASTRO_MATERIAL = 'cadastro_material';
    case ATUALIZACAO_MATERIAL = 'atualizacao_material';
    case CADASTRO_USUARIO = 'cadastro_usuario';
    case BLOQUEIO_USUARIO = 'bloqueio_usuario';
    case DESBLOQUEIO_USUARIO = 'desbloqueio_usuario';
}
