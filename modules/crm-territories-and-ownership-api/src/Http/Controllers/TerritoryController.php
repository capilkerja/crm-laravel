<?php

declare(strict_types=1);

namespace Liberu\CRM\TerritoriesAndOwnershipApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Liberu\CRM\TerritoriesAndOwnership\Actions\AssignOwner;
use Liberu\CRM\TerritoriesAndOwnership\Actions\UpsertTerritoryRule;
use Liberu\CRM\TerritoriesAndOwnership\Queries\TerritoryQuery;

final class TerritoryController extends Controller
{
    private function team(Request $r): int
    {
        abort_unless($r->user()?->current_team_id !== null, 403);

        return (int) $r->user()->current_team_id;
    }

    public function index(Request $r, TerritoryQuery $q)
    {
        return response()->json($q->rules($this->team($r)));
    }

    public function history(Request $r, TerritoryQuery $q)
    {
        return response()->json($q->history($this->team($r)));
    }

    public function store(Request $r, UpsertTerritoryRule $a)
    {
        return response()->json(['data' => $a->execute($this->team($r), (int) $r->user()->id, $r->all())], 201);
    }

    public function assign(Request $r, AssignOwner $a)
    {
        return response()->json(['data' => $a->execute($this->team($r), (int) $r->user()->id, (string) $r->input('subject_type'), (int) $r->input('subject_id'), $r->integer('previous_owner_id') ?: null, (int) $r->input('owner_id'), (string) $r->input('reason'))], 201);
    }
}
