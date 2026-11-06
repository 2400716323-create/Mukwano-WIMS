# Mukwano WIMS user manual

## Sign in and account access

Open the WIMS URL and choose **Sign in**. Use the email address and password
given to you by an administrator. Deactivated accounts cannot sign in. Use
**My profile** to update your name or password, then **Sign out** when finished.
There is currently no self-service password reset or multi-factor sign-in.

Administrators create accounts, assign one of the configured roles, and
activate or deactivate users from **Administration**. The **Roles** page
describes the permissions configured for each role; role permissions are
defined by the application and are not edited in the UI.

## Products, categories, suppliers, and warehouse locations

- Use **Products**, **Categories**, and **Suppliers** to find and maintain the
  master records. Product images accept JPEG, PNG, or WebP uploads.
- Use **Warehouses** to maintain the warehouse, zone, rack, and bin hierarchy.
  Keep bins active and operational before receiving or moving stock into them.
- Product and location labels can be printed as QR codes. **Scan QR** resolves
  a scanned code to its WIMS record.

Records with stock or transaction history may not be deletable. Deactivate
records instead of removing records that are still referenced.

## Purchase orders and goods receiving

1. Create a purchase order with a supplier, order date, optional expected date,
   and one or more product quantities. New orders start as drafts.
2. Review and approve the purchase order before receiving against it.
3. Open **Goods receipts**, select the purchase order or start a direct receipt
   if your role permits it, and enter product, batch, quantity, expiry, and bin
   details.
4. Review the receipt and confirm it. Stock is posted only after confirmation.
   The receipt and stock movements are then available in the receipt history
   and ledger.

An approved purchase order can be received in multiple partial receipts.
Server-side validation checks the current order, products, locations, and
quantities at confirmation.

## Stock, transfers, put-away, and dispatch

- **Stock** shows on-hand balances and recent movements.
- **Stock enquiry** and **Movement ledger** provide filters for product, batch,
  location, and movement/date details.
- **Transfers** move a quantity between operational bins. The system records
  paired movements and does not allow a transfer to silently overwrite a
  balance.
- Put-away moves received stock from a receiving location to an operational
  bin.
- Create a **Dispatch** request for a product and quantity. Review the FEFO
  pick list (first-expiring, first-out), adjust picks only with an allowed
  reason, then post. A goods delivery note is generated for a posted dispatch.

Dispatch posting rechecks available stock and request state on the server.

## Cycle counts and approvals

Create a count for a bin, optionally restricted to a product or batch. Blind
counts hide the system quantity from the counter. Enter a physical quantity
for every line and provide a reason for each discrepancy, then submit it.
Submission does not change stock. A supervisor reviews and approves or rejects
the count. Approval rechecks that the stock snapshot has not changed; approved
variances are recorded as stock movements and audit events.

The configured variance threshold and inventory-record-accuracy tolerance
are available under the stock-count settings. Administrators can also manage
these thresholds in System settings.

## Reports, ERP exchange, and alerts

- **Reports** provides stock-on-hand, received/dispatched, movement, transfer,
  expiry, low-stock, cycle-count, inventory-reconciliation, purchase-order,
  and audit-log reports. Where offered, export to CSV or PDF.
- **ERP exchange** stages a product CSV, lets an operator map and validate
  columns, and requires explicit confirmation before importing product
  changes. Reconciliation CSV compares stock balances with the movement
  ledger.
- **Alerts** lists generated inventory conditions. Resolving an alert records
  its resolution; it does not change a stock balance.

Review downloaded exports as sensitive business data. CSV exports are
formula-escaped where applicable.

## Administration and audit

Administrators can create users, change roles and active status, review the
configured permissions, update the company name and logo, and configure
inventory thresholds and expiry windows. The audit log is a filterable,
read-only view. Audit records cannot be edited or deleted through the app.

The audit log records authentication activity and the covered user,
permission, product, receipt, dispatch, transfer, adjustment, count approval,
import/export, and configuration changes.

## Installable app and offline queue

Use the browser's **Install app** prompt while online over HTTPS or localhost.
The app's service worker caches only the offline notice, manifest, and icons;
it does not cache private pages or reference data.

Only already-open **goods receipt review**, **dispatch pick**, and **count
submission** forms can be saved to the device's IndexedDB queue. The page must
remain open for automatic retry. On reconnect, the server validates current
permissions and transaction state. The queue displays **pending**, **synced**,
**rejected**, or **needs attention**. Open a synced item's next step to
complete any required online action. Compare rejected or conflicting
submissions with current server data before creating a new transaction.

Sign-in, loading pages and reference data, creating purchase orders or count
lists, receipt confirmation, approvals, transfers, inventory changes,
reports, imports/exports, and all other actions require connectivity. Queued
data is device-local, unencrypted by this app, and may be lost if the browser
clears site data. Do not use the queue on a shared or untrusted device.

## Backups and recovery

`php artisan backup:run` creates a SQLite database and public-upload backup.
It does not back up MySQL, MariaDB, or another production database. Follow the
production database provider's backup policy; see the README for MySQL backup
and restore guidance. Test restores in a separate environment before relying
on a backup.

## Common problems

- **Missing database table:** configure the correct database, then run
  `php artisan migrate --force` in production or `php artisan migrate` locally.
- **Cannot sign in:** confirm the account is active and the email is correct.
  Ask an administrator to reactivate the account or reset access; there is no
  self-service reset page.
- **403 Forbidden:** the account may not have the role required for that
  action.
- **PWA changes do not appear:** reconnect, reload the page, and verify that
  the site is served over HTTPS or localhost.
- **Offline item needs attention:** compare the current server record and
  stock before submitting a replacement. Removing a local item does not undo
  a transaction already received by the server.
