<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Location::class) ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        $parentType = match ($type) {
            'zone', 'receiving', 'staging' => 'warehouse',
            'rack' => 'zone',
            'bin' => 'rack',
            default => null,
        };

        return [
            'type' => [
                'required',
                Rule::in([
                    'warehouse',
                    'zone',
                    'rack',
                    'bin',
                    'receiving',
                    'staging',
                ]),
            ],

            'name' => ['required', 'string', 'max:255'],

            'parent_id' => $parentType === null
                ? ['prohibited']
                : [
                    'required',
                    'integer',
                    Rule::exists('locations', 'id')
                        ->where('type', $parentType)
                        ->where('status', 'active'),
                ],

            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/\A[A-Za-z0-9][A-Za-z0-9-]*\z/',
                'unique:locations,code',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->input('parent_id');

            if ($parentId === null || $validator->errors()->has('parent_id')) {
                return;
            }

            $parent = Location::query()->find($parentId);

            if ($parent !== null && ! $parent->isOperational()) {
                $validator->errors()->add(
                    'parent_id',
                    'The parent location and all its ancestors must be active.'
                );
            }
        });
    }
}
