<?php

declare(strict_types=1);

namespace Liberu\CRM\WhiteLabel\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\CRM\WhiteLabel\Actions\UpdateWhiteLabelSettings;
use Liberu\CRM\WhiteLabel\Queries\WhiteLabelSettingsQuery;

final class WhiteLabelController extends Controller
{
    public function show(Request $request, WhiteLabelSettingsQuery $query): JsonResponse
    {
        $settings = $query->forTeam($this->teamId($request));

        return response()->json(['data' => $this->resource($settings)]);
    }

    public function update(Request $request, UpdateWhiteLabelSettings $update, WhiteLabelSettingsQuery $query): JsonResponse
    {
        $data = $request->validate(['brand_name' => ['sometimes', 'nullable', 'string', 'max:255'], 'custom_domain' => ['sometimes', 'nullable', 'string', 'max:255'], 'theme' => ['sometimes', 'string', 'max:100'], 'email_settings' => ['sometimes', 'nullable', 'array'], 'application_settings' => ['sometimes', 'nullable', 'array'], 'client_experience' => ['sometimes', 'nullable', 'array'], 'provider' => ['sometimes', 'string', 'max:100'], 'show_platform_attribution' => ['sometimes', 'boolean']]);
        $current = $query->forTeam($this->teamId($request));
        $updated = $update->execute($this->teamId($request), (int) $request->user()->getKey(), array_merge($current->only(['theme', 'provider', 'show_platform_attribution']), $data), $this->expectedVersion($request));

        return response()->json(['data' => $this->resource($updated)]);
    }

    private function teamId(Request $request): int
    {
        $id = $request->user()?->current_team_id;
        abort_unless($id !== null, 403, 'A current team is required.');

        return (int) $id;
    }

    private function expectedVersion(Request $request): ?int
    {
        $header = $request->header('If-Match');
        if ($header === null) {
            return null;
        }

        $version = trim($header, '" W/');
        abort_unless(ctype_digit($version), 409, 'If-Match must contain a settings version.');

        return (int) $version;
    }

    /** @return array<string, mixed> */
    private function resource(object $settings): array
    {
        return ['id' => (string) $settings->getKey(), 'type' => 'crm-white-label-settings', 'attributes' => $settings->only(['team_id', 'brand_name', 'custom_domain', 'theme', 'email_settings', 'application_settings', 'client_experience', 'provider', 'show_platform_attribution', 'version', 'created_at', 'updated_at'])];
    }
}
