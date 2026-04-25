<?php

declare(strict_types=1);

namespace PlexStats\Domain\Entity;

final class UserRequestStat
{
    public function __construct(
        public readonly User $user,
        public readonly int  $yearRequestCount,
        public readonly int  $year,
    ) {}
}

