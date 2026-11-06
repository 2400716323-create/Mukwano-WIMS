<?php

namespace App\Http\Requests;

use App\Models\BinInventory;
use App\Models\Location;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveBinInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inventory = $this->route('binInventory');

        return $inventory instanceof BinInventory
            && $inventory->location->type === 'bin'
            && ($this->user()?->can('update', $inventory->location) ?? false);
    }

    public function rules(): array
    {
        $inventory = $this->route('binInventory');

        return [
            'destination_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'bin')
                    ->where('status', 'active'),
                Rule::notIn([$inventory instanceof BinInventory ? $inventory->location_id : null]),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $destinationId = $this->input('destination_location_id');
            if ($destinationId === null || $validator->errors()->has('destination_location_id')) {
                return;
            }

            $destination = Location::query()->find($destinationId);
            if ($destination !== null && ! $destination->isOperational()) {
                $validator->errors()->add(
                    'destination_location_id',
                    'The destination location and all its ancestors must be active.',
                );
            }
        });
    }
}
