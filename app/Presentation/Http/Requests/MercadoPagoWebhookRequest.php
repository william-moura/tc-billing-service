<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use App\Application\DTOs\WebhookPaymentInputDto;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

final class MercadoPagoWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação tolerantes aos diferentes padrões de webhook do Mercado Pago.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string'],
            'topic' => ['nullable', 'string'],
            'action' => ['nullable', 'string'],
            'data.id' => ['nullable', 'string'],
            'data_id' => ['nullable', 'string'],
            'id' => ['nullable', 'string'],
            'live_mode' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Extrai e mapeia a notificação sanitizada para o DTO de aplicação.
     */
    public function toDto(): ?WebhookPaymentInputDto
    {
        return WebhookPaymentInputDto::fromRequestData(
            data: $this->all(),
            queryParams: $this->query()
        );
    }

    /**
     * Garante resposta JSON estruturada caso a validação básica falhe.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Invalid webhook payload received.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}