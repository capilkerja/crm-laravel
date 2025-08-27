<?php

namespace App\Filament\App\Resources\AnalyticsDashboardResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\AnalyticsDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnalyticsDashboard extends EditRecord
{
    protected static string $resource = AnalyticsDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
