<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum Permissao: string
{
    case CONSULTAR_MATERIAIS = 'materiais.consultar';
    case CADASTRAR_MATERIAIS = 'materiais.cadastrar';
    case ATUALIZAR_MATERIAIS = 'materiais.atualizar';
    case CONSULTAR_USUARIOS = 'usuarios.consultar';
    case GERENCIAR_USUARIOS = 'usuarios.gerenciar';
    case CONSULTAR_EMPRESTIMOS = 'emprestimos.consultar';
    case CONSULTAR_PROPRIOS_EMPRESTIMOS = 'emprestimos.consultar_proprios';
    case REGISTRAR_EMPRESTIMOS = 'emprestimos.registrar';
    case REGISTRAR_DEVOLUCOES = 'emprestimos.devolver';
    case CONSULTAR_MULTAS = 'multas.consultar';
    case CONSULTAR_PROPRIAS_MULTAS = 'multas.consultar_proprias';
    case CONSULTAR_HISTORICO = 'historico.consultar';
    case CONSULTAR_RELATORIOS = 'relatorios.consultar';
}
