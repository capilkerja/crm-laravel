<?php

declare(strict_types=1);

namespace Liberu\CRM\TemplatesAndSnapshotsApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Liberu\CRM\TemplatesAndSnapshots\Actions\CreateSnapshot;
use Liberu\CRM\TemplatesAndSnapshots\Actions\InstallSnapshot;
use Liberu\CRM\TemplatesAndSnapshots\Actions\ShareSnapshot;
use Liberu\CRM\TemplatesAndSnapshots\Queries\SnapshotQuery;

final class SnapshotController extends Controller
{
    private function team(Request $r): int
    {
        abort_unless($r->user()?->current_team_id !== null, 403);

        return (int) $r->user()->current_team_id;
    }

    public function index(Request $r, SnapshotQuery $q)
    {
        return response()->json($q->list($this->team($r)));
    }

    public function show(Request $r, int $snapshot, SnapshotQuery $q)
    {
        return response()->json(['data' => $q->find($this->team($r), $snapshot)]);
    }

    public function store(Request $r, CreateSnapshot $a)
    {
        return response()->json(['data' => $a->execute($this->team($r), (int) $r->user()->id, $r->all())], 201);
    }

    public function install(Request $r, int $snapshot, InstallSnapshot $a)
    {
        return response()->json(['data' => $a->execute($this->team($r), (int) $r->user()->id, $snapshot)]);
    }

    public function share(Request $r, int $snapshot, ShareSnapshot $a)
    {
        return response()->json(['data' => ['token' => $a->execute($this->team($r), (int) $r->user()->id, $snapshot)]]);
    }
}
