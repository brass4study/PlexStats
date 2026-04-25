<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Persistence;

use PlexStats\Application\Ports\WatchRepositoryInterface;
use PlexStats\Infrastructure\Adapters\TautulliHttpClient;

final class TautulliWatchRepository implements WatchRepositoryInterface
{
    private const PAGE_SIZE         = 1000;
    private const MAX_PAGES         = 50;
    private const MIN_WATCH_PERCENT = 85;
    private const MIN_EPISODES_TV   = 2;

    public function __construct(
        private readonly TautulliHttpClient $client,
    ) {}

    /**
     * @return array<int, array<int, true>>  [ plexUserId => [ ratingKey => true ] ]
     */
    public function getWatchedRatingKeysByPlexUser(): array
    {
        $result = $this->collectMovieKeys();

        foreach ($this->collectTvShowKeys() as $userId => $keys) {
            foreach ($keys as $key => $v) {
                $result[$userId][$key] = true;
            }
        }

        return $result;
    }

    /** @return array<int, array<int, true>> */
    private function collectMovieKeys(): array
    {
        $result = [];

        foreach ($this->paginateHistory('movie') as $item) {
            if (!$this->isWatched($item)) {
                continue;
            }
            $userId    = (int)($item['user_id'] ?? 0);
            $ratingKey = (int)($item['rating_key'] ?? 0);
            if ($userId !== 0 && $ratingKey !== 0) {
                $result[$userId][$ratingKey] = true;
            }
        }

        return $result;
    }

    /** @return array<int, array<int, true>> */
    private function collectTvShowKeys(): array
    {
        /** @var array<int, array<int, int>> $episodeCounts */
        $episodeCounts = [];

        foreach ($this->paginateHistory('episode') as $item) {
            if (!$this->isWatched($item)) {
                continue;
            }
            $userId  = (int)($item['user_id'] ?? 0);
            $showKey = (int)($item['grandparent_rating_key'] ?? 0);
            if ($userId !== 0 && $showKey !== 0) {
                $episodeCounts[$userId][$showKey] = ($episodeCounts[$userId][$showKey] ?? 0) + 1;
            }
        }

        $result = [];
        foreach ($episodeCounts as $userId => $shows) {
            foreach ($shows as $showKey => $count) {
                if ($count > self::MIN_EPISODES_TV) {
                    $result[$userId][$showKey] = true;
                }
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $item */
    private function isWatched(array $item): bool
    {
        return (int)($item['watched_status'] ?? 0) === 1
            || (int)($item['percent_complete'] ?? 0) >= self::MIN_WATCH_PERCENT;
    }

    /**
     * @param  'movie'|'episode' $mediaType
     * @return iterable<array<string, mixed>>
     */
    private function paginateHistory(string $mediaType): iterable
    {
        $start = 0;
        $page  = 0;

        do {
            $data  = $this->client->get('get_history', [
                'media_type' => $mediaType,
                'grouping'   => 1,
                'length'     => self::PAGE_SIZE,
                'start'      => $start,
            ]);
            $items = is_array($data) ? (array)($data['data'] ?? []) : [];
            $total = is_array($data) ? (int)($data['recordsFiltered'] ?? 0) : 0;

            foreach ($items as $item) {
                if (is_array($item)) {
                    yield $item;
                }
            }

            $start += self::PAGE_SIZE;
            $page++;

        } while (!empty($items) && $start < $total && $page < self::MAX_PAGES);
    }
}
