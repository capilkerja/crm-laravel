<?php

declare(strict_types=1);

namespace Liberu\CRM\TerritoriesAndOwnership\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\CRM\TerritoriesAndOwnership\Models\TerritoryRule;
use Liberu\CRM\TerritoriesAndOwnership\Services\TerritoryPolicy;

final class UpsertTerritoryRule
{
    public function execute(int $teamId, int $actorId, array $data): TerritoryRule
    {
        if (! app(TerritoryPolicy::class)->canManage($teamId, $actorId)) {
            throw ValidationException::withMessages(['authorization' => 'Not authorized.']);
        }validator($data, ['name' => ['required', 'string', 'max:255'], 'book_of_business' => ['nullable', 'string', 'max:255'], 'criteria' => ['nullable', 'array'], 'members' => ['required', 'array'], 'members.*' => ['integer'], 'capacity' => ['nullable', 'integer', 'min:1'], 'active' => ['boolean']])->validate();

        return DB::transaction(function () use ($teamId, $data) {
            $rule = TerritoryRule::query()->updateOrCreate(['team_id' => $teamId, 'name' => $data['name']], array_merge($data, ['team_id' => $teamId]));

            return $rule->fresh();
        });
    }
}
