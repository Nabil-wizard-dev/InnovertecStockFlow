<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'product_reference' => ['required', 'string', 'max:255', Rule::unique('products', 'product_reference')],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'available_quantity' => ['required', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
        ];
    }
}
