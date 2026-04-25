<?php

declare(strict_types=1);

$nsMap = [
    'PlexStats\\Application\\UseCases\\'                 => __DIR__ . '/src/application/use-cases/',
    'PlexStats\\Application\\Ports\\'                    => __DIR__ . '/src/application/ports/',
    'PlexStats\\Application\\Dto\\'                      => __DIR__ . '/src/application/dto/',
    'PlexStats\\Domain\\Entities\\'                      => __DIR__ . '/src/domain/entities/',
    'PlexStats\\Domain\\Errors\\'                        => __DIR__ . '/src/domain/errors/',
    'PlexStats\\Domain\\Services\\'                      => __DIR__ . '/src/domain/services/',
    'PlexStats\\Domain\\ValueObjects\\'                  => __DIR__ . '/src/domain/value-objects/',
    'PlexStats\\Infrastructure\\Adapters\\'              => __DIR__ . '/src/infrastructure/adapters/',
    'PlexStats\\Infrastructure\\Http\\Controllers\\'     => __DIR__ . '/src/infrastructure/http/controllers/',
    'PlexStats\\Infrastructure\\Http\\Routes\\'          => __DIR__ . '/src/infrastructure/http/routes/',
    'PlexStats\\Infrastructure\\Http\\'                  => __DIR__ . '/src/infrastructure/http/',
    'PlexStats\\Infrastructure\\Persistence\\InMemory\\' => __DIR__ . '/src/infrastructure/persistence/in-memory/',
    'PlexStats\\Infrastructure\\Persistence\\'           => __DIR__ . '/src/infrastructure/persistence/',
    'PlexStats\\Composition\\'                           => __DIR__ . '/src/composition/',
    'PlexStats\\Shared\\'                                => __DIR__ . '/src/shared/',
];

spl_autoload_register(function (string $class) use ($nsMap): void {
    foreach ($nsMap as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file     = $baseDir . $relative . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
            return;
        }
    }
});

use PlexStats\Composition\AppFactory;

AppFactory::create()->dispatch(
    (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string)($_SERVER['REQUEST_URI']    ?? '/'),
);
