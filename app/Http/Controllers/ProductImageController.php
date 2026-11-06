<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        $this->authorize('viewAny', Product::class);

        if (preg_match('/\A[a-z0-9]{40}\.(?:jpe?g|png|webp)\z/i', $filename) !== 1) {
            abort(404);
        }

        $imagePath = 'products/'.$filename;
        $disk = Storage::disk('public');

        if (! $disk->exists($imagePath)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($imagePath);
        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            abort(404);
        }

        return response()->file($disk->path($imagePath), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
