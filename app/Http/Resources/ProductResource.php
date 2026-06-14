<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->barcode ?: $this->sku,
            'barcode' => $this->barcode,
            'sku' => $this->sku,
            'image_url' => $this->image_url,
            'product_type' => $this->product_type,
            'price' => $this->unitSalePrice(),
            'fee_amount' => (float) $this->fee_amount,
            'product_service_fee' => $this->productServiceFeeAmount(),
            'product_service_fee_type' => $this->product_service_fee_type,
            'product_service_fee_value' => (float) $this->product_service_fee,
            'product_tax_type' => $this->product_tax_type,
            'product_tax_value' => (float) $this->product_tax_value,
            'product_tax_amount' => $this->productTaxAmount(),
            'base_price' => $this->baseSalePrice(),
            'unit_price' => $this->unitSalePrice(),
            'stock' => $this->stock,
            'unit' => $this->unit,
        ];
    }
}
