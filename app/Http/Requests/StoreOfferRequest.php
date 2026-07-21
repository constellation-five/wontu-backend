<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['food', 'electronics', 'fashion', 'home', 'beauty', 'gaming', 'sports', 'other'])],
            'based_on_request_id' => ['nullable', 'integer', 'exists:requests,request_id'],
            'merchant_name' => ['nullable', 'string', 'max:64'],
            'location_label' => ['nullable', 'string', 'max:255'],
            // The `location` column is a NOT NULL spatial POINT (required for the spatial index), so every offer must carry coordinates.
            'location_lat' => ['required', 'numeric', 'between:-90,90'],
            'location_lng' => ['required', 'numeric', 'between:-180,180'],
            'closing_time' => ['required', 'date'],
            // Full-datetime ordering (time-of-day included) is only enforced
            // when both times are meaningfully set — see withValidator()
            // below, since a bare `after_or_equal` here would also wrongly
            // reject e.g. a same-day offer whose arrival time was left unset
            // (and so defaults to 00:00, "before" an afternoon closing time).
            'arrival_time' => ['required', 'date'],
            'has_cod_payment' => ['boolean'],
            'payment_method_ids' => ['sometimes', 'array'],
            'payment_method_ids.*' => ['integer', 'exists:payment_methods,payment_method_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.item_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.item_url' => ['nullable', 'string', 'url'],
            'items.*.current_slot' => ['nullable', 'integer', 'min:0'],
            'items.*.slot' => ['nullable', 'integer', 'min:0'],
            'items.*.image_url' => ['nullable', 'string', 'url'],
        ];
    }

    /**
     * The date must always be closing <= arrival, regardless of time-of-day.
     * Only when the dates fall on the same day do we also compare the times.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $closing = $this->input('closing_time');
            $arrival = $this->input('arrival_time');

            if (! $closing || ! $arrival) {
                return;
            }

            $closingTime = Carbon::parse($closing);
            $arrivalTime = Carbon::parse($arrival);

            if ($arrivalTime->toDateString() < $closingTime->toDateString()) {
                $validator->errors()->add('arrival_time', 'Items must arrive on or after the offer closing date.');
            } elseif ($arrivalTime->toDateString() === $closingTime->toDateString() && $arrivalTime->lt($closingTime)) {
                $validator->errors()->add('arrival_time', 'On the same day, items must arrive at or after the offer closing time.');
            }
        });
    }
}
