<?php

namespace App\Http\Requests\Order;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'string', Rule::exists('customers', 'public_id')],
            'order_reference' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('orders', 'order_reference')->ignore($this->route('order')?->id),
            ],
        ];
    }

    /**
     * Reject any attempt to change the status through this endpoint.
     *
     * Status transitions carry stock side effects, so they only happen
     * through POST /orders/{order}/confirm and /cancel.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->has('status')) {
                    $validator->errors()->add(
                        'status',
                        'Le statut se change via /orders/{order}/confirm ou /orders/{order}/cancel.'
                    );
                }
            },
        ];
    }
}
