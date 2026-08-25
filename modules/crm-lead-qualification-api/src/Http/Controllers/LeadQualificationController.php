<?php

declare(strict_types=1);

namespace Liberu\CRM\LeadQualification\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\CRM\LeadQualification\Actions\ConvertQualification;
use Liberu\CRM\LeadQualification\Actions\CreateFramework;
use Liberu\CRM\LeadQualification\Actions\CreateQualification;
use Liberu\CRM\LeadQualification\Actions\DisqualifyQualification;
use Liberu\CRM\LeadQualification\Actions\EnrollNurture;
use Liberu\CRM\LeadQualification\Actions\EvaluateQualification;
use Liberu\CRM\LeadQualification\Actions\TransitionLifecycle;
use Liberu\CRM\LeadQualification\Actions\UpdateScores;
use Liberu\CRM\LeadQualification\Models\LeadQualification;
use Liberu\CRM\LeadQualification\Models\QualificationFramework;
use Liberu\CRM\LeadQualification\Services\QualificationReport;
use Liberu\Foundation\ApiAccess\Support\IdempotencyStore;

final class LeadQualificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->owned($request)->when($request->query('status'), fn ($query, $status) => $query->where('qualification_status', $status))->when($request->query('lifecycle_stage'), fn ($query, $stage) => $query->where('lifecycle_stage', $stage))->when($request->query('subject_type'), fn ($query, $type) => $query->where('subject_type', $type))->latest();
        $qualifications = $query->paginate(min(max((int) $request->query('page[size]', 25), 1), 100));

        return response()->json(['data' => $qualifications->through(fn (LeadQualification $qualification): array => $this->resource($qualification)), 'meta' => ['current_page' => $qualifications->currentPage(), 'last_page' => $qualifications->lastPage()], 'links' => ['self' => $request->fullUrl()]]);
    }

    public function store(Request $request, CreateQualification $create, IdempotencyStore $idempotency): JsonResponse
    {
        $data = $request->validate(['subject_type' => ['required', 'string', 'max:160'], 'subject_id' => ['required', 'integer', 'min:1'], 'framework_id' => ['nullable', 'integer', 'min:1'], 'lifecycle_stage' => ['nullable', 'string', 'max:48'], 'fit_score' => ['nullable', 'integer', 'between:0,100'], 'engagement_score' => ['nullable', 'integer', 'between:0,100'], 'metadata' => ['nullable', 'array']]);
        $replay = $this->replayIdempotent($request, $idempotency);
        if ($replay !== null) {
            return $replay;
        }
        $qualification = $create->execute($this->teamId($request), $request->user()->getKey(), $data);

        return $this->completeIdempotent($request, $idempotency, response()->json(['data' => $this->resource($qualification)], 201));
    }

    public function show(Request $request, int $qualification): JsonResponse
    {
        return response()->json(['data' => $this->resource($this->owned($request)->findOrFail($qualification))]);
    }

    public function updateScores(Request $request, int $qualification, UpdateScores $update): JsonResponse
    {
        $model = $this->owned($request)->findOrFail($qualification);
        $this->assertVersion($request, $model);
        $data = $request->validate(['fit_score' => ['required', 'integer', 'between:0,100'], 'engagement_score' => ['required', 'integer', 'between:0,100']]);

        return response()->json(['data' => $this->resource($update->execute($model, $request->user()->getKey(), $data))]);
    }

    public function evaluate(Request $request, int $qualification, EvaluateQualification $evaluate, IdempotencyStore $idempotency): JsonResponse
    {
        $replay = $this->replayIdempotent($request, $idempotency);
        if ($replay !== null) {
            return $replay;
        }
        $response = response()->json(['data' => $this->resource($evaluate->execute($this->owned($request)->findOrFail($qualification), $request->user()->getKey()))]);

        return $this->completeIdempotent($request, $idempotency, $response);
    }

    public function transition(Request $request, int $qualification, TransitionLifecycle $transition): JsonResponse
    {
        $data = $request->validate(['lifecycle_stage' => ['required', 'string', 'max:48'], 'reason' => ['nullable', 'string', 'max:255']]);

        return response()->json(['data' => $this->resource($transition->execute($this->owned($request)->findOrFail($qualification), $request->user()->getKey(), $data['lifecycle_stage'], $data['reason'] ?? null))]);
    }

    public function disqualify(Request $request, int $qualification, DisqualifyQualification $disqualify): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $this->resource($disqualify->execute($this->owned($request)->findOrFail($qualification), $request->user()->getKey(), $data['reason']))]);
    }

    public function nurture(Request $request, int $qualification, EnrollNurture $enroll): JsonResponse
    {
        $data = $request->validate(['sequence' => ['required', 'string', 'max:160'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'metadata' => ['nullable', 'array']]);
        $enrollment = $enroll->execute($this->owned($request)->findOrFail($qualification), $request->user()->getKey(), $data);

        return response()->json(['data' => ['id' => (string) $enrollment->getKey(), 'type' => 'crm-lead-qualification-nurture', 'attributes' => $enrollment->only(['status', 'sequence', 'starts_at', 'ends_at', 'metadata'])]], 201);
    }

    public function convert(Request $request, int $qualification, ConvertQualification $convert): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        return response()->json(['data' => $this->resource($convert->execute($this->owned($request)->findOrFail($qualification), $request->user()->getKey(), $data['reason'] ?? null))]);
    }

    public function frameworks(Request $request): JsonResponse
    {
        return response()->json(['data' => QualificationFramework::query()->where('team_id', $this->teamId($request))->latest()->get()->map(fn (QualificationFramework $framework): array => ['id' => (string) $framework->getKey(), 'type' => 'crm-lead-qualification-framework', 'attributes' => $framework->only(['name', 'status', 'mql_threshold', 'pql_threshold', 'sql_threshold', 'service_qualified_threshold', 'rules', 'settings'])])]);
    }

    public function storeFramework(Request $request, CreateFramework $create): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'status' => ['nullable', 'in:draft,active,archived'], 'mql_threshold' => ['nullable', 'integer', 'between:0,100'], 'pql_threshold' => ['nullable', 'integer', 'between:0,100'], 'sql_threshold' => ['nullable', 'integer', 'between:0,100'], 'service_qualified_threshold' => ['nullable', 'integer', 'between:0,100'], 'rules' => ['nullable', 'array'], 'settings' => ['nullable', 'array']]);
        $framework = $create->execute($this->teamId($request), $request->user()->getKey(), $data);

        return response()->json(['data' => ['id' => (string) $framework->getKey(), 'type' => 'crm-lead-qualification-framework', 'attributes' => $framework->only(['name', 'status', 'mql_threshold', 'pql_threshold', 'sql_threshold', 'service_qualified_threshold', 'rules', 'settings'])]], 201);
    }

    public function report(Request $request, QualificationReport $report): JsonResponse
    {
        $data = $request->validate(['from' => ['required', 'date'], 'until' => ['required', 'date', 'after_or_equal:from']]);

        return response()->json(['data' => $report->summarize($this->teamId($request), now()->parse($data['from']), now()->parse($data['until']))]);
    }

    private function owned(Request $request)
    {
        return LeadQualification::query()->where('team_id', $this->teamId($request));
    }

    private function teamId(Request $request): int
    {
        $teamId = $request->user()?->current_team_id;
        abort_unless($teamId !== null, 403, 'A current team is required.');

        return (int) $teamId;
    }

    private function assertVersion(Request $request, LeadQualification $qualification): void
    {
        $header = $request->header('If-Match');
        if ($header === null) {
            return;
        }

        $version = trim($header, '" W/');
        abort_unless(ctype_digit($version) && (int) $version === $qualification->version, 409, 'The qualification version is stale.');
    }

    private function replayIdempotent(Request $request, IdempotencyStore $idempotency): ?JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if ($key === null) {
            return null;
        }

        abort_unless(strlen($key) <= 128 && trim($key) !== '', 422, 'Idempotency-Key must be a non-empty value of 128 characters or fewer.');
        $existing = $idempotency->begin((string) $request->user()->getKey(), $key, (string) $request->getContent());
        if ($existing === null) {
            return null;
        }
        if ($existing->response_body === null) {
            abort(409, 'The idempotent request is still being processed.');
        }

        return response()->json(json_decode($existing->response_body, true, 512, JSON_THROW_ON_ERROR), (int) $existing->response_status);
    }

    private function completeIdempotent(Request $request, IdempotencyStore $idempotency, JsonResponse $response): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if ($key !== null) {
            $idempotency->complete((string) $request->user()->getKey(), $key, $response->getStatusCode(), (string) $response->getContent());
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function resource(LeadQualification $qualification): array
    {
        return ['id' => (string) $qualification->getKey(), 'type' => 'crm-lead-qualification', 'attributes' => $qualification->only(['subject_type', 'subject_id', 'framework_id', 'lifecycle_stage', 'fit_score', 'engagement_score', 'total_score', 'qualification_status', 'disqualification_reason', 'nurture_until', 'converted_at', 'version', 'metadata', 'created_at', 'updated_at'])];
    }
}
