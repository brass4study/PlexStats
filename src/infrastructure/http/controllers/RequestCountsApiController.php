<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Application\Ports\UserRepositoryInterface;
use Throwable;

final class RequestCountsApiController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        if ($year === false || $year === null || $year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        try {
            $byUser = $this->userRepository->countRequestsByUserForYear($year);
            $total  = (int)array_sum($byUser);
            $active = count($byUser);

            echo json_encode(
                [
                    'year'   => $year,
                    'total'  => $total,
                    'active' => $active,
                    'byUser' => $byUser,
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }
}
