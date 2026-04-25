<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Adapters;

use PlexStats\Domain\Errors\PlexException;

/**
 * Gestiona el flujo OAuth de Plex:
 *  1. Crear un PIN en plex.tv
 *  2. Construir la URL de autorización de Plex
 *  3. Recuperar el PIN (con el authToken una vez que el usuario autoriza)
 */
final class PlexAuthService
{
    private const PLEX_API = 'https://plex.tv/api/v2';

    public function __construct(
        private readonly string $appName,
        private readonly string $clientId,
    ) {}

    /**
     * Solicita un PIN a plex.tv.
     * Devuelve ['id' => int, 'code' => string, ...]
     */
    public function createPin(): array
    {
        $ch = curl_init(self::PLEX_API . '/pins?strong=true');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => $this->buildHeaders(),
            CURLOPT_TIMEOUT        => 15,
        ]);

        [$body, $code] = $this->exec($ch);

        if ($code < 200 || $code >= 300) {
            throw new PlexException("Plex rechazó la creación del PIN (HTTP {$code}).");
        }

        $data = json_decode($body, true) ?? [];

        if (empty($data['id']) || empty($data['code'])) {
            throw new PlexException('Respuesta inválida de Plex al crear el PIN.');
        }

        return $data;
    }

    /**
     * Consulta el estado de un PIN. Cuando el usuario autoriza,
     * $result['authToken'] contendrá el token de Plex.
     */
    public function getPin(int $pinId): array
    {
        $ch = curl_init(self::PLEX_API . "/pins/{$pinId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->buildHeaders(),
            CURLOPT_TIMEOUT        => 15,
        ]);

        [$body, $code] = $this->exec($ch);

        if ($code < 200 || $code >= 300) {
            throw new PlexException("Plex rechazó la consulta del PIN (HTTP {$code}).");
        }

        return json_decode($body, true) ?? [];
    }

    /**
     * Construye la URL a la que hay que redirigir al usuario para que autorice.
     */
    public function buildAuthUrl(string $pinCode, string $callbackUrl): string
    {
        // El fragmento (#) es necesario para la web de Plex
        $query = http_build_query([
            'clientID'   => $this->clientId,
            'code'       => $pinCode,
            'forwardUrl' => $callbackUrl,
        ]);

        return 'https://app.plex.tv/auth#?' . $query;
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** @return list<string> */
    private function buildHeaders(): array
    {
        return [
            'Accept: application/json',
            'X-Plex-Product: '            . $this->appName,
            'X-Plex-Client-Identifier: ' . $this->clientId,
            'X-Plex-Version: 1.0',
        ];
    }

    /** @return array{string, int} [body, http_code] */
    private function exec(\CurlHandle $ch): array
    {
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            $err = curl_error($ch);
            throw new PlexException("cURL error: {$err}");
        }

        return [(string)$body, $code];
    }
}
