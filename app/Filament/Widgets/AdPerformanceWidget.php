<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\AdvertisingAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AdPerformanceWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;

        // Fetch data from all advertising accounts
        AdvertisingAccount::where('status', true)->each(function ($account) use (&$totalImpressions, &$totalClicks, &$totalConversions) {
            $serviceClass = 'App\\Services\\' . str_replace(' ', '', $account->platform) . 'Service';
            $service = new $serviceClass($account);
            
            // Assuming each service has a getPerformanceMetrics method
            $metrics = $service->getPerformanceMetrics();
            
            $totalImpressions += $metrics['impressions'];
            $totalClicks += $metrics['clicks'];
            $totalConversions += $metrics['conversions'];
        });

        return [
            Stat::make('Total Impressions', number_format($totalImpressions))
                ->description('Across all platforms')
                ->descriptionIcon('heroicon-s-trending-up')
                ->color('primary'),
            Stat::make('Total Clicks', number_format($totalClicks))
                ->description('Across all platforms')
                ->descriptionIcon('heroicon-s-cursor-click')
                ->color('success'),
            Stat::make('Total Conversions', number_format($totalConversions))
                ->description('Across all platforms')
                ->descriptionIcon('heroicon-s-shopping-cart')
                ->color('warning'),
        ];
    }
}