<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidProductImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a valid image upload.');

            return;
        }

        $path = $value->getRealPath();
        $image = $path === false ? false : @getimagesize($path);
        $mimeType = $image['mime'] ?? null;

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $fail('The :attribute must contain valid JPEG, PNG, or WebP image data.');
        }
    }
}
