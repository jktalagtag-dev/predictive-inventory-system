<?php

namespace App\Domains\Reporting\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Reporting\Models\ReportExport;

/**
 * Authorization is rechecked at every read, including download, per
 * REST_API_SPECIFICATION.md section 13.4 ("authorization is evaluated
 * again even for known export ID") — an export ID alone is never
 * sufficient to read or download it.
 */
class ReportExportPolicy
{
    public function create(User $actor): bool
    {
        return $actor->hasPermission('reports.export');
    }

    public function view(User $actor, ReportExport $reportExport): bool
    {
        if (! $actor->hasPermission('reports.export')) {
            return false;
        }

        if ($reportExport->branch_id !== null && ! $actor->canAccessBranch($reportExport->branch_id)) {
            return false;
        }

        return $reportExport->requested_by_user_id === $actor->id || $actor->hasRole('owner') || $actor->hasRole('manager');
    }

    public function download(User $actor, ReportExport $reportExport): bool
    {
        return $this->view($actor, $reportExport);
    }
}
