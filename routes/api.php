<?php

use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/payments/razorpay/webhook', [PaymentController::class, 'webhook'])
        ->middleware('throttle:120,1')->name('payments.razorpay.webhook');
    Route::get('/website-content', [WebsiteContentApiController::class, 'index'])->name('website-content.index');\n    Route::get('/pages/{slug}', [WebsiteContentApiController::class, 'page'])->name('website-content.page');\n    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'service' => 'examtesthub-api',
        'version' => '1.0.0',
    ]))->name('health');

    Route::post('/auth/token', [ApiTokenController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('auth.token.store');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/me', fn (Request $request) => response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'preferred_locale' => $request->user()->preferred_locale,
                'roles' => $request->user()->roles()->pluck('slug'),
            ],
        ]))->name('me');

        Route::delete('/auth/token', [ApiTokenController::class, 'destroy'])
            ->name('auth.token.destroy');
    });
});
