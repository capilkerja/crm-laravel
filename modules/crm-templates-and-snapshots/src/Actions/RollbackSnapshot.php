<?php

declare(strict_types=1);

namespace Liberu\CRM\TemplatesAndSnapshots\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\CRM\TemplatesAndSnapshots\Models\SnapshotBundle;
use Liberu\CRM\TemplatesAndSnapshots\Models\SnapshotInstall;
use Liberu\CRM\TemplatesAndSnapshots\Services\SnapshotAudit;
use Liberu\CRM\TemplatesAndSnapshots\Services\SnapshotPolicy;

final class RollbackSnapshot
{
    public function execute(int $teamId, int $actorId, int $bundleId, int $version): SnapshotInstall
    {
        if (! app(SnapshotPolicy::class)->canManage($teamId, $actorId)) {
            throw ValidationException::withMessages(['authorization' => 'Not authorized.']);
        }$bundle = SnapshotBundle::query()->where('team_id', $teamId)->where('name', SnapshotBundle::query()->whereKey($bundleId)->value('name'))->where('version', $version)->firstOrFail();
        $install = SnapshotInstall::query()->where('team_id', $teamId)->where('bundle_id', $bundleId)->firstOrFail();
        $install->update(['version' => $version, 'status' => 'installed', 'installed_by' => $actorId]);
        app(SnapshotAudit::class)->record($teamId, $actorId, 'snapshot_rolled_back', ['bundle_id' => $bundleId, 'version' => $version]);

        return $install->fresh();
    }
}
