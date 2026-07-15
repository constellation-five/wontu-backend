<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['food', 'other'])],
            'merchant_name' => ['nullable', 'string', 'max:64'],
            'location_label' => ['nullable', 'string', 'max:255'],
            // The `location` column is a NOT NULL spatial POINT (required for the spatial index), so every offer must carry coordinates.
            'location_lat' => ['required', 'numeric', 'between:-90,90'],
            'location_lng' => ['required', 'numeric', 'between:-180,180'],
            'closing_time' => ['required', 'date'],
            'arrival_time' => ['required', 'date', 'after_or_equal:closing_time'],
            'has_cod_payment' => ['boolean'],
            'payment_method_ids' => ['sometimes', 'array'],
            'payment_method_ids.*' => ['integer', 'exists:payment_methods,payment_method_id'],
            'items' => ['sometimes', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.item_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.item_url' => ['nullable', 'string', 'url'],
            'items.*.current_slot' => ['nullable', 'integer', 'min:0'],
            'items.*.slot' => ['nullable', 'integer', 'min:0'],
            'items.*.image_url' => ['nullable', 'string', 'url'],
        ];
    }
}
