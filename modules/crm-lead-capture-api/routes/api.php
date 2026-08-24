<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\CRM\LeadCapture\Api\Http\Controllers\LeadCaptureController;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('api/v1/crm/lead-capture')->group(function (): void {
    Route::get('/', [LeadCaptureController::class, 'index']);
    Route::post('/', [LeadCaptureController::class, 'store']);
    Route::get('/report', [LeadCaptureController::class, 'report']);
    Route::get('/forms', [LeadCaptureController::class, 'forms']);
    Route::post('/forms', [LeadCaptureController::class, 'createForm']);
    Route::post('/forms/{form}/submit', [LeadCaptureController::class, 'submitForm']);
    Route::get('/qr-codes', [LeadCaptureController::class, 'qrCodes']);
    Route::post('/qr-codes', [LeadCaptureController::class, 'createQrCode']);
    Route::get('/referrals', [LeadCaptureController::class, 'referrals']);
    Route::post('/referrals', [LeadCaptureController::class, 'recordReferral']);
    Route::get('/{capture}', [LeadCaptureController::class, 'show']);
    Route::patch('/{capture}', [LeadCaptureController::class, 'update']);
});
