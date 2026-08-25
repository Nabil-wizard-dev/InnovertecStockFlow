<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['required', 'string', Rule::exists('customers', 'public_id')],
            'order_reference' => ['sometimes', 'string', 'max:255', Rule::unique('orders', 'order_reference')],
            // Pas de 'status' ici : une commande nait toujours en 'draft',
            // et passe en 'confirmed' via /orders/{order}/confirm.
            'items' => ['sometimes', 'array'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'public_id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'client',
            'items.*.product_id' => 'produit',
            'items.*.quantity' => 'quantite',
        ];
    }
}
