<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');
        $ability = $this->isMethod('DELETE') ? 'delete' : 'update';

        return $category instanceof Category
            && ($this->user()?->can($ability, $category) ?? false);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'reassign_to' => [
                'nullable',
                'integer',
                Rule::notIn([$category instanceof Category ? $category->getKey() : null]),
                Rule::exists('categories', 'id')->where('status', 'active'),
            ],
        ];
    }
}
