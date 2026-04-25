<?php

declare(strict_types=1);

namespace PlexStats\Domain\Entities;

final class MediaRequest
{
    public function __construct(
        public readonly int    $id,
        public readonly string $title,
        public readonly string $mediaType,
        public readonly string $posterPath,
        public readonly int    $ratingKey,
        public readonly bool   $watched,
        public readonly string $requestedAt,
        public readonly array  $genres = [],
        public readonly ?int   $watchedAt = null,
    ) {}
}
