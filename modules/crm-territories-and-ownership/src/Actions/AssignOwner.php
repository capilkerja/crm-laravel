<?php

declare(strict_types=1);

namespace Liberu\CRM\TerritoriesAndOwnership\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\CRM\TerritoriesAndOwnership\Models\OwnershipHistory;
use Liberu\CRM\TerritoriesAndOwnership\Services\TerritoryPolicy;

final class AssignOwner
{
    public function execute(int $teamId, int $actorId, string $subjectType, int $subjectId, ?int $previousOwnerId, int $ownerId, string $reason): OwnershipHistory
    {
        if (! app(TerritoryPolicy::class)->canManage($teamId, $actorId)) {
            throw ValidationException::withMessages(['authorization' => 'Not authorized.']);
        }validator(compact('subjectType', 'subjectId', 'ownerId', 'reason'), ['subjectType' => ['required', 'string', 'max:100'], 'subjectId' => ['required', 'integer'], 'ownerId' => ['required', 'integer'], 'reason' => ['required', 'string', 'max:255']])->validate();

        return DB::transaction(fn () => OwnershipHistory::query()->create(['team_id' => $teamId, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'previous_owner_id' => $previousOwnerId, 'owner_id' => $ownerId, 'reason' => $reason, 'actor_id' => $actorId]));
    }
}
