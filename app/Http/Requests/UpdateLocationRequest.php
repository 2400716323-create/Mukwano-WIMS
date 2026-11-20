<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route('location');

        return $location instanceof Location
            && ($this->user()?->can('update', $location) ?? false);
    }

    public function rules(): array
    {
        $location = $this->route('location');

        return [
            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/\A[A-Za-z0-9][A-Za-z0-9-]*\z/',
                Rule::unique('locations', 'code')->ignore($location),
            ],
        ];
    }
}
