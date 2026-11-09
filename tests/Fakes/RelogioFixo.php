<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Support\RelogioInterface;
use DateTimeImmutable;

final class RelogioFixo implements RelogioInterface
{
    private DateTimeImmutable $momento;

    public function __construct(string $momento = '2026-03-02 10:00:00')
    {
        $this->momento = new DateTimeImmutable($momento);
    }

    public function agora(): DateTimeImmutable
    {
        return $this->momento;
    }

    public function avancarDias(int $dias): void
    {
        $this->momento = $this->momento->modify("+{$dias} days");
    }

    public function avancarMinutos(int $minutos): void
    {
        $this->momento = $this->momento->modify("+{$minutos} minutes");
    }
}
