<?php

namespace App\Http\Requests;

use App\Models\DispatchRequest as DispatchRequestModel;
use App\Models\OfflineSync;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dispatchRequest = $this->route('dispatchRequest');
        if ($this->routeIs('offline.sync.dispatch')
            && $this->user() !== null
            && is_string($this->input('client_id'))
            && OfflineSync::query()
                ->whereKey($this->input('client_id'))
                ->where('user_id', $this->user()->id)
                ->where('operation', 'dispatch')
                ->exists()) {
            return true;
        }

        return $dispatchRequest instanceof DispatchRequestModel
            && ($this->user()?->can('dispatch', $dispatchRequest) ?? false);
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'uuid'],
            'picks' => ['required', 'array', 'min:1'],
            'picks.*' => ['required', 'numeric', 'gte:0', 'decimal:0,3'],
            'deviation_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
