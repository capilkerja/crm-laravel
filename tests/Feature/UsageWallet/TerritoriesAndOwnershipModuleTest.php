<?php

declare(strict_types=1);

namespace Tests\Feature\UsageWallet;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\CRM\TerritoriesAndOwnership\Actions\AssignOwner;
use Liberu\CRM\TerritoriesAndOwnership\Actions\UpsertTerritoryRule;
use Liberu\CRM\TerritoriesAndOwnership\Models\OwnershipHistory;
use Liberu\CRM\TerritoriesAndOwnership\Models\TerritoryRule;
use Tests\TestCase;

final class TerritoriesAndOwnershipModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_and_ownership_history_are_team_scoped(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $other = Team::factory()->create(['user_id' => User::factory()->create()->id]);
        $rule = app(UpsertTerritoryRule::class)->execute($team->id, $owner->id, ['name' => 'North', 'book_of_business' => 'Enterprise', 'members' => [$owner->id], 'criteria' => ['region' => 'north'], 'capacity' => 5]);
        $history = app(AssignOwner::class)->execute($team->id, $owner->id, 'lead', 12, null, $owner->id, 'round_robin');
        self::assertSame('North', $rule->getAttribute('name'));
        self::assertSame($team->id, $history->getAttribute('team_id'));
        self::assertSame(1, TerritoryRule::query()->where('team_id', $team->id)->count());
        self::assertSame(1, OwnershipHistory::query()->where('team_id', $team->id)->count());
        self::assertSame(0, TerritoryRule::query()->where('team_id', $other->id)->count());
    }
}
