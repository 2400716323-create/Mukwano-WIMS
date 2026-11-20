<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Services\QrCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QrCodeController extends Controller
{
    /**
     * Display the QR code scanner.
     */
    public function scan(): View
    {
        $this->authorize('scan', Location::class);
        $this->authorize('scan', Product::class);

        return view('qr.scan');
    }

    /**
     * Resolve a scanned QR code into its location or product details.
     */
    public function resolve(
        Request $request,
        QrCodeService $qrCodeService
    ): JsonResponse {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $decoded = $qrCodeService->decode($validated['code']);

        if ($decoded === null) {
            throw ValidationException::withMessages([
                'code' => 'This QR code is invalid or its record is unavailable.',
            ]);
        }

        // Resolve location QR codes.
        if ($decoded['type'] === 'location') {
            $location = Location::query()
                ->with('parent.parent.parent', 'inventory.product')
                ->find($decoded['id']);

            if ($location === null) {
                throw ValidationException::withMessages([
                    'code' => 'This QR code is invalid or its record is unavailable.',
                ]);
            }

            $this->authorize('scan', Location::class);

            return response()->json([
                'type' => 'location',
                'name' => $location->name,
                'code' => $location->code,
                'status' => $location->isOperational()
                    ? 'active'
                    : 'inactive',
                'path' => $this->locationPath($location),
                'contents' => $location->type === 'bin'
                    ? $location->inventory->map(
                        fn ($inventory): array => [
                            'product' => $inventory->product->name,
                            'sku' => $inventory->product->sku,
                            'batch_number' => $inventory->batch_number,
                            'quantity' => $inventory->quantity,
                        ]
                    )->values()
                    : [],
                'url' => route('locations.show', $location),
            ]);
        }

        // Resolve product QR codes.
        $product = Product::query()
            ->with('category')
            ->find($decoded['id']);

        if ($product === null) {
            throw ValidationException::withMessages([
                'code' => 'This QR code is invalid or its record is unavailable.',
            ]);
        }

        $this->authorize('scan', Product::class);

        return response()->json([
            'type' => 'product',
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category->name,
            'status' => $product->status,
            'url' => route('products.show', $product),
        ]);
    }

    /**
     * Display a printable QR label for a warehouse location.
     */
    public function locationLabel(
        Location $location,
        QrCodeService $qrCodeService
    ): View {
        $this->authorize('printLabel', $location);

        $code = $qrCodeService->codeFor(
            'location',
            $location->id
        );

        return view('qr.label', [
            'title' => $location->name,
            'subtitle' => ucfirst($location->type)
                . ($location->code ? ' · '.$location->code : ''),
            'code' => $code,
            'qrSvg' => $qrCodeService->toSvg($code),
        ]);
    }

    /**
     * Display a printable QR label for a product.
     */
    public function productLabel(
        Product $product,
        QrCodeService $qrCodeService
    ): View {
        $this->authorize('printLabel', $product);

        $code = $qrCodeService->codeFor(
            'product',
            $product->id
        );

        return view('qr.label', [
            'title' => $product->name,
            'subtitle' => 'Product · '.$product->sku,
            'code' => $code,
            'qrSvg' => $qrCodeService->toSvg($code),
        ]);
    }

    /**
     * Build the full warehouse location hierarchy.
     */
    private function locationPath(Location $location): string
    {
        $names = [];
        $current = $location;

        while ($current !== null) {
            $names[] = $current->name;
            $current = $current->parent;
        }

        return implode(' / ', array_reverse($names));
    }
}
