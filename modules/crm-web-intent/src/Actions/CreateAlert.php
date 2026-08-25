<?php

declare(strict_types=1);

namespace Liberu\CRM\WebIntent\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\CRM\WebIntent\Models\WebIntentAlert;
use Liberu\CRM\WebIntent\Services\WebIntentAudit;
use Liberu\CRM\WebIntent\Services\WebIntentPolicy;

final class CreateAlert
{
    public function execute(int $teamId, int $actorId, string $visitorKey, string $title, string $severity, ?int $visitId, ?string $details, WebIntentPolicy $policy): WebIntentAlert
    {
        abort_unless($policy->canManage($teamId, $actorId), 403, 'You cannot manage web-intent alerts for this team.');
        validator(compact('title', 'severity'), ['title' => ['required', 'string', 'max:255'], 'severity' => ['required', 'in:low,normal,high,critical']])->validate();

        return DB::transaction(function () use ($teamId, $actorId, $visitorKey, $title, $severity, $visitId, $details): WebIntentAlert {
            $alert = WebIntentAlert::query()->create(['team_id' => $teamId, 'visitor_key' => $visitorKey, 'title' => $title, 'severity' => $severity, 'visit_id' => $visitId, 'details' => $details, 'triggered_at' => now(), 'status' => 'open']);
            app(WebIntentAudit::class)->record($teamId, $actorId, 'alert_created', ['visitor_key' => $visitorKey, 'severity' => $severity]);

            return $alert;
        });
    }
}
