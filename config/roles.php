<?php

return [
    'roles' => [
        'administrator' => [
            'label' => 'Administrator',
            'permissions' => [
                'Manage users, roles, and security policies',
                'View audit log and system events',
                'Manage system settings and configurations',
                'Manage product catalog, categories, and suppliers',
                'Manage warehouse locations and bin layouts',
                'Create, approve, and cancel purchase orders',
                'Receive, transfer, dispatch, and reconcile stock',
                'Create and approve cycle counts and adjust variances',
                'View and export all analytical reports and ERP exchanges',
            ],
        ],
        'warehouse_manager' => [
            'label' => 'Warehouse Manager',
            'permissions' => [
                'Manage product catalog, categories, and suppliers',
                'Manage warehouse locations and bin layouts',
                'Create, approve, and cancel purchase orders',
                'Receive, transfer, dispatch, and reconcile stock',
                'Create and approve cycle counts and adjust variances',
                'Manage cycle-count variance and accuracy thresholds',
                'View and export stock ledger, enquiry, and operational reports',
            ],
        ],
        'storekeeper' => [
            'label' => 'Storekeeper',
            'permissions' => [
                'View product catalog, stock balances, and bin locations',
                'Perform goods receipt and staging put-away',
                'Perform stock transfers and bin movements',
                'Execute cycle count recording and blind counts',
                'Scan location and product QR codes',
            ],
        ],
        'procurement_officer' => [
            'label' => 'Procurement Officer',
            'permissions' => [
                'Manage supplier records and contacts',
                'Create and submit purchase orders',
                'View purchase orders and goods receipt status',
                'View product catalog and current stock levels',
                'Perform ERP purchase and item catalog imports',
            ],
        ],
        'dispatch_officer' => [
            'label' => 'Dispatch Officer',
            'permissions' => [
                'Create and manage dispatch requests',
                'Generate pick lists and verify batch allocation',
                'Post dispatches and print Goods Delivery Notes (GDN)',
                'View stock balances and product details',
                'Scan product and location QR codes for dispatch staging',
            ],
        ],
        'auditor' => [
            'label' => 'Auditor',
            'permissions' => [
                'View-only access to audit logs and security events',
                'View stock enquiry, stock ledger, and movements history',
                'View cycle count reports, variances, and review history',
                'Export analytical reports (PDF, CSV)',
                'View ERP exchange logs and reconciliation summaries',
            ],
        ],
    ],
];
