<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http;

/**
 * Middleware de autenticación: redirige a /login si la sesión no está activa.
 */
final class AuthMiddleware
{
    public function requireAuth(): void
    {
        if (empty($_SESSION['authenticated'])) {
            header('Location: /login');
            exit;
        }
    }
}
