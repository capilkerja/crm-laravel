<?php

declare(strict_types=1);

namespace Liberu\CRM\UnifiedConversationsApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Liberu\CRM\UnifiedConversations\Actions\OpenConversation;
use Liberu\CRM\UnifiedConversations\Actions\SendMessage;
use Liberu\CRM\UnifiedConversations\Queries\ConversationQuery;

final class ConversationController extends Controller
{
    private function team(Request $r): int
    {
        abort_unless($r->user()?->current_team_id !== null, 403);

        return (int) $r->user()->current_team_id;
    }

    public function index(Request $r, ConversationQuery $q)
    {
        return response()->json($q->list($this->team($r)));
    }

    public function store(Request $r, OpenConversation $a)
    {
        return response()->json(['data' => $a->execute($this->team($r), (int) $r->user()->id, $r->all())], 201);
    }

    public function message(Request $r, int $conversation, SendMessage $a)
    {
        return response()->json(['data' => $a->execute($this->team($r), (int) $r->user()->id, $conversation, $r->all())], 201);
    }
}
