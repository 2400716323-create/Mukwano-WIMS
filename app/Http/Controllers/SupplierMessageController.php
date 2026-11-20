<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierMessage;
use App\Models\User;
use App\Notifications\SupplierInternalMessageNotification;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SupplierMessageController extends Controller
{
    /**
     * Display messages for a supplier.
     */
    public function index(Request $request, Supplier $supplier): View
    {
        // Only users with permission to view this supplier can access messages.
        $this->authorize('view', $supplier);

        $currentUser = $request->user();
        $userId = $currentUser->getAuthIdentifier();

        $messages = SupplierMessage::with(['sender', 'recipient'])
            ->where('supplier_id', $supplier->getKey())
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('recipient_id', $userId);
            })
            ->latest()
            ->paginate(15);

        $users = User::query()
            ->where('is_active', true)
            ->whereKeyNot($userId)
            ->orderBy('name')
            ->get();

        return view('suppliers.messages.index', [
            'supplier' => $supplier,
            'messages' => $messages,
            'users' => $users,
        ]);
    }

    /**
     * Send an internal message.
     */
    public function store(
        Request $request,
        Supplier $supplier
    ): RedirectResponse {
        // Prevent unauthorized users from sending supplier messages.
        $this->authorize('view', $supplier);

        $currentUser = $request->user();

        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'subject' => [
                'required',
                'string',
                'max:150',
            ],
            'message' => [
                'required',
                'string',
                'max:10000',
            ],
        ]);

        $recipient = User::query()
            ->whereKey($validated['recipient_id'])
            ->where('is_active', true)
            ->first();

        if (! $recipient) {
            return back()
                ->withInput()
                ->withErrors([
                    'recipient_id' => 'Choose an active system user.',
                ]);
        }

        if (
            (string) $recipient->getAuthIdentifier()
            === (string) $currentUser->getAuthIdentifier()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'recipient_id' => 'You cannot send a message to yourself.',
                ]);
        }

        $supplierMessage = SupplierMessage::create([
            'supplier_id' => $supplier->getKey(),
            'sender_id' => $currentUser->getAuthIdentifier(),
            'recipient_id' => $recipient->getAuthIdentifier(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        try {
            $recipient->notify(
                new SupplierInternalMessageNotification($supplierMessage)
            );
        } catch (\Throwable $exception) {
            Log::error('Supplier internal message notification failed.', [
                'message_id' => $supplierMessage->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('suppliers.messages.index', $supplier)
            ->with('success', 'Your internal message has been sent.');
    }

    /**
     * Mark a received message as read.
     */
    public function markRead(
        Request $request,
        Supplier $supplier,
        SupplierMessage $supplierMessage
    ): RedirectResponse {
        // Verify access to the supplier first.
        $this->authorize('view', $supplier);

        $currentUserId = $request->user()->getAuthIdentifier();

        // Ensure the message belongs to the requested supplier.
        abort_unless(
            (string) $supplierMessage->supplier_id
                === (string) $supplier->getKey(),
            404
        );

        // Only the intended recipient can mark the message as read.
        abort_unless(
            (string) $supplierMessage->recipient_id
                === (string) $currentUserId,
            403
        );

        if (! $supplierMessage->read_at) {
            $supplierMessage->update([
                'read_at' => now(),
            ]);
        }

        return redirect()
            ->route('suppliers.messages.index', $supplier)
            ->with('success', 'Message marked as read.');
    }
}
