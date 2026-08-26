<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            // `gte` ne se declenche que si la borne basse est fournie : sans ce garde,
            // Laravel compare a un `min_price` absent et rejette un `max_price` seul.
            'max_price' => [
                'sometimes', 'numeric', 'min:0',
                Rule::when($this->has('min_price'), ['gte:min_price']),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * The status to filter on, already resolved to its enum case.
     */
    public function status(): ?ProductStatus
    {
        $value = $this->validated()['status'] ?? null;

        return $value !== null ? ProductStatus::from($value) : null;
    }

    /**
     * The lower bound of the unit price filter, if any.
     */
    public function minPrice(): ?float
    {
        $value = $this->validated()['min_price'] ?? null;

        return $value !== null ? (float) $value : null;
    }

    /**
     * The upper bound of the unit price filter, if any.
     */
    public function maxPrice(): ?float
    {
        $value = $this->validated()['max_price'] ?? null;

        return $value !== null ? (float) $value : null;
    }

    /**
     * The requested page size, within bounds.
     */
    public function perPage(): int
    {
        return (int) ($this->validated()['per_page'] ?? 15);
    }
}
