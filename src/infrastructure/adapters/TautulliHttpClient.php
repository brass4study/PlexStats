<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Adapters;

use PlexStats\Domain\Errors\TautulliException;

final class TautulliHttpClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    /**
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function get(string $cmd, array $params = []): array
    {
        $query = http_build_query(array_merge(
            ['apikey' => $this->apiKey, 'cmd' => $cmd, 'output_format' => 'json'],
            $params,
        ));

        $url = rtrim($this->baseUrl, '/') . '/api/v2?' . $query;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            throw new TautulliException('cURL error: ' . curl_error($ch));
        }

        if ($code < 200 || $code >= 300) {
            throw new TautulliException("Tautulli API respondió con HTTP {$code}.");
        }

        $json = json_decode((string)$body, true);

        if (!is_array($json)) {
            throw new TautulliException('Respuesta JSON inválida de Tautulli.');
        }

        return $json['response']['data'] ?? $json;
    }
}