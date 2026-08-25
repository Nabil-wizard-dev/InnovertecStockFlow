<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(CustomerStatus::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * The status to filter on, already resolved to its enum case.
     */
    public function status(): ?CustomerStatus
    {
        $value = $this->validated()['status'] ?? null;

        return $value !== null ? CustomerStatus::from($value) : null;
    }

    /**
     * The requested page size, within bounds.
     */
    public function perPage(): int
    {
        return (int) ($this->validated()['per_page'] ?? 15);
    }
}
