<?php

namespace App\Console\Commands;

use App\Domains\Planning\Services\RestockingAlertService;
use Illuminate\Console\Command;

/**
 * Scheduled restocking-alert evaluation (CLAUDE.md section 53, "Evaluate
 * alerts on schedule"). Complements the on-demand /restocking-alerts/evaluate
 * endpoint used right after a specific operational change.
 */
class EvaluateRestockingAlerts extends Command
{
    protected $signature = 'restocking:evaluate-alerts';

    protected $description = 'Evaluate all active reorder policies and update restocking alerts.';

    public function handle(RestockingAlertService $alertService): int
    {
        $alerts = $alertService->evaluateAll();

        $this->info("Evaluated reorder policies; {$alerts->count()} alert(s) are currently active or acknowledged.");

        return self::SUCCESS;
    }
}
