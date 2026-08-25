<?php

declare(strict_types=1);

namespace Tests\Feature\LeadCapture;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\CRM\LeadCapture\Actions\CaptureLead;
use Liberu\CRM\LeadCapture\Actions\CreateCaptureForm;
use Liberu\CRM\LeadCapture\Actions\SubmitCaptureForm;
use Liberu\CRM\LeadCapture\Events\CaptureFormSubmitted;
use Liberu\CRM\LeadCapture\Events\LeadCaptured;
use Liberu\CRM\LeadCapture\Models\LeadCapture;
use Liberu\CRM\LeadCapture\Services\CaptureReport;
use Tests\TestCase;

final class LeadCaptureModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_channels_are_deduplicated_and_team_scoped(): void
    {
        Event::fake([LeadCaptured::class]);
        $action = app(CaptureLead::class);
        $first = $action->execute(7, 11, ['kind' => 'api', 'email' => 'person@example.test', 'source' => 'web', 'source_metadata' => ['utm_campaign' => 'launch']]);
        $same = $action->execute(7, 11, ['kind' => 'api', 'email' => 'person@example.test', 'source' => 'web']);
        $otherTeam = $action->execute(8, 11, ['kind' => 'api', 'email' => 'person@example.test', 'source' => 'web']);
        self::assertSame($first->getKey(), $same->getKey());
        self::assertNotSame($first->getKey(), $otherTeam->getKey());
        Event::assertDispatchedTimes(LeadCaptured::class, 2);
    }

    public function test_published_form_submission_validates_and_emits_event(): void
    {
        Event::fake([CaptureFormSubmitted::class]);
        $form = app(CreateCaptureForm::class)->execute(7, 11, ['kind' => 'survey', 'name' => 'Discovery', 'slug' => 'discovery', 'status' => 'published', 'schema' => [['name' => 'email', 'required' => true]]]);
        $capture = app(SubmitCaptureForm::class)->execute($form, 11, ['email' => 'person@example.test']);
        self::assertSame('survey', $capture->kind);
        self::assertDatabaseHas('crm_lead_capture_forms', ['id' => $form->getKey(), 'submissions_count' => 1]);
        Event::assertDispatched(CaptureFormSubmitted::class);
    }

    public function test_unpublished_form_and_empty_capture_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(CaptureLead::class)->execute(7, null, ['kind' => 'manual']);
    }

    public function test_capture_report_is_team_scoped(): void
    {
        app(CaptureLead::class)->execute(7, null, ['kind' => 'manual', 'email' => 'converted@example.test', 'status' => 'converted']);
        app(CaptureLead::class)->execute(8, null, ['kind' => 'manual', 'email' => 'other@example.test', 'status' => 'converted']);
        $report = app(CaptureReport::class)->summarize(7, now()->subDay(), now()->addDay());
        self::assertSame(1, $report['total']);
        self::assertSame(100.0, $report['conversion_rate']);
        self::assertSame(1, LeadCapture::query()->where('team_id', 7)->count());
    }
}
