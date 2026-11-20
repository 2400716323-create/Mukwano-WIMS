<?php

namespace App\Services;

use App\Models\OfflineSync;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

class OfflineSyncService
{
    /**
     * @param array<string, mixed> $payload
     * @return array{duplicate: bool, next_url: string}
     */
    public function synchronize(
        User $user,
        string $clientId,
        string $operation,
        array $payload,
        Closure $sync,
    ): array {
        unset($payload['client_id'], $payload['_token'], $payload['_method']);

        $payloadHash = hash('sha256', json_encode(
            $this->canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return DB::transaction(function () use (
            $user,
            $clientId,
            $operation,
            $payload,
            $payloadHash,
            $sync
        ): array {
            $existingSync = OfflineSync::query()
                ->lockForUpdate()
                ->find($clientId);

            if ($existingSync !== null) {
                abort_unless(
                    (int) $existingSync->user_id === $user->id
                    && $existingSync->operation === $operation
                    && hash_equals($existingSync->payload_hash, $payloadHash),
                    409,
                    'This client transaction ID was already used with different data or an account.',
                );

                abort_unless(
                    $existingSync->completed_at !== null
                    && $existingSync->result_url !== null,
                    409
                );

                return [
                    'duplicate' => true,
                    'next_url' => $existingSync->result_url,
                ];
            }

            $offlineSync = OfflineSync::query()->create([
                'id' => $clientId,
                'user_id' => $user->id,
                'operation' => $operation,
                'payload_hash' => $payloadHash,
                'payload' => $operation === 'goods_receipt_review'
                    ? $payload
                    : null,
            ]);

            $nextUrl = $sync();

            $offlineSync->forceFill([
                'result_url' => $nextUrl,
                'completed_at' => now(),
            ])->save();

            return [
                'duplicate' => false,
                'next_url' => $nextUrl,
            ];
        }, attempts: 3);
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function canonicalize(array $value): array
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->canonicalize($item);
            }
        }

        unset($item);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
