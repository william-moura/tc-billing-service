<?php

use App\Presentation\Http\Controllers\InvoiceController;
use App\Presentation\Http\Controllers\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    // Endpoints de consulta de Faturas
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::get('/invoices/work-order/{workOrderId}', [InvoiceController::class, 'findByWorkOrder']);

    // Webhook Mercado Pago
    Route::post('/payments/webhook', [PaymentWebhookController::class, 'handle']);
});
