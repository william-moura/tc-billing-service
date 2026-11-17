<?php

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\ListInvoicesUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListInvoicesController extends Controller
{
    public function __construct(
        private readonly ListInvoicesUseCase $listInvoicesUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        
        $invoices = $this->listInvoicesUseCase->execute();
        return response()->json($invoices);
    }
}