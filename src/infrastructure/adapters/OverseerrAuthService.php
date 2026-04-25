<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Adapters;

use PlexStats\Domain\Errors\OverseerrException;

/**
 * Autentica un token de Plex contra Overseerr.
 * Si Overseerr acepta el token devuelve el objeto User de Overseerr.
 */
final class OverseerrAuthService
{
    public function __construct(
        private readonly string $overseerrUrl,
        private readonly string $overseerrApiKey,
    ) {}

    /**
     * @return array<string, mixed>  El usuario de Overseerr si la autenticación es correcta.
     * @throws OverseerrException      Si Overseerr rechaza el token.
     */
    public function authenticateWithPlexToken(string $token): array
    {
        $url = rtrim($this->overseerrUrl, '/') . '/auth/plex';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['authToken' => $token], JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Api-Key: ' . $this->overseerrApiKey,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            throw new OverseerrException('cURL error: ' . curl_error($ch));
        }

        if ($code === 401 || $code === 403) {
            throw new OverseerrException('Tu cuenta de Plex no tiene acceso a este servidor de Overseerr.');
        }

        if ($code !== 200) {
            throw new OverseerrException("Overseerr respondió con HTTP {$code}.");
        }

        $user = json_decode((string)$body, true) ?? [];

        if (empty($user['id'])) {
            throw new OverseerrException('Respuesta de usuario inválida de Overseerr.');
        }

        return $user;
    }
}
