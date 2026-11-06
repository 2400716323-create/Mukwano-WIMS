# Future Flutter client API

## Implementation status

This document is a proposed API contract for a future Flutter client. The
current application has no `routes/api.php`, versioned JSON API, bearer-token
authentication, or OpenAPI definition. Current business pages use Laravel web
routes, sessions, and CSRF tokens. The three `/offline/sync/...` endpoints
accept authenticated web-session requests from the PWA; they are not a
Flutter-ready API and must not be called with a long-lived session cookie
stored in a mobile app.

Implement and version the API before shipping a Flutter client. Keep these
contract details aligned with the existing FEFO, goods receipt, inventory,
and count services so that API requests use the same authorization and
transaction rules.

## Proposed conventions

- Base URL: `https://<host>/api/v1`.
- JSON request and response bodies use UTF-8. Dates are ISO 8601; quantities
  are decimal strings with three fractional digits to avoid floating-point
  rounding.
- Authenticate with short-lived access tokens and revocable refresh tokens
  (Laravel Sanctum or an equivalent reviewed mechanism). Token authentication
  is not installed yet. Do not use browser session cookies as mobile tokens.
- Require TLS. Apply per-user and per-IP rate limits to sign-in and sensitive
  actions. Return a request/correlation ID for support diagnostics.
- Paginated list endpoints return:

  ```json
  {
    "data": [],
    "meta": { "current_page": 1, "per_page": 25, "last_page": 1, "total": 0 }
  }
  ```

- Use standard status codes: `200`/`201` success, `401` unauthenticated,
  `403` forbidden, `404` missing resource, `409` current-state/idempotency
  conflict, `422` validation failure, and `429` rate limit. Validation errors
  should use Laravel's field-keyed `errors` object.

## Proposed endpoint inventory

These are design targets, not routes available in the current release.

| Method | Proposed route | Purpose |
|---|---|---|
| POST | `/auth/session` | Authenticate and issue tokens |
| POST | `/auth/refresh` | Rotate refresh/access tokens |
| DELETE | `/auth/session` | Revoke the current token |
| GET | `/me` | Current active user and role |
| GET | `/products`, `/categories`, `/suppliers` | Paginated reference data |
| GET | `/locations`, `/batches` | Operational locations and batches |
| GET | `/inventory` | Filtered on-hand balances |
| GET | `/inventory/movements` | Filtered movement ledger |
| GET/POST | `/purchase-orders` | List or create purchase orders |
| POST | `/purchase-orders/{id}/approve` | Approve a draft order |
| POST | `/goods-receipts` | Validate and post a receipt |
| GET/POST | `/dispatch-requests` | List or create dispatch requests |
| POST | `/dispatch-requests/{id}/post` | Revalidate FEFO picks and post |
| GET/POST | `/stock-counts` | List or create count lists |
| POST | `/stock-counts/{id}/submit` | Submit physical quantities |
| POST | `/stock-counts/{id}/review` | Supervisor decision and approval |
| POST | `/inventory/transfers` | Atomically transfer stock |
| GET | `/reports/{report}` | Filtered report data |
| GET | `/alerts` | Active inventory alerts |
| GET | `/admin/audit-log` | Admin-only, read-only audit search |

Add admin user and system-setting endpoints only with role-policy tests,
field-level validation, audit coverage, and explicit protection against
ordinary-user access.

## Idempotent stock-changing requests

Require a client-generated UUID `client_id` for any mobile request that may
be retried after a network interruption. Persist the ID with the authenticated
user, operation, canonical payload hash, and result. In one database
transaction, revalidate current server state and post stock effects. Do not
trust device balances, snapshots, roles, FEFO suggestions, or timestamps as
authoritative.

For a retry with the same user, operation, client ID, and payload, return the
original result and set `"duplicate": true`; do not apply stock effects again.
Reusing an ID with a different payload, user, operation, or target resource
returns `409 Conflict`. Never automatically overwrite a changed stock balance.

### Goods receipt submission

Proposed request:

```json
{
  "client_id": "UUID",
  "purchase_order_id": 42,
  "supplier_id": 7,
  "notes": "Optional",
  "lines": [
    {
      "product_id": 12,
      "quantity": "3.500",
      "batch_number": "LOT-100",
      "manufactured_at": null,
      "expires_at": "2027-03-01",
      "location_id": 9
    }
  ]
}
```

Validate the active supplier, permitted purchase-order state, product,
quantity, batch, expiry, and operational location on the server. A successful
response means the receipt was accepted and stock was posted only according
to the explicit receiving workflow. Return a stable receipt ID and current
status.

### Dispatch posting

Proposed request:

```json
{
  "client_id": "UUID",
  "picks": [
    { "batch_id": 23, "location_id": 9, "quantity": "3.500" }
  ],
  "deviation_reason": null
}
```

Recheck request status, authorization, live balances, requested quantity,
batch eligibility, FEFO order, and any deviation reason inside the posting
transaction. A stock/state conflict returns `409` with a safe message and
does not debit stock.

### Count submission

Proposed request:

```json
{
  "client_id": "UUID",
  "lines": {
    "101": { "physical_quantity": "3.500", "discrepancy_reason": null }
  }
}
```

Validate that every current count line appears once, the count is still a
draft, quantities are valid, and every discrepancy has a reason. Submission
does not change stock. Supervisor approval must recheck the original stock
snapshot and remain a distinct, audited action.

## Offline client state

The future client may locally queue only receiving, dispatch posting, and
count submission after their forms and reference data have already been
loaded. Suggested local states are `pending`, `synced`, `rejected`, and
`needs_attention`; retain the client ID for every retry. Retry transient
network/5xx failures with bounded backoff. Treat `401`, `403`, `409`, and
`422` as user-action states, not as permission to silently edit/resubmit.
Use encrypted platform storage for any credentials and minimize offline
personal/business data.

Do not queue approvals, transfers, master-data edits, imports, report
exports, or any operation that depends on creating fresh server reference
data offline.

## Existing web/PWA sync routes (not a public API)

The current PWA uses these same-origin, session-authenticated web routes:

- `POST /offline/sync/goods-receipt`
- `POST /offline/sync/dispatch/{dispatchRequest}`
- `POST /offline/sync/count/{stockCount}`

They require an authenticated browser session and CSRF protection. Their
request/response details are implementation-specific and are not a stable
mobile contract. Keep them private to the web application until a versioned
API and token strategy are implemented and tested.
