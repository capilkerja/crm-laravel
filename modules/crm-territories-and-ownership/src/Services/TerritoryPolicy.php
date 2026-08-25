<?php

declare(strict_types=1);

namespace Liberu\CRM\TerritoriesAndOwnership\Services;

use App\Models\Team;

final class TerritoryPolicy
{
    public function canManage(int $teamId, int $userId): bool
    {
        $team = Team::query()->find($teamId);

        return $team !== null && ((int) $team->user_id === $userId || $team->users()->whereKey($userId)->wherePivotIn('role', ['admin', 'manager'])->exists());
    }
}
