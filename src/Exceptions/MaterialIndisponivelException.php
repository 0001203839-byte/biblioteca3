<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MaterialIndisponivelException extends ConflitoException
{
    public function __construct()
    {
        parent::__construct('O material não está disponível.', 'MATERIAL_INDISPONIVEL');
    }
}
