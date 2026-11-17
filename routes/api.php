<?php

use App\Presentation\Http\Controllers\GetInvoiceByWorkOrderController;
use App\Presentation\Http\Controllers\InvoiceController;
use App\Presentation\Http\Controllers\ListInvoicesController;
use App\Presentation\Http\Controllers\PaymentWebhookController;
use App\Presentation\Http\Controllers\ShowInvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    // Endpoints de consulta de Faturas
    Route::get('/invoices', ListInvoicesController::class);
    Route::get('/invoices/{id}', [ShowInvoiceController::class, 'show']);
    Route::get('/invoices/work-order/{workOrderId}', GetInvoiceByWorkOrderController::class);

    // Webhook Mercado Pago
    Route::post('/webhooks/mercadopago', [PaymentWebhookController::class, 'handle']);
    Route::post('/webhooks/payments', [PaymentWebhookController::class, 'handle']);
});
