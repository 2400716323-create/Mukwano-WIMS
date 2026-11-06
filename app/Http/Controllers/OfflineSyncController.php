<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDispatchRequest;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Http\Requests\SubmitStockCountRequest;
use App\Models\DispatchRequest as DispatchRequestModel;
use App\Models\GoodsReceipt;
use App\Models\StockCount;
use App\Services\FefoPickingService;
use App\Services\OfflineSyncService;
use App\Services\StockCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfflineSyncController extends Controller
{
    public function goodsReceipt(
        StoreGoodsReceiptRequest $request,
        OfflineSyncService $offlineSyncService,
    ): JsonResponse {
        $validated = $request->validated();
        $clientId = $this->clientId($validated);
        unset($validated['client_id']);
        $this->authorize('create', GoodsReceipt::class);
        abort_unless(
            isset($validated['purchase_order_id']) || $request->user()->can('createDirect', GoodsReceipt::class),
            403,
        );

        $sync = $offlineSyncService->synchronize(
            $request->user(),
            $clientId,
            'goods_receipt_review',
            $validated,
            function () use ($clientId, $validated): string {
                session()->put('goods_receipt_reviews.'.$clientId, $validated);

                return route('goods-receipts.review.show', $clientId);
            },
        );

        return response()->json([
            'status' => 'synced',
            'duplicate' => $sync['duplicate'],
            'next_url' => $sync['next_url'],
            'message' => 'Receipt saved for review. Stock has not changed; confirm the receipt from its review page.',
        ]);
    }

    public function dispatch(
        ConfirmDispatchRequest $request,
        DispatchRequestModel $dispatchRequest,
        OfflineSyncService $offlineSyncService,
        FefoPickingService $fefoPickingService,
    ): JsonResponse {
        $validated = $request->validated();
        $clientId = $this->clientId($validated);
        unset($validated['client_id']);
        $validated['_offline_target_id'] = $dispatchRequest->id;

        try {
            $sync = $offlineSyncService->synchronize(
                $request->user(),
                $clientId,
                'dispatch',
                $validated,
                function () use ($dispatchRequest, $validated, $request, $fefoPickingService): string {
                    $this->authorize('dispatch', $dispatchRequest);
                    $postedDispatch = $fefoPickingService->post(
                        $dispatchRequest,
                        $validated['picks'],
                        $validated['deviation_reason'] ?? null,
                        $request->user(),
                    );

                    return route('dispatch-requests.print', $postedDispatch);
                },
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'needs_attention',
                'message' => 'The server rejected this dispatch after checking current stock. Review the live pick list before submitting a new transaction.',
                'errors' => $exception->errors(),
            ], 409);
        }

        return response()->json([
            'status' => 'synced',
            'duplicate' => $sync['duplicate'],
            'next_url' => $sync['next_url'],
            'message' => $sync['duplicate']
                ? 'This dispatch was already posted; no stock was changed again.'
                : 'Dispatch synced and revalidated against current server stock.',
        ]);
    }

    public function count(
        SubmitStockCountRequest $request,
        StockCount $stockCount,
        OfflineSyncService $offlineSyncService,
        StockCountService $stockCountService,
    ): JsonResponse {
        $validated = $request->validated();
        $clientId = $this->clientId($validated);
        unset($validated['client_id']);
        $validated['_offline_target_id'] = $stockCount->id;

        try {
            $sync = $offlineSyncService->synchronize(
                $request->user(),
                $clientId,
                'count_submission',
                $validated,
                function () use ($stockCount, $validated, $request, $stockCountService): string {
                    $this->authorize('submit', $stockCount);
                    $stockCountService->submit($stockCount, $validated['lines'], $request->user());

                    return route('stock-counts.show', $stockCount);
                },
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'needs_attention',
                'message' => 'The server could not apply this count submission to its current count state. Review the live count before submitting again.',
                'errors' => $exception->errors(),
            ], 409);
        }

        return response()->json([
            'status' => 'synced',
            'duplicate' => $sync['duplicate'],
            'next_url' => $sync['next_url'],
            'message' => $sync['duplicate']
                ? 'This count was already submitted; no stock was changed again.'
                : 'Count synced and server-validated. Stock still requires supervisor approval.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function clientId(array $validated): string
    {
        $clientId = $validated['client_id'] ?? null;
        abort_unless(is_string($clientId) && Str::isUuid($clientId), 422, 'A valid client transaction ID is required.');

        return $clientId;
    }
}
