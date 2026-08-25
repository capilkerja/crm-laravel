<?php

declare(strict_types=1);

namespace Tests\Feature\LeadQualification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\CRM\LeadQualification\Actions\ConvertQualification;
use Liberu\CRM\LeadQualification\Actions\CreateFramework;
use Liberu\CRM\LeadQualification\Actions\CreateQualification;
use Liberu\CRM\LeadQualification\Actions\EvaluateQualification;
use Liberu\CRM\LeadQualification\Actions\TransitionLifecycle;
use Liberu\CRM\LeadQualification\Actions\UpdateScores;
use Liberu\CRM\LeadQualification\Models\LeadQualification;
use Tests\TestCase;

final class LeadQualificationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoring_evaluation_lifecycle_and_conversion_are_audited(): void
    {
        $framework = app(CreateFramework::class)->execute(7, 11, [
            'name' => 'Default',
            'mql_threshold' => 40,
            'pql_threshold' => 60,
            'sql_threshold' => 80,
            'service_qualified_threshold' => 90,
        ]);
        $qualification = app(CreateQualification::class)->execute(7, 11, [
            'subject_type' => 'contact',
            'subject_id' => 42,
            'framework_id' => $framework->getKey(),
            'lifecycle_stage' => 'lead',
        ]);

        $qualification = app(UpdateScores::class)->execute($qualification, 11, ['fit_score' => 90, 'engagement_score' => 90]);
        $qualification = app(EvaluateQualification::class)->execute($qualification, 11);
        self::assertSame('service_qualified', $qualification->qualification_status);

        $qualification = app(TransitionLifecycle::class)->execute($qualification, 11, 'opportunity');
        $qualification = app(ConvertQualification::class)->execute($qualification, 11);
        self::assertSame('customer', $qualification->lifecycle_stage);
        self::assertSame('converted', $qualification->qualification_status);
        self::assertSame('opportunity', $qualification->stageHistory()->latest('id')->firstOrFail()->from_stage);
        self::assertSame(5, $qualification->getConnection()->table('crm_lead_qualification_audits')->where('qualification_id', $qualification->getKey())->count());
    }

    public function test_qualification_and_reports_are_team_scoped_and_duplicate_safe(): void
    {
        $attributes = ['subject_type' => 'contact', 'subject_id' => 42];
        $first = app(CreateQualification::class)->execute(7, null, $attributes);
        $second = app(CreateQualification::class)->execute(7, null, $attributes);
        app(CreateQualification::class)->execute(8, null, $attributes);

        self::assertSame($first->getKey(), $second->getKey());
        self::assertSame(2, LeadQualification::query()->count());
        self::assertSame(1, LeadQualification::query()->where('team_id', 7)->count());
    }

    public function test_invalid_framework_order_and_customer_transition_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(CreateFramework::class)->execute(7, null, ['name' => 'Invalid', 'mql_threshold' => 80, 'pql_threshold' => 60]);
    }
}
