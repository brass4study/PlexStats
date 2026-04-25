<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Application\Ports\UserRepositoryInterface;
use PlexStats\Application\Ports\WatchRepositoryInterface;
use Throwable;

final class WatchCountsApiController
{
    public function __construct(
        private readonly UserRepositoryInterface   $userRepository,
        private readonly ?WatchRepositoryInterface $watchRepository,
    ) {}

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        if ($year === false || $year === null || $year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        // Sin integración Tautulli devolvemos todo a cero
        if ($this->watchRepository === null) {
            echo json_encode(['byUser' => [], 'total' => 0], JSON_THROW_ON_ERROR);
            return;
        }

        try {
            $requestedByUser = $this->userRepository->getRequestedRatingKeysByUserForYear($year);
            $watchedByUser   = $this->watchRepository->getWatchedRatingKeysByPlexUser();

            $byUser = [];
            $total  = 0;

            foreach ($requestedByUser as $overseerrUserId => $ratingKeys) {
                $watched = 0;
                foreach ($ratingKeys as $ratingKey) {
                    foreach ($watchedByUser as $watchedKeys) {
                        if (isset($watchedKeys[$ratingKey])) {
                            $watched++;
                            break;
                        }
                    }
                }
                $byUser[$overseerrUserId] = $watched;
                $total += $watched;
            }

            echo json_encode(['byUser' => $byUser, 'total' => $total], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }
}
