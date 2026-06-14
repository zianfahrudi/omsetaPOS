<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
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
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'vehicle_name' => ['nullable', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:30'],
            'mileage' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
