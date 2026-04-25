<?php

declare(strict_types=1);

/**
 * Front controller — punto de entrada HTTP.
 */
$router = require_once __DIR__ . '/bootstrap.php';

$router->dispatch(
    (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string)($_SERVER['REQUEST_URI']    ?? '/'),
);
