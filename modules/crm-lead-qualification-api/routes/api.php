<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\CRM\LeadQualification\Api\Http\Controllers\LeadQualificationController;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('api/v1/crm/lead-qualification')->group(function (): void {
    Route::get('/', [LeadQualificationController::class, 'index']);
    Route::post('/', [LeadQualificationController::class, 'store']);
    Route::get('/report', [LeadQualificationController::class, 'report']);
    Route::get('/frameworks', [LeadQualificationController::class, 'frameworks']);
    Route::post('/frameworks', [LeadQualificationController::class, 'storeFramework']);
    Route::get('/{qualification}', [LeadQualificationController::class, 'show']);
    Route::patch('/{qualification}', [LeadQualificationController::class, 'updateScores']);
    Route::post('/{qualification}/evaluate', [LeadQualificationController::class, 'evaluate']);
    Route::post('/{qualification}/transition', [LeadQualificationController::class, 'transition']);
    Route::post('/{qualification}/disqualify', [LeadQualificationController::class, 'disqualify']);
    Route::post('/{qualification}/nurture', [LeadQualificationController::class, 'nurture']);
    Route::post('/{qualification}/convert', [LeadQualificationController::class, 'convert']);
});
