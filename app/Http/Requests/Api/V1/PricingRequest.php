<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAccessStore((int) $this->input('store_id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_code' => ['nullable', 'string', 'max:60'],
        ];
    }
}
