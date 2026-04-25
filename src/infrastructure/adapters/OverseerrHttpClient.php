<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Adapters;

use CurlHandle;
use PlexStats\Domain\Errors\OverseerrException;

/**
 * Cliente HTTP mínimo para la API de Overseerr.
 * Encapsula cURL y la autenticación via X-Api-Key.
 */
final class OverseerrHttpClient
{
    private CurlHandle $ch;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {
        $handle = curl_init();
        if ($handle === false) {
            throw new OverseerrException('No se pudo inicializar cURL.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'X-Api-Key: ' . $this->apiKey,
                'Accept: application/json',
            ],
        ]);

        $this->ch = $handle;
    }

    /**
     * @return array<string, mixed>
     * @throws OverseerrException  en caso de error de red o respuesta no 2xx
     */
    public function get(string $path): array
    {
        curl_setopt($this->ch, CURLOPT_URL, rtrim($this->baseUrl, '/') . $path);

        $body = curl_exec($this->ch);
        $code = (int)curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            throw new OverseerrException('cURL error: ' . curl_error($this->ch));
        }

        if ($code < 200 || $code >= 300) {
            throw new OverseerrException("Overseerr API respondió con HTTP {$code} en {$path}");
        }

        $data = json_decode((string)$body, true);

        if (!is_array($data)) {
            throw new OverseerrException("Respuesta JSON inválida de Overseerr en {$path}");
        }

        return $data;
    }

    public function __destruct()
    {
        // curl_close() no tiene efecto desde PHP 8.0 y está deprecado en 8.5
    }
}
