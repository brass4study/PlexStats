<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Application\Ports\UserRepositoryInterface;
use PlexStats\Application\Ports\WatchRepositoryInterface;
use Throwable;

final class WatchCountsApiController
{
    public function __construct(
        private readonly UserRepositoryInterface  $userRepository,
        private readonly WatchRepositoryInterface $watchRepository,
    ) {}

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        if ($year === false || $year === null || $year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        try {
            $requestedByUser = $this->userRepository->getRequestedRatingKeysByUserForYear($year);
            $watchedByUser   = $this->watchRepository->getWatchedRatingKeysByPlexUser();

            $counts = [];
            foreach ($requestedByUser as $overseerrUserId => $ratingKeys) {
                $watched = 0;
                foreach ($ratingKeys as $ratingKey) {
                    // Los rating keys Plex del usuario están indexados por plexId.
                    // Si Overseerr y Plex comparten el mismo ID de usuario, comparamos directamente.
                    foreach ($watchedByUser as $watchedKeys) {
                        if (isset($watchedKeys[$ratingKey])) {
                            $watched++;
                            break;
                        }
                    }
                }
                $counts[$overseerrUserId] = [
                    'total'   => count($ratingKeys),
                    'watched' => $watched,
                ];
            }

            echo json_encode(['watchCounts' => $counts], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }
}
