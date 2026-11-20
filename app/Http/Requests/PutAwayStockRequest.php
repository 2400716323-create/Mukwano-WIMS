<?php

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PutAwayStockRequest extends FormRequest
{
    /**
     * Determine whether the logged-in user can perform put-away.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $balance = $this->route('stockBalance');

        if (! $user || ! $balance instanceof StockBalance) {
            return false;
        }

        $source = $balance->location;

        if (! $source) {
            return false;
        }

        // Only allow stock held in operational storage or receiving locations.
        $allowedSourceTypes = ['bin', 'receiving', 'staging'];

        // Storekeepers and Warehouse Managers can perform put-away.
        $canPutAway = $user->hasPermission(
            'Perform goods receipt and staging put-away'
        ) || $user->hasPermission(
            'Receive, transfer, dispatch, and reconcile stock'
        );

        return $canPutAway
            && in_array($source->type, $allowedSourceTypes, true)
            && $source->isOperational();
    }

    /**
     * Validate the put-away request data.
     */
    public function rules(): array
    {
        $balance = $this->route('stockBalance');

        return [
            'destination_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'bin')
                    ->where('status', 'active'),
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'lte:'.($balance?->quantity ?? 0),
                'decimal:0,3',
            ],

            'idempotency_key' => [
                'required',
                'uuid',
            ],
        ];
    }

    /**
     * Validate that the destination bin and its parent locations
     * are active and operational.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $destinationId = $this->input('destination_location_id');

            if (
                $destinationId === null
                || $validator->errors()->has('destination_location_id')
            ) {
                return;
            }

            $destination = Location::query()->find($destinationId);

            if ($destination !== null && ! $destination->isOperational()) {
                $validator->errors()->add(
                    'destination_location_id',
                    'Choose a storage bin with active ancestors.'
                );
            }
        });
    }
}
