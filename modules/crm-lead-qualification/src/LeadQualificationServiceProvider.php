<?php

declare(strict_types=1);

namespace Liberu\CRM\LeadQualification;

use Illuminate\Support\ServiceProvider;

final class LeadQualificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Services\QualificationScorer::class);
        $this->app->singleton(Services\QualificationAudit::class);
        $this->app->singleton(Services\QualificationReport::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
