# Mukwano WIMS — Pages and Route Security Audit (PAGES.md)

**Generated:** 2026-11-05  
**Target Phase Evaluated:** Phase 1 (`config/wims.php: completed_phase = 1`)  
**Database Connection:** `mysql` (database: `mukwano_wims`)  
**Audit Command:** `php artisan wims:audit` (aliases: `wims:audit-pages`, `wims:pages:audit`)  
**Overall Security Status:** `SECURITY FAIL` (92 non-guest routes currently exposed without proper authentication redirect)

---

## 1. Executive Summary

This document represents the baseline audit and registry verification for the Mukwano Warehouse Inventory Management System (WIMS) prior to initiating Phase 2 execution. 

In accordance with the audit hardening rules:
1. **Security Result is Independent of Phase**: Any non-guest route exposed or missing `auth` middleware triggers a `SECURITY FAIL` exit code (`1`), preventing `AUDIT PASSED` from displaying regardless of `completed_phase`.
2. **Static Route & Middleware Classification**: All 111 registered routes are classified by resolving their full middleware stack. Probes returning `404` or `500` due to model binding on fake IDs (`999999`) are classified as `INDETERMINATE`, never as `PROTECTED`. Probes returning `403` to unauthenticated guests are classified as `MISSING AUTH` (guests must redirect to `/login`).
3. **No Mutating Probes**: Audits run without mutating real database records. Real-record checks across roles reside in the PHPUnit test suite.
4. **44-Page Registry Coverage**: All 44 system pages (`P01` to `P44`) are tracked with controller, view, stub text, navigation, and error/offline fallback checks.

---

## 2. Phase 2 Architecture & RBAC Migration Plan

### Legacy-to-New Role Mapping

| Legacy Role Key | Target Role Key | Notes |
|:---|:---|:---|
| `admin` | `administrator` | Direct migration |
| `supervisor` | `warehouse_manager` | Direct migration |
| `dispatch_officer` | `dispatch_officer` | Direct migration |
| `user` | `storekeeper` | Default migration (to be reviewed user by user) |
| *(none)* | `procurement_officer` | New role (no existing legacy users) |
| *(none)* | `auditor` | New role (no existing legacy users) |

### Phase 2 Ordered Priorities

1. **(a) Route Authentication Enforcement**: Enforce authentication on every non-guest route, validated by logged-out HTTP redirect tests.
2. **(b) Role Migration & Server-Side Policies**: Execute migration from legacy role keys to standard role keys and register gate policies.
3. **(c) Role-Aware Navigation**: Update navigation to conditionally render links according to user roles.
4. **(d) Navy Shell & Primitives**: Implement the unified navy layout shell, header, drawer, and breadcrumbs.
5. **(e) P02 & P04 Error/Auth Pages**: Implement password reset flows (`P02`) and standard HTTP error pages (`P04`: 403, 404, 419, 500).
6. **(f) Dashboard Fixes**: Implement the 4 KPI cards, move top filters, render "No counts yet", whole numbers for countable units, and deferred PWA installation.

---

## 3. 44-Page Gap Analysis Matrix

