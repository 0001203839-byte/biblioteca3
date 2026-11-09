<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class RelogioSistema implements RelogioInterface
{
    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
