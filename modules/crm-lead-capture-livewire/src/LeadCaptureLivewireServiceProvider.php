<?php

declare(strict_types=1);

namespace Liberu\CRM\LeadCapture\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\CRM\LeadCapture\Livewire\Components\CaptureBrowser;
use Liberu\CRM\LeadCapture\Livewire\Components\CaptureForm;
use Livewire\Livewire;

final class LeadCaptureLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crm-lead-capture-livewire');
        Livewire::component('module-crm-lead-capture::capture-browser', CaptureBrowser::class);
        Livewire::component('module-crm-lead-capture::capture-form', CaptureForm::class);
    }
}
