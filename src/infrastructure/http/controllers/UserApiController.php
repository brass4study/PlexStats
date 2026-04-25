<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Application\Ports\UserRepositoryInterface;
use Throwable;

final class UserApiController
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $userId = filter_input(INPUT_GET, 'userId', FILTER_VALIDATE_INT);
        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'userId inválido'], JSON_THROW_ON_ERROR);
            return;
        }

        try {
            foreach ($this->userRepository->findAll() as $user) {
                if ($user->id !== $userId) {
                    continue;
                }

                echo json_encode([
                    'user' => [
                        'id' => $user->id,
                        'displayName' => $user->displayName,
                        'avatar' => $user->avatar,
                    ],
                ], JSON_THROW_ON_ERROR);
                return;
            }

            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado'], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        }
    }
}
