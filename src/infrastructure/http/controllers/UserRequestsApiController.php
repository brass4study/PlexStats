<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Application\UseCases\GetUserRequestsWithWatchStatus;
use PlexStats\Domain\Entities\MediaRequest;
use Throwable;

final class UserRequestsApiController
{
    public function __construct(
        private readonly GetUserRequestsWithWatchStatus $useCase,
    ) {}

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $userId = filter_input(INPUT_GET, 'userId', FILTER_VALIDATE_INT);
        $year   = filter_input(INPUT_GET, 'year',   FILTER_VALIDATE_INT);

        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'userId inválido'], JSON_THROW_ON_ERROR);
            return;
        }

        if ($year === false || $year === null || $year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        try {
            $requests = $this->useCase->execute($userId, $year);
            $data = array_map(
                static fn(MediaRequest $request): array => [
                    'id'          => $request->id,
                    'title'       => $request->title,
                    'mediaType'   => $request->mediaType,
                    'posterPath'  => $request->posterPath,
                    'ratingKey'   => $request->ratingKey,
                    'watched'     => $request->watched,
                    'requestedAt' => $request->requestedAt,
                    'genres'      => $request->genres,
                    'watchedAt'   => $request->watchedAt,
                ],
                $requests,
            );

            echo json_encode([
                'requests' => $data,
                'profile' => $this->buildProfile($requests),
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @param  MediaRequest[] $requests
     * @return array<string, mixed>
     */
    private function buildProfile(array $requests): array
    {
        $watchedCount = 0;
        $movieCount = 0;
        $tvCount = 0;
        $genreCounts = [];
        $totalWatchDelayDays = 0.0;
        $watchDelayCount = 0;

        foreach ($requests as $request) {
            if ($request->watched) {
                $watchedCount++;
            }

            if ($request->mediaType === 'tv') {
                $tvCount++;
            } else {
                $movieCount++;
            }

            foreach ($request->genres as $genre) {
                $genreCounts[$genre] = ($genreCounts[$genre] ?? 0) + 1;
            }

            $watchDelay = $this->calculateWatchDelayDays($request);
            if ($watchDelay !== null) {
                $totalWatchDelayDays += $watchDelay;
                $watchDelayCount++;
            }
        }

        arsort($genreCounts);
        $requestCount = count($requests);

        return [
            'watchedPercentage' => $requestCount > 0 ? round(($watchedCount / $requestCount) * 100, 1) : 0.0,
            'movieCount' => $movieCount,
            'moviePercentage' => $requestCount > 0 ? round(($movieCount / $requestCount) * 100, 1) : 0.0,
            'tvCount' => $tvCount,
            'tvPercentage' => $requestCount > 0 ? round(($tvCount / $requestCount) * 100, 1) : 0.0,
            'topGenres' => array_slice(array_keys($genreCounts), 0, 3),
            'averageDaysToWatch' => $watchDelayCount > 0 ? round($totalWatchDelayDays / $watchDelayCount, 1) : null,
        ];
    }

    private function calculateWatchDelayDays(MediaRequest $request): ?float
    {
        if ($request->watchedAt === null) {
            return null;
        }

        $requestedAt = strtotime($request->requestedAt);
        if ($requestedAt === false || $request->watchedAt < $requestedAt) {
            return null;
        }

        return ($request->watchedAt - $requestedAt) / 86400;
    }
}
