<?php

declare(strict_types=1);

namespace PlexStats\Presentation\Controller;

/**
 * Renderiza el dashboard principal.
 */
final class DashboardController
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function show(): void
    {
        $currentYear  = (int)date('Y');
        $years        = range($currentYear, (int)$this->config['start_year']);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        extract(compact('currentYear', 'years', 'currentUserId'), EXTR_SKIP);
        require_once __DIR__ . '/../View/dashboard.php';
    }
}
