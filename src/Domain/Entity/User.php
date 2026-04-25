<?php

declare(strict_types=1);

namespace PlexStats\Domain\Entity;

final class User
{
    public function __construct(
        public readonly int    $id,
        public readonly string $displayName,
        public readonly string $email,
        public readonly string $avatar,
        public readonly int    $userType,
        public readonly int    $permissions,
        public readonly int    $totalRequestCount,
    ) {}

    public function isAdmin(): bool
    {
        return ($this->permissions & 2) === 2;
    }

    public function isLocalUser(): bool
    {
        return $this->userType === 2;
    }
}