| ID | Phase | Page Name | Due in P1? | Registered Routes | Target Roles | Gaps / Missing Alignment | Baseline Status |
|:---|:---|:---|:---|:---|:---|:---|:---|
| **P01** | P2 | Login | No | `login`<br>`login.store` | *(guest)* | Aligned | `PASS` |
| **P02** | P2 | Forgot and reset password | No | `password.request`<br>`password.email`<br>`password.reset`<br>`password.update` | *(guest)* | Missing routes: `password.request`, `password.email`, `password.reset`, `password.update` | `GAP (PHASE 2)` |
| **P03** | P2 | Profile and password settings | No | `profile.edit`<br>`profile.update`<br>`profile.password` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles: `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `auditor` | `GAP (PHASE 2)` |
| **P04** | P2 | Error pages 403, 404, 419, 500 | No | *(none)* | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Missing error views: `403.blade.php`, `404.blade.php`, `419.blade.php`, `500.blade.php`; Unmigrated roles | `GAP (PHASE 2)` |
| **P05** | P2 | Dashboard | No | `dashboard` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles; Unprotected route: `dashboard` (HTTP 200 without auth) | `GAP (PHASE 2)` |
| **P06** | P3 | Categories: list, add, edit | No | `categories.index`<br>`categories.create`<br>`categories.store`<br>`categories.edit`<br>`categories.update` | `administrator`, `warehouse_manager` | Unmigrated roles; Unprotected routes: `categories.edit`, `categories.update` | `GAP (PHASE 3)` |
| **P07** | P3 | Suppliers: list, add, edit | No | `suppliers.index`<br>`suppliers.create`<br>`suppliers.store`<br>`suppliers.edit`<br>`suppliers.update` | `administrator`, `warehouse_manager`, `procurement_officer` | Unmigrated roles; Unprotected routes: `suppliers.edit`, `suppliers.update` | `GAP (PHASE 3)` |
| **P08** | P3 | Products list | No | `products.index` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles | `GAP (PHASE 3)` |
| **P09** | P3 | Add product | No | `products.create`<br>`products.store` | `administrator`, `warehouse_manager` | Unmigrated roles | `GAP (PHASE 3)` |
| **P10** | P3 | Edit product | No | `products.edit`<br>`products.update` | `administrator`, `warehouse_manager` | Unmigrated roles; Unprotected routes: `products.edit`, `products.update` | `GAP (PHASE 3)` |
| **P11** | P3 | Product details with image, stock by batch and bin | No | `products.show` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles; Unprotected route: `products.show` | `GAP (PHASE 3)` |
| **P12** | P4 | Warehouse locations: hierarchy, add, edit | No | `locations.index`<br>`locations.create`<br>`locations.store`<br>`locations.edit`<br>`locations.update` | `administrator`, `warehouse_manager`, `storekeeper` | Unmigrated roles; Unprotected routes: `locations.edit`, `locations.update` | `GAP (PHASE 4)` |
| **P13** | P4 | Location/bin details and bin enquiry | No | `locations.show`<br>`inventory.enquiry` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles; Unprotected route: `locations.show` | `GAP (PHASE 4)` |
| **P14** | P4 | QR labels: preview and print | No | `locations.label`<br>`products.label` | `administrator`, `warehouse_manager`, `storekeeper` | Unmigrated roles; Unprotected routes: `locations.label`, `products.label` | `GAP (PHASE 4)` |
| **P15** | P4 | Scan QR (camera) | No | `qr.scan`<br>`qr.resolve` | `administrator`, `warehouse_manager`, `storekeeper`, `dispatch_officer` | Unmigrated roles; Unprotected route: `qr.resolve` | `GAP (PHASE 4)` |
| **P16** | P5 | Purchase orders list | No | `purchase-orders.index` | `administrator`, `warehouse_manager`, `procurement_officer` | Unmigrated roles | `GAP (PHASE 5)` |
| **P17** | P5 | Create and edit purchase order | No | `purchase-orders.create`<br>`purchase-orders.store`<br>`purchase-orders.edit`<br>`purchase-orders.update` | `administrator`, `warehouse_manager`, `procurement_officer` | Unmigrated roles; Unprotected routes: `purchase-orders.edit`, `purchase-orders.update` | `GAP (PHASE 5)` |
| **P18** | P5 | Purchase order details and print | No | `purchase-orders.show`<br>`purchase-orders.print` | `administrator`, `warehouse_manager`, `procurement_officer` | Unmigrated roles; Unprotected routes: `purchase-orders.show`, `purchase-orders.print` | `GAP (PHASE 5)` |
| **P19** | P5 | Goods receiving: guided new GRN | No | `goods-receipts.create`<br>`goods-receipts.review`<br>`goods-receipts.review.show`<br>`goods-receipts.confirm` | `administrator`, `warehouse_manager`, `storekeeper` | Unmigrated roles | `GAP (PHASE 5)` |
| **P20** | P5 | GRN history and search | No | `goods-receipts.index` | `administrator`, `warehouse_manager`, `storekeeper` | Unmigrated roles | `GAP (PHASE 5)` |
| **P21** | P5 | GRN details and print | No | `goods-receipts.show`<br>`goods-receipts.print` | `administrator`, `warehouse_manager`, `storekeeper` | Unmigrated roles; Unprotected routes: `goods-receipts.show`, `goods-receipts.print` | `GAP (PHASE 5)` |
| **P22** | P5 | Put-away tasks | No | `put-away.index`<br>`put-away.show`<br>`stock-balances.put-away` | `administrator`, `warehouse_manager`, `storekeeper` | Missing routes: `put-away.index`, `put-away.show`; Unmigrated roles; Unprotected route: `stock-balances.put-away` | `GAP (PHASE 5)` |
| **P23** | P5 | Batch management | No | `batches.index`<br>`batches.show` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Missing routes: `batches.index`, `batches.show`; Unmigrated roles | `GAP (PHASE 5)` |
| **P24** | P6 | Stock overview by product, batch, bin | No | `inventory.index` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles | `GAP (PHASE 6)` |
| **P25** | P6 | Stock enquiry with movement history | No | `inventory.enquiry` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles | `GAP (PHASE 6)` |
| **P26** | P6 | Stock movement ledger | No | `inventory.ledger` | `administrator`, `warehouse_manager`, `auditor` | Unmigrated roles | `GAP (PHASE 6)` |
| **P27** | P6 | Stock transfers: list and new transfer | No | `stock-transfers.index`<br>`stock-transfers.create`<br>`stock-transfers.store` | `administrator`, `warehouse_manager`, `storekeeper` | Missing route: `stock-transfers.index`; Unmigrated roles; Unprotected route: `stock-transfers.store` | `GAP (PHASE 6)` |
| **P28** | P6 | Manual stock adjustments: request and approve | No | `stock-adjustments.index`<br>`stock-adjustments.create`<br>`stock-adjustments.store`<br>`stock-adjustments.show`<br>`stock-adjustments.approve` | `storekeeper`, `warehouse_manager`, `administrator` | Missing routes: `stock-adjustments.index`, `stock-adjustments.create`, `stock-adjustments.store`, `stock-adjustments.show`, `stock-adjustments.approve`; Unmigrated roles | `GAP (PHASE 6)` |
| **P29** | P7 | Dispatch requests: list and create | No | `dispatch-requests.index`<br>`dispatch-requests.create`<br>`dispatch-requests.store` | `administrator`, `warehouse_manager`, `dispatch_officer` | Unmigrated roles | `GAP (PHASE 7)` |
| **P30** | P7 | FEFO picking and exception request | No | `dispatch-requests.pick-list`<br>`dispatch-requests.exception` | `dispatch_officer`, `warehouse_manager`, `administrator` | Missing route: `dispatch-requests.exception`; Unmigrated roles; Unprotected route: `dispatch-requests.pick-list` | `GAP (PHASE 7)` |
| **P31** | P7 | GDN details and print | No | `dispatch-requests.print` | `dispatch_officer`, `warehouse_manager`, `administrator` | Unmigrated roles; Unprotected route: `dispatch-requests.print` | `GAP (PHASE 7)` |
| **P32** | P8 | Cycle counts: list and create | No | `stock-counts.index`<br>`stock-counts.create`<br>`stock-counts.store` | `administrator`, `warehouse_manager`, `storekeeper` | Unmigrated roles | `GAP (PHASE 8)` |
| **P33** | P8 | Count entry (blind-count option) | No | `stock-counts.show`<br>`stock-counts.submit` | `storekeeper`, `warehouse_manager`, `administrator` | Unmigrated roles; Unprotected routes: `stock-counts.show`, `stock-counts.submit` | `GAP (PHASE 8)` |
| **P34** | P8 | Count approvals | No | `stock-counts.approvals`<br>`stock-counts.review` | `warehouse_manager`, `administrator` | Missing route: `stock-counts.approvals`; Unmigrated roles; Unprotected route: `stock-counts.review` | `GAP (PHASE 8)` |
| **P35** | P9 | Alert centre | No | `alerts.index`<br>`alerts.resolve` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles; Unprotected routes: `alerts.index`, `alerts.resolve` | `GAP (PHASE 9)` |
| **P36** | P9 | Near-expiry view: 30/60/90 days, expired separate | No | `alerts.near-expiry` | `administrator`, `warehouse_manager`, `storekeeper`, `dispatch_officer` | Missing route: `alerts.near-expiry`; Unmigrated roles | `GAP (PHASE 9)` |
| **P37** | P10 | Reports hub with filters and PDF/CSV export | No | `reports.index`<br>`reports.show`<br>`reports.pdf`<br>`reports.csv` | `administrator`, `warehouse_manager`, `storekeeper`, `procurement_officer`, `dispatch_officer`, `auditor` | Unmigrated roles | `GAP (PHASE 10)` |
| **P38** | P10 | ERP CSV import | No | `erp-exchange.index`<br>`erp-exchange.products.preview`<br>`erp-exchange.products.validate`<br>`erp-exchange.products.confirm`<br>`erp-exchange.products.show`<br>`erp-exchange.products.errors` | `administrator`, `procurement_officer` | Unmigrated roles | `GAP (PHASE 10)` |
| **P39** | P10 | Reconciliation exports | No | `erp-exchange.reconciliation.csv` | `administrator`, `warehouse_manager`, `procurement_officer` | Unmigrated roles | `GAP (PHASE 10)` |
| **P40** | P11 | Users: list, add, edit, activate/deactivate, assign role | No | `admin.users.index`<br>`admin.users.create`<br>`admin.users.store`<br>`admin.users.edit`<br>`admin.users.update`<br>`admin.users.role`<br>`admin.users.status` | `administrator` | Missing routes: `admin.users.create`, `admin.users.edit`, `admin.users.update`; Unmigrated roles | `GAP (PHASE 11)` |
| **P41** | P11 | Roles and permissions (read-only matrix) | No | `admin.roles.index` | `administrator`, `warehouse_manager` | Unmigrated roles | `GAP (PHASE 11)` |
| **P42** | P11 | Audit logs (filterable, read-only) | No | `admin.audit.index` | `administrator`, `auditor` | Unmigrated roles | `GAP (PHASE 11)` |
| **P43** | P11 | System settings: company name, logo, thresholds, expiry windows | No | `admin.settings.edit`<br>`admin.settings.update` | `administrator` | Unmigrated roles | `GAP (PHASE 11)` |
| **P44** | P12 | Offline fallback page with stale-data notice and retry control | No | *(none)* | *(guest)* | `public/offline.html` missing retry control | `GAP (PHASE 12)` |

---

## 4. Route Authentication Enforcement Summary

- **Total Registered Routes:** 111
- **Guest Routes:** 4 (`login`, `login.store`, `_boost/browser-logs`, `/up`)
- **Properly Protected Non-Guest Routes:** 15 (`logout`, `profile.edit`, `profile.update`, `profile.password`, `offline.sync.*`, `admin.*`)
- **Indeterminate Routes (404/500 model binding on unseeded probe):** 0
- **Exposed / Missing Auth Routes:** 92 (Routes lacking `auth` middleware or returning 200/403 without login redirect)

### Primary Exposed Routes Requiring Phase 2 Authentication Guarding

1. `GET /` (`dashboard`) &rarr; HTTP 200 without authentication
2. `GET /alerts` (`alerts.index`) &rarr; HTTP 200 without authentication
3. `PATCH /alerts/{alert}/resolve` (`alerts.resolve`) &rarr; [MISSING auth middleware]
4. `GET /categories` (`categories.index`) &rarr; HTTP 403 [MISSING auth middleware]
5. `GET /products` (`products.index`) &rarr; HTTP 403 [MISSING auth middleware]
6. `GET /suppliers` (`suppliers.index`) &rarr; HTTP 403 [MISSING auth middleware]
7. `GET /purchase-orders` (`purchase-orders.index`) &rarr; HTTP 403 [MISSING auth middleware]
8. `GET /goods-receipts` (`goods-receipts.index`) &rarr; HTTP 403 [MISSING auth middleware]
9. `GET /inventory` (`inventory.index`) &rarr; HTTP 403 [MISSING auth middleware]
10. `GET /stock-transfers` (`stock-transfers.create`) &rarr; HTTP 403 [MISSING auth middleware]
11. `GET /dispatch-requests` (`dispatch-requests.index`) &rarr; HTTP 403 [MISSING auth middleware]
12. `GET /stock-counts` (`stock-counts.index`) &rarr; HTTP 403 [MISSING auth middleware]
13. `GET /reports` (`reports.index`) &rarr; HTTP 403 [MISSING auth middleware]
14. `GET /erp-exchange` (`erp-exchange.index`) &rarr; HTTP 403 [MISSING auth middleware]
15. `GET /locations` (`locations.index`) &rarr; HTTP 403 [MISSING auth middleware]
16. `GET /scan` (`qr.scan`) &rarr; HTTP 403 [MISSING auth middleware]

---

## 5. Exit Criteria for Phase 2 Acceptance

To advance from Phase 1 to Phase 2, the following conditions must be met:
1. Every non-guest route must have `auth` middleware and redirect unauthenticated requests to `/login` (302).
2. The hardened audit command `php artisan wims:audit` must return `0` exposed routes and exit with code `0`.
3. `config/wims.php` `completed_phase` raised to `2`.
4. `WimsPagesRegistryTest` passes all assertions for pages `P01` through `P05`.

