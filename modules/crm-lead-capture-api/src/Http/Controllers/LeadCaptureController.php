<?php

declare(strict_types=1);

namespace Liberu\CRM\LeadCapture\Api\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\CRM\LeadCapture\Actions\CaptureLead;
use Liberu\CRM\LeadCapture\Actions\CreateCaptureForm;
use Liberu\CRM\LeadCapture\Actions\CreateCaptureQrCode;
use Liberu\CRM\LeadCapture\Actions\RecordReferral;
use Liberu\CRM\LeadCapture\Actions\SubmitCaptureForm;
use Liberu\CRM\LeadCapture\Actions\UpdateCaptureStatus;
use Liberu\CRM\LeadCapture\Models\CaptureForm;
use Liberu\CRM\LeadCapture\Models\CaptureQrCode;
use Liberu\CRM\LeadCapture\Models\CaptureReferral;
use Liberu\CRM\LeadCapture\Models\LeadCapture;
use Liberu\CRM\LeadCapture\Services\CaptureReport;

final class LeadCaptureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $captures = $this->owned()->when($request->string('kind')->toString() !== '', fn ($query) => $query->where('kind', $request->string('kind')))->when($request->string('status')->toString() !== '', fn ($query) => $query->where('status', $request->string('status')))->latest('captured_at')->paginate(min(max($request->integer('page.size', 25), 1), 100));

        return response()->json(['data' => $captures->through(fn ($capture): array => $this->resource(LeadCapture::query()->findOrFail($capture->getKey()))), 'meta' => ['current_page' => $captures->currentPage(), 'last_page' => $captures->lastPage(), 'per_page' => $captures->perPage(), 'total' => $captures->total()]]);
    }

    public function store(Request $request, CaptureLead $action): JsonResponse
    {
        $data = $request->validate(['kind' => 'required|string', 'name' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:80', 'source' => 'nullable|string|max:120', 'source_medium' => 'nullable|string|max:120', 'source_campaign' => 'nullable|string|max:160', 'external_id' => 'nullable|string|max:180', 'dedupe_key' => 'nullable|string|max:255', 'source_metadata' => 'nullable|array', 'payload' => 'nullable|array', 'provenance' => 'nullable|array']);
        $capture = $action->execute($this->teamId(), auth()->id(), $data);

        return response()->json(['data' => $this->resource($capture)], 201);
    }

    public function show(LeadCapture $capture): JsonResponse
    {
        abort_unless($capture->team_id === $this->teamId(), 404);

        return response()->json(['data' => $this->resource($capture)]);
    }

    public function update(Request $request, LeadCapture $capture, UpdateCaptureStatus $action): JsonResponse
    {
        abort_unless($capture->team_id === $this->teamId(), 404);
        $data = $request->validate(['status' => 'required|string', 'failure_reason' => 'nullable|string|max:2000']);

        return response()->json(['data' => $this->resource($action->execute($capture, $data['status'], $data['failure_reason'] ?? null))]);
    }

    public function forms(): JsonResponse
    {
        return response()->json(['data' => CaptureForm::query()->where('team_id', $this->teamId())->latest()->get()->map(fn (CaptureForm $form): array => $this->formResource($form))]);
    }

    public function createForm(Request $request, CreateCaptureForm $action): JsonResponse
    {
        $data = $request->validate(['kind' => 'required|in:form,survey', 'name' => 'required|string|max:255', 'slug' => 'required|string|max:180', 'schema' => 'required|array|min:1', 'settings' => 'nullable|array', 'status' => 'nullable|in:draft,published,archived']);

        return response()->json(['data' => $this->formResource($action->execute($this->teamId(), auth()->id(), $data))], 201);
    }

    public function submitForm(Request $request, CaptureForm $form, SubmitCaptureForm $action): JsonResponse
    {
        abort_unless($form->team_id === $this->teamId(), 404);
        $data = $request->validate(['payload' => 'required|array']);

        return response()->json(['data' => $this->resource($action->execute($form, auth()->id(), $data['payload']))], 201);
    }

    public function qrCodes(): JsonResponse
    {
        return response()->json(['data' => CaptureQrCode::query()->where('team_id', $this->teamId())->latest()->get()]);
    }

    public function createQrCode(Request $request, CreateCaptureQrCode $action): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:100', 'destination' => 'required|url|max:2048', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $action->execute($this->teamId(), auth()->id(), $data)], 201);
    }

    public function referrals(): JsonResponse
    {
        return response()->json(['data' => CaptureReferral::query()->where('team_id', $this->teamId())->latest()->get()]);
    }

    public function recordReferral(Request $request, RecordReferral $action): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:100', 'referrer_type' => 'nullable|string|max:120', 'referrer_id' => 'nullable|integer', 'referred_type' => 'nullable|string|max:120', 'referred_id' => 'nullable|integer', 'status' => 'nullable|string|max:24', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $action->execute($this->teamId(), auth()->id(), $data)], 201);
    }

    public function report(Request $request, CaptureReport $report): JsonResponse
    {
        $from = now()->parse($request->input('from', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->input('until', now()->toDateString()))->endOfDay();

        return response()->json(['data' => $report->summarize($this->teamId(), $from, $until)]);
    }

    private function teamId(): int
    {
        $teamId = auth()->user()?->current_team_id;
        abort_unless($teamId !== null, 403);

        return (int) $teamId;
    }

    private function owned(): Builder
    {
        return LeadCapture::query()->where('team_id', $this->teamId());
    }

    /** @return array<string, mixed> */
    private function resource(LeadCapture $capture): array
    {
        return ['id' => (string) $capture->getKey(), 'type' => 'crm-lead-capture', 'attributes' => $capture->only(['kind', 'status', 'name', 'email', 'phone', 'source', 'source_medium', 'source_campaign', 'external_id', 'source_metadata', 'payload', 'provenance', 'captured_at', 'processed_at', 'failure_reason'])];
    }

    /** @return array<string, mixed> */
    private function formResource(CaptureForm $form): array
    {
        return ['id' => (string) $form->getKey(), 'type' => 'crm-lead-capture-form', 'attributes' => $form->only(['kind', 'name', 'slug', 'status', 'schema', 'settings', 'submissions_count'])];
    }
}
