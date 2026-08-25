<?php

declare(strict_types=1);

namespace Liberu\CRM\UnifiedConversations\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\CRM\UnifiedConversations\Models\Conversation;
use Liberu\CRM\UnifiedConversations\Services\ConversationPolicy;

final class AssignConversation
{
    public function execute(int $teamId, int $actorId, int $conversationId, ?int $assignee): Conversation
    {
        if (! app(ConversationPolicy::class)->canManage($teamId, $actorId)) {
            throw ValidationException::withMessages(['authorization' => 'Not authorized.']);
        }$c = Conversation::query()->where('team_id', $teamId)->findOrFail($conversationId);
        $c->setAttribute('assigned_to', $assignee);
        $c->save();

        return $c->fresh();
    }
}
