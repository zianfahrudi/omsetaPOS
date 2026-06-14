<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
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
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'type' => ['required', 'in:full,exchange'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'additional_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'evidence_photos' => ['required', 'array', 'min:1', 'max:6'],
            'evidence_photos.*' => ['image', 'max:5120'],
            'returned_items' => ['nullable', 'array'],
            'returned_items.*.sale_item_id' => ['required_with:returned_items', 'integer', 'exists:sale_items,id'],
            'returned_items.*.quantity' => ['required_with:returned_items', 'integer', 'min:1'],
            'replacement_items' => ['nullable', 'array'],
            'replacement_items.*.product_id' => ['required_with:replacement_items', 'integer', 'exists:products,id'],
            'replacement_items.*.quantity' => ['required_with:replacement_items', 'integer', 'min:1'],
        ];
    }
}
