<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\ValidProductImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'sap_material_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sap_material_number')->ignore($product),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => [
                'nullable',
                'bail',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5120),
                new ValidProductImage,
            ],
            'remove_image' => ['sometimes', 'boolean'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('status', 'active'),
            ],
            'unit' => ['required', 'string', 'max:32'],
            'min_level' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'lte:reorder_level'],
            'reorder_level' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'gte:min_level', 'lte:max_level'],
            'max_level' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'gte:reorder_level'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
