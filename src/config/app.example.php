<?php

declare(strict_types=1);

// Copia este archivo como config/app.php y rellena los valores.
return [
    // URL base de tu servidor Overseerr (sin barra final)
    'overseerr_url'     => 'https://your-overseerr-server/api/v1',

    // API Key → Overseerr > Ajustes > General > API Key
    'overseerr_api_key' => '',

    // URL pública de esta app (sin barra final) — usada para el callback de Plex OAuth
    'app_url'           => 'http://localhost:3000',

    // Identificador único de esta app ante Plex (puede ser cualquier string estable)
    'plex_client_id'    => 'plex-stats-dashboard',

    // Nombre de la app que verá el usuario en la pantalla de autorización de Plex
    'plex_app_name'     => 'PlexStats',

    // Año más antiguo que aparecerá en el selector
    'start_year'        => 2020,

    // URL base de tu servidor Tautulli (sin barra final, sin /api/v2)
    // Deja vacío si no usas Tautulli
    'tautulli_url'      => '',

    // API Key → Tautulli > Ajustes > Web Interface > API Key
    'tautulli_api_key'  => '',
];
