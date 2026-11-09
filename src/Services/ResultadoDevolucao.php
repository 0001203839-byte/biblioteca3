<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\Emprestimo;
use App\Domain\Models\Multa;

final class ResultadoDevolucao
{
    public function __construct(
        public readonly Emprestimo $emprestimo,
        public readonly ?Multa $multa = null,
    ) {
    }

    public function gerouMulta(): bool
    {
        return $this->multa !== null;
    }
}
