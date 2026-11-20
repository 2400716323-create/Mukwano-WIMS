<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierMessage;
use Illuminate\Http\Request;

class ProcurementCommunicationController extends Controller
{
    /**
     * Display the Procurement Communication Centre dashboard.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $userId = $request->user()->id;

        $openStatuses = [
            PurchaseOrder::STATUS_APPROVED,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
        ];

        // Purchase order statistics
        $metrics = [
            'total_orders' => PurchaseOrder::count(),

            'draft' => PurchaseOrder::where(
                'status',
                PurchaseOrder::STATUS_DRAFT
            )->count(),

            'approved' => PurchaseOrder::where(
                'status',
                PurchaseOrder::STATUS_APPROVED
            )->count(),

            'partially_received' => PurchaseOrder::where(
                'status',
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED
            )->count(),

            'received' => PurchaseOrder::where(
                'status',
                PurchaseOrder::STATUS_RECEIVED
            )->count(),

            'overdue' => PurchaseOrder::whereIn('status', $openStatuses)
                ->whereNotNull('expected_date')
                ->whereDate('expected_date', '<', now()->toDateString())
                ->count(),
        ];

        // Recent purchase orders and their suppliers
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->withCount('lines')
            ->latest('created_at')
            ->limit(10)
            ->get();

        // Supplier directory with outstanding order counts
        $suppliers = Supplier::withCount([
            'purchaseOrders as open_purchase_orders_count' => function ($query) use ($openStatuses) {
                $query->whereIn('status', $openStatuses);
            },
        ])
            ->orderBy('name')
            ->limit(10)
            ->get();

        $supplierCount = Supplier::count();

        // Only show messages sent or received by the logged-in user
        $messageQuery = SupplierMessage::where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('recipient_id', $userId);
        });

        $messageCount = (clone $messageQuery)->count();

        // Count unread messages received by the logged-in user
        $unreadMessagesCount = SupplierMessage::where('recipient_id', $userId)
            ->whereNull('read_at')
            ->count();

        // Latest five messages for the dashboard
        $recentMessages = (clone $messageQuery)
            ->with(['supplier', 'sender', 'recipient'])
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('procurement.communications.index', compact(
            'metrics',
            'purchaseOrders',
            'suppliers',
            'supplierCount',
            'messageCount',
            'unreadMessagesCount',
            'recentMessages'
        ));
    }

    /**
     * Display the complete supplier communication history.
     */
    public function messages(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        // Validate search and filter inputs
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'filter' => ['nullable', 'in:all,unread,read,sent,received'],
        ]);

        $userId = $request->user()->id;

        $search = trim($validated['q'] ?? '');
        $filter = $validated['filter'] ?? 'all';

        // Only retrieve messages belonging to this user
        $query = SupplierMessage::with([
            'supplier',
            'sender',
            'recipient',
        ])->where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('recipient_id', $userId);
        });

        // Search by subject, message, supplier or staff member
        if ($search !== '') {
            $query->where(function ($query) use ($search) {

                $query->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")

                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    })

                    ->orWhereHas('sender', function ($senderQuery) use ($search) {
                        $senderQuery->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    })

                    ->orWhereHas('recipient', function ($recipientQuery) use ($search) {
                        $recipientQuery->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        // Filter messages
        switch ($filter) {

            case 'unread':
                $query->where('recipient_id', $userId)
                    ->whereNull('read_at');
                break;

            case 'read':
                $query->where('recipient_id', $userId)
                    ->whereNotNull('read_at');
                break;

            case 'sent':
                $query->where('sender_id', $userId);
                break;

            case 'received':
                $query->where('recipient_id', $userId);
                break;

            case 'all':
            default:
                // All messages sent or received by the logged-in user
                break;
        }

        // Newest messages first, 15 messages per page
        $messages = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('procurement.communications.messages', compact(
            'messages',
            'search',
            'filter'
        ));
    }
}
