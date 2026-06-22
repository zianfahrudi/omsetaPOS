<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'vehicle_plate_number' => ['nullable', 'string', 'max:30'],
            'vehicle_mileage' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,transfer,split'],
            'payment_proof' => ['nullable', 'required_if:payment_method,qris', 'image', 'max:5120'],
            'is_debt' => ['nullable', 'boolean'],
            'discount_code' => ['nullable', 'string', 'max:60'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'in:cash,qris,transfer'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'items.*.service_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
