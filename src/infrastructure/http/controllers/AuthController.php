<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Infrastructure\Adapters\OverseerrAuthService;
use PlexStats\Infrastructure\Adapters\PlexAuthService;
use Throwable;

/**
 * Gestiona el flujo de autenticación via Plex OAuth + Overseerr.
 *
 * Flujo:
 *   GET  /login                → muestra botón "Login with Plex"
 *   GET  /auth/plex/init       → crea PIN en plex.tv y redirige a Plex
 *   GET  /auth/plex/callback   → verifica PIN, autentica contra Overseerr, inicia sesión
 *   GET  /logout               → destruye sesión
 */
final class AuthController
{
    private const REDIRECT_LOGIN = 'Location: /login';

    public function __construct(
        private readonly PlexAuthService      $plexAuth,
        private readonly OverseerrAuthService $overseerrAuth,
        private readonly string               $appUrl,
    ) {}

    // ── Pantalla de login ─────────────────────────────────────

    public function showLogin(): void
    {
        if (!empty($_SESSION['authenticated'])) {
            header('Location: /');
            exit;
        }

        $error = (string)($_SESSION['auth_error'] ?? ''); // NOSONAR — used by login.php view
        unset($_SESSION['auth_error']);

        require_once __DIR__ . '/../views/login.php';
    }

    // ── Inicio del flujo Plex OAuth ───────────────────────────

    public function initPlexAuth(): void
    {
        try {
            $pin = $this->plexAuth->createPin();

            $_SESSION['plex_pin_id'] = $pin['id'];

            $callbackUrl = rtrim($this->appUrl, '/') . '/auth/plex/callback';
            $authUrl     = $this->plexAuth->buildAuthUrl($pin['code'], $callbackUrl);

            header('Location: ' . $authUrl);
            exit;
        } catch (Throwable $e) {
            $_SESSION['auth_error'] = 'No se pudo iniciar la autenticación con Plex: ' . $e->getMessage();
            header(self::REDIRECT_LOGIN);
            exit;
        }
    }

    // ── Callback tras autenticación en Plex ───────────────────

    public function plexCallback(): void
    {
        $pinId = (int)($_SESSION['plex_pin_id'] ?? 0);

        if ($pinId === 0) {
            header(self::REDIRECT_LOGIN);
            exit;
        }

        try {
            $pin   = $this->plexAuth->getPin($pinId);
            $token = $pin['authToken'] ?? null;

            if (!$token) {
                $_SESSION['auth_error'] = 'No se completó la autorización en Plex. Inténtalo de nuevo.';
                header(self::REDIRECT_LOGIN);
                exit;
            }

            $user = $this->overseerrAuth->authenticateWithPlexToken($token);

            session_regenerate_id(true);
            unset($_SESSION['plex_pin_id']);

            $_SESSION['authenticated'] = true;
            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['user_name']     = $user['displayName'] ?? $user['plexUsername'] ?? 'Usuario';
            $_SESSION['user_avatar']   = (string)($user['avatar'] ?? '');

            header('Location: /');
            exit;
        } catch (Throwable $e) {
            unset($_SESSION['plex_pin_id']);
            $_SESSION['auth_error'] = $e->getMessage();
            header(self::REDIRECT_LOGIN);
            exit;
        }
    }

    // ── Cierre de sesión ──────────────────────────────────────

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly'],
            );
        }

        session_destroy();
        header(self::REDIRECT_LOGIN);
        exit;
    }
}

