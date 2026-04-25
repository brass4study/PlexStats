<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Persistence;

use PlexStats\Application\Ports\RequestRepositoryInterface;
use PlexStats\Domain\Entities\MediaRequest;
use PlexStats\Domain\Errors\OverseerrException;
use PlexStats\Infrastructure\Adapters\OverseerrHttpClient;

final class OverseerrRequestRepository implements RequestRepositoryInterface
{
    private const PAGE_SIZE    = 100;
    private const MAX_REQUESTS = 5_000;

    public function __construct(
        private readonly OverseerrHttpClient $client,
    ) {}

    /**
     * @param  array<int, true> $watchedRatingKeys
     * @return MediaRequest[]
     */
    public function findByUserAndYear(int $overseerrUserId, int $year, array $watchedRatingKeys): array
    {
        $rawItems = $this->fetchRawItemsForYear($overseerrUserId, $year);
        if (empty($rawItems)) {
            return [];
        }

        $detailCache = $this->buildDetailCache($rawItems);

        $results = [];
        foreach ($rawItems as $item) {
            $detail    = $detailCache[$item['mediaType'] . ':' . $item['tmdbId']];
            $ratingKey = $item['ratingKey'];

            $results[] = new MediaRequest(
                id:         $item['id'],
                title:      $detail['title'] !== '' ? $detail['title'] : 'Sin título',
                mediaType:  $item['mediaType'],
                posterPath: $detail['posterPath'],
                ratingKey:  $ratingKey,
                watched:    $ratingKey !== 0 && isset($watchedRatingKeys[$ratingKey]),
            );
        }

        return $results;
    }

    /** @return array<int, array{id: int, tmdbId: int, mediaType: string, ratingKey: int}> */
    private function fetchRawItemsForYear(int $overseerrUserId, int $year): array
    {
        $rawItems = [];
        $skip     = 0;

        do {
            $resp  = $this->client->get(
                '/request?take=' . self::PAGE_SIZE
                . '&skip=' . $skip
                . '&filter=all&sort=added&requestedBy=' . $overseerrUserId,
            );
            $items = $resp['results'] ?? [];
            $total = (int)($resp['pageInfo']['results'] ?? 0);

            foreach ($items as $req) {
                $rawItem = $this->extractRawItem($req, $year);
                if ($rawItem !== null) {
                    $rawItems[] = $rawItem;
                }
            }

            $skip += self::PAGE_SIZE;
        } while (!empty($items) && $skip < $total && $skip < self::MAX_REQUESTS);

        return $rawItems;
    }

    /**
     * @param  array<string, mixed> $req
     * @return array{id: int, tmdbId: int, mediaType: string, ratingKey: int}|null
     */
    private function extractRawItem(array $req, int $year): ?array
    {
        if ((int)substr((string)($req['createdAt'] ?? ''), 0, 4) !== $year) {
            return null;
        }
        $media  = $req['media'] ?? [];
        $tmdbId = (int)($media['tmdbId'] ?? 0);
        if ($tmdbId === 0) {
            return null;
        }

        return [
            'id'        => (int)($req['id'] ?? 0),
            'tmdbId'    => $tmdbId,
            'mediaType' => (string)($media['mediaType'] ?? 'movie'),
            'ratingKey' => (int)($media['ratingKey'] ?? 0),
        ];
    }

    /**
     * @param  array<int, array{tmdbId: int, mediaType: string}> $rawItems
     * @return array<string, array{title: string, posterPath: string}>
     */
    private function buildDetailCache(array $rawItems): array
    {
        $cache = [];
        foreach ($rawItems as $item) {
            $cacheKey = $item['mediaType'] . ':' . $item['tmdbId'];
            if (!array_key_exists($cacheKey, $cache)) {
                $cache[$cacheKey] = $this->fetchMediaDetail($item['tmdbId'], $item['mediaType']);
            }
        }
        return $cache;
    }

    /** @return array{title: string, posterPath: string} */
    private function fetchMediaDetail(int $tmdbId, string $mediaType): array
    {
        $path = $mediaType === 'tv' ? '/tv/' . $tmdbId : '/movie/' . $tmdbId;
        try {
            $data = $this->client->get($path);
        } catch (OverseerrException) {
            return ['title' => '', 'posterPath' => ''];
        }

        return [
            'title'      => (string)($data['title'] ?? $data['name'] ?? $data['originalTitle'] ?? $data['originalName'] ?? ''),
            'posterPath' => (string)($data['posterPath'] ?? ''),
        ];
    }
}
