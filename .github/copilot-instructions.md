# PlexStats — Project Guidelines

## Architecture
- Clean Architecture: Domain → Application → Infrastructure → Presentation
- PSR-4 autoloading, namespace raíz `PlexStats\`, PHP 8+, sin frameworks externos
- Front controller: `index.php` → `bootstrap.php` → `src/Presentation/Router.php`
- Servidor Apache con `.htaccess`, rewrite a `index.php`

## Configuration
- Config en `src/config/app.php` (array PHP, nunca hardcoded). En `.gitignore`.
- Claves: `overseerr_url`, `overseerr_api_key`, `app_url`, `plex_client_id`, `plex_app_name`, `start_year`
- Plantilla sin datos sensibles: `src/config/app.example.php`

## Authentication
- Login exclusivamente via Plex OAuth (flujo PIN: `plex.tv/api/v2/pins`)
- Callback en `/auth/plex/callback`
- Overseerr: `POST /auth/plex` con body `{"authToken": "..."}` — el campo es `authToken`, NO `token`
- Sesión PHP: `$_SESSION['authenticated']`, `['user_id']`, `['user_name']`, `['user_avatar']`

## Overseerr API
- Base URL viene de `config['overseerr_url']` (ya incluye `/api/v1`)
- Autenticación via header `X-Api-Key`

## Frontend
- Bootstrap 5.3, DataTables 1.13 + bootstrap5, Font Awesome 6, jQuery 3.7
- Assets servidos desde `/public/assets/` (no `/assets/`)
- Color primario: azul Bootstrap `#0d6efd`
- `app.js` es una IIFE vanilla JS con `'use strict'`; función `esc()` para escapar HTML (XSS)
- Fila del usuario logueado: clase `row-me` en `<tr>`, degradado vertical CSS con `rgba(13,110,253,...)`
- Variable JS `CURRENT_USER_ID` emitida inline desde PHP en la vista, antes de cargar `app.js`

## PHP Conventions
- `declare(strict_types=1)` en todos los archivos
- Clases `final` por defecto; constructor property promotion con `readonly`
- No usar `curl_close()` (deprecado en PHP 8.4)
- No añadir docblocks, comentarios ni type hints en código no modificado

## Security
- Cookies de sesión: `httponly`, `samesite=Lax`, `secure` cuando hay HTTPS
- `session_regenerate_id(true)` tras login exitoso
- Nunca exponer API keys en JS ni en vistas

## Workflow
