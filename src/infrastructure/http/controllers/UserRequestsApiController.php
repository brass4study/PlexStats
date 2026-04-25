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
                static fn(MediaRequest $r) => [
                    'id'         => $r->id,
                    'title'      => $r->title,
                    'mediaType'  => $r->mediaType,
                    'posterPath' => $r->posterPath,
                    'ratingKey'  => $r->ratingKey,
                    'watched'    => $r->watched,
                ],
                $requests,
            );

            echo json_encode(['requests' => $data], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }
}
