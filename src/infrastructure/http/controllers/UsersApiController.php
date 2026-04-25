<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Application\UseCases\GetUsersWithRequestStats;
use PlexStats\Domain\Entities\UserRequestStat;
use Throwable;

/**
 * Endpoint JSON: devuelve usuarios + recuento de peticiones para el año dado.
 */
final class UsersApiController
{
    public function __construct(
        private readonly GetUsersWithRequestStats $getUserStats,
    ) {}

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        if ($year === false || $year === null || $year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        try {
            $stats = $this->getUserStats->execute($year);

            echo json_encode(
                [
                    'year'              => $year,
                    'totalYearRequests' => (int)array_sum(
                        array_map(static fn(UserRequestStat $s) => $s->yearRequestCount, $stats)
                    ),
                    'users' => array_map(
                        static fn(UserRequestStat $s) => [
                            'id'               => $s->user->id,
                            'displayName'      => $s->user->displayName,
                            'email'            => $s->user->email,
                            'avatar'           => $s->user->avatar,
                            'userType'         => $s->user->userType,
                            'permissions'      => $s->user->permissions,
                            'requestCount'     => $s->user->totalRequestCount,
                            'yearRequestCount' => $s->yearRequestCount,
                        ],
                        $stats,
                    ),
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }
}
