<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

interface RelogioInterface
{
    public function agora(): DateTimeImmutable;
}
