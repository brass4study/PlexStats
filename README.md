# PlexStats

Dashboard de estadísticas de peticiones de Plex. Muestra cuántas peticiones ha realizado cada usuario en Overseerr, con soporte de filtro por año, búsqueda y tabla interactiva.

## Características

- Autenticación exclusiva via Plex OAuth (flujo PIN)
- Tabla de usuarios con conteo de peticiones por año y totales históricos
- Filtro por año configurable
- Badges de rol (Admin, Local, Plex)
- Resaltado del usuario logueado
- Caché de 5 minutos (sesión PHP) para no saturar la API de Overseerr

---

## Requisitos

- PHP 8.0 o superior con extensión **cURL**
- Apache 2.4+ con `mod_rewrite` activado
- Acceso a una instancia de [Overseerr](https://overseerr.dev/)
- Una aplicación Plex registrada (Client ID)

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/brass4study/PlexStats.git
cd PlexStats
```

### 2. Configurar la aplicación

Copia el archivo de ejemplo y edítalo con tus datos:

```bash
cp config/app.example.php config/app.php
```

Edita `config/app.php`:

```php
<?php

return [
    'overseerr_url'     => 'https://tu-servidor-overseerr/api/v1',
    'overseerr_api_key' => 'TU_API_KEY_DE_OVERSEERR',
    'app_url'           => 'https://tu-dominio.com',
    'plex_client_id'    => 'plexstats-dashboard',
    'plex_app_name'     => 'PlexStats',
    'start_year'        => 2020,
];
```

| Clave | Descripción |
|---|---|
| `overseerr_url` | URL base de Overseerr, incluyendo `/api/v1` |
| `overseerr_api_key` | API Key de Overseerr (Ajustes → General) |
| `app_url` | URL pública de esta aplicación (usada para el callback OAuth de Plex) |
| `plex_client_id` | Identificador único estable para tu aplicación en Plex |
| `plex_app_name` | Nombre que verá el usuario en la pantalla de autorización de Plex |
| `start_year` | Año más antiguo disponible en el selector de año |

### 3. Configurar el servidor web

El directorio raíz del virtual host debe apuntar a la raíz del proyecto (donde está `index.php`). El archivo `.htaccess` incluido ya gestiona el enrutamiento y protege los directorios sensibles.

Ejemplo de virtual host en Apache:

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/PlexStats

    <Directory /var/www/PlexStats>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Estructura del proyecto

```
/
├── index.php               # Front controller
├── bootstrap.php           # Inyección de dependencias y arranque
├── config/
│   ├── app.php             # Configuración local (gitignored)
│   └── app.example.php     # Plantilla de configuración
├── public/
│   └── assets/
│       ├── css/style.css
│       └── js/app.js
└── src/
    ├── Application/
    │   └── UseCase/
    │       └── GetUsersWithRequestStats.php
    ├── Domain/
    │   ├── Entity/          # User, UserRequestStat
    │   ├── Exception/       # PlexException, OverseerrException
    │   └── Repository/      # UserRepositoryInterface
    ├── Infrastructure/
    │   ├── Auth/            # PlexAuthService, OverseerrAuthService
    │   ├── Cache/           # SessionCache
    │   ├── Http/            # OverseerrHttpClient
    │   └── Repository/      # OverseerrUserRepository, CachedUserRepository
    └── Presentation/
        ├── Router.php
        ├── Controller/      # AuthController, DashboardController, UsersApiController
        ├── Middleware/      # AuthMiddleware
        └── View/            # dashboard.php, login.php
```

---

## Flujo de autenticación

1. El usuario hace clic en **Login with Plex**
2. `GET /auth/plex/init` → se crea un PIN en `plex.tv` y se redirige al usuario a la pantalla de autorización de Plex
3. El usuario autoriza la aplicación en Plex
4. `GET /auth/plex/callback` → se verifica el PIN, se obtiene el `authToken` y se valida contra Overseerr (`POST /auth/plex`)
5. Si Overseerr confirma el usuario, se inicia la sesión PHP y se redirige al dashboard

---

## Rutas disponibles

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| GET | `/` | Dashboard principal | ✅ |
| GET | `/login` | Pantalla de login | ❌ |
| GET | `/auth/plex/init` | Inicia el flujo OAuth con Plex | ❌ |
| GET | `/auth/plex/callback` | Callback OAuth de Plex | ❌ |
| GET | `/logout` | Cierra la sesión | ❌ |
| GET | `/api/users?year=YYYY` | JSON con estadísticas de usuarios | ✅ |

---

## Tecnologías

**Backend**
- PHP 8.0+, arquitectura Clean Architecture (sin frameworks externos)
- Autoloading PSR-4 personalizado

**Frontend**
- Bootstrap 5.3
- DataTables 1.13 + adaptador Bootstrap 5
- Font Awesome 6
- jQuery 3.7

---

## Seguridad

- Las cookies de sesión se configuran con `httponly`, `samesite=Lax` y `secure` (automático cuando hay HTTPS)
- Se ejecuta `session_regenerate_id(true)` tras cada login exitoso
- Las API keys nunca se exponen al frontend
- Los directorios `src/`, `config/` y `bootstrap.php` están protegidos por `.htaccess` (HTTP 403)
