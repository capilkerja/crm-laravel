<?php

declare(strict_types=1);

namespace Liberu\CRM\LeadCapture\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\CRM\LeadCapture\Events\LeadCaptured;
use Liberu\CRM\LeadCapture\Models\LeadCapture;
use Liberu\CRM\LeadCapture\Services\CaptureDeduplicator;

final class CaptureLead
{
    private const KINDS = ['leads_inbox', 'manual', 'import', 'api', 'form', 'survey', 'qr_code', 'chat', 'call', 'advertisement', 'event', 'referral'];

    /** @param array<string, mixed> $attributes */
    public function execute(int $teamId, ?int $actorId, array $attributes): LeadCapture
    {
        $kind = (string) ($attributes['kind'] ?? 'manual');
        if (! in_array($kind, self::KINDS, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported lead capture channel.']);
        }
        if (blank($attributes['name'] ?? null) && blank($attributes['email'] ?? null) && blank($attributes['phone'] ?? null)) {
            throw ValidationException::withMessages(['identity' => 'A name, email, or phone number is required.']);
        }

        return DB::transaction(function () use ($teamId, $actorId, $attributes, $kind): LeadCapture {
            $attributes['dedupe_key'] ??= app(CaptureDeduplicator::class)->key($attributes);
            $capture = LeadCapture::query()->where('team_id', $teamId)->where('dedupe_key', $attributes['dedupe_key'])->first();
            if ($capture !== null) {
                return $capture;
            }
            $capture = LeadCapture::query()->create(array_merge($attributes, ['team_id' => $teamId, 'actor_id' => $actorId, 'kind' => $kind, 'status' => $attributes['status'] ?? 'received', 'captured_at' => $attributes['captured_at'] ?? now()]));
            LeadCaptured::dispatch($capture);

            return $capture->refresh();
        });
    }
}
