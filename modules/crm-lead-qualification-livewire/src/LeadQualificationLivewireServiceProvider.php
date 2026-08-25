<?php

declare(strict_types=1);

namespace Liberu\CRM\LeadQualification\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\CRM\LeadQualification\Livewire\Components\QualificationBrowser;
use Liberu\CRM\LeadQualification\Livewire\Components\QualificationForm;
use Livewire\Livewire;

final class LeadQualificationLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crm-lead-qualification-livewire');
        Livewire::component('module-crm-lead-qualification::qualification-browser', QualificationBrowser::class);
        Livewire::component('module-crm-lead-qualification::qualification-form', QualificationForm::class);
    }
}
