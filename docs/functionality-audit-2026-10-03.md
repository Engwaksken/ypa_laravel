# Functionality and modal audit — 3 October 2026

## Scope and result

Audited the local Laravel migration against the local legacy PHP source associated with https://ypa.kemmytech.com/. Reviewed 19 modal definitions across 12 view files, form submissions, AJAX handlers, routes, permissions, report queries, and the existing feature suite. Preserved changes already present in the workspace, including the pending reports module.

The initial PHP run had 222 tests with 26 errors, all in the pending report tests. The final verified run has 232 passing tests and 844 assertions. Nine JavaScript regression checks pass. Blade templates compile successfully. These checks establish local behavior; they do not certify every visual interaction or production workflow.

## Fixed issues

| Area | Finding and repair |
| --- | --- |
| Projects, project categories, expenses | Multipart PUT requests did not deliver form fields reliably to PHP 8.2. Use POST with `_method=PUT`. |
| Project edit | Disabled project code was omitted from FormData although validation requires it. Explicitly include the code; prevent saving while edit data is loading. |
| Normal modal forms | Validation redirects discarded entered values and left modals closed. Restore the originating form, submitted values, edit method, and same-origin action; reopen it with errors. |
| Product, category, branch forms | Unchecked active flags were missing from creation requests and defaulted to true. Include explicit zero values. |
| Meeting invites and attendance | Saving before loading could submit an empty selection. Disable saves until successful loading and during submission; surface save/network failures. |
| Guest checkout | Cart JSON was validated as an array without being decoded. Decode it before validation; reject malformed JSON, repeated products, and inactive products or branches. |
| Checkout result modal | Opened before native form validation and always claimed success in its title. Open it after validation, show actual progress/errors, allow dismissal and retry, reject empty carts, and prevent overlapping submissions. Reset product quantities after success. |
| Checkout transaction | Regression coverage confirms insufficient stock rolls back customer/order creation and leaves stock unchanged. |
| Order status modal | Offered invalid transitions and retained the previous order's selection. Restrict and reset options using the current order status. |
| Validation messages | Errors disappeared after six seconds. Keep errors/warnings visible until dismissed; expire success/info notices only. |
| Report fixtures | Wrong permission column, nonexistent factories, missing timestamps/soft-delete fields, and invented financial fields prevented useful execution. Align fixtures with actual model fields. |
| Stock/low-stock/business reports | Remaining quantities were recalculated using sales across branches. Use each stock row's current quantity and derive the difference from its own total. |
| Expiry report | Excluded already-expired rows, reversed the date difference, omitted a closure variable, and valued original rather than remaining stock. Correct classification, filtering, and values. |
| Report filters | Missing selected filter variables caused view errors. Provide them. Validate calendar dates rather than accepting impossible dates. |
| Financial report | Queried nonexistent `order_id`, `payment_method`, and `projects.contract_amount` fields. Use transaction dates, collected amounts, project IDs, payment method relations, and project names. Avoid counting project collections twice in net totals. |
| Branch reports | Financial branch detail and business profit/product quantities could include other branches. Scope the calculations and displayed rows to the selected branch. |

## Modal inventory

| View | Modals |
| --- | --- |
| branches/index | branchModal |
| customers/index | customerModal |
| expenses/index | expenseModal, expenseDeleteModal, bulkUploadModal |
| layouts/partials/confirm-delete | ypaConfirmDeleteModal |
| meetings/show | inviteModal, attendanceModal |
| orders/index | statusModal |
| orders/place | orderResult |
| products/index | productModal, categoryModal |
| project-categories/index | categoryModal, categoryDeleteModal |
| projects/index | projectModal, viewModal, deleteModal |
| stock/index | stockModal |
| suppliers/index | supplierModal |

The categoryModal identifier appears on two separate pages, so it is not a duplicate within a document. Inspection did not confirm the initially suspected hidden-method reset bug; no fix is claimed for that suspicion.

## Remaining migration gaps and verification limits

- **Point of Sales is not migrated.** The Laravel `sales.index` route is absent. Dashboard sale links fall back to `#`, and the sidebar hides the missing module. The legacy source includes sales entry, products, payment, and branch endpoints. This requires a complete sales workflow, stock/payment integration, and its database schema; the audit did not create a substitute route that would conceal the gap.
- **Financial ledger parity is incomplete.** The legacy financial report includes ledger-based income/expense, trial balance, balance sheet, and cash-flow queries. The current Laravel report provides transaction/expense/harvest summaries and project/branch breakdowns. It does not reproduce those accounting statements. Its project cost summary is an estimate from purchase price and quantity, not the legacy ledger.
- **Order completion parity needs reconciliation.** The legacy process_order_status.php creates sales/sale_items and updates inventory when an order completes, while guest_order_api.php already reserves inventory at checkout. The Laravel status endpoint updates order status only. A migration should reconcile this inconsistency using actual production records before adding financial postings or a second stock deduction.
- Live access was unsuccessful: the web reader reported the URL inaccessible, and the in-app browser failed with “target closed while handling command.” No authenticated live account or production database was inspected, and no production writes or deployment were performed.
- No visual browser walkthrough was completed. Bootstrap/CDN availability, responsive layout, keyboard focus, all role-specific interactions, SMTP/OTP delivery, PDF output, upload storage permissions, and concurrency on production MySQL remain unverified.

## Reproduce verification

From the Laravel directory:

```powershell
& C:/xampp/php/php.exe vendor/phpunit/phpunit/phpunit
& 'C:/Users/HumbleKen/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node.exe' tests/js/modal-workflows.test.cjs
& C:/xampp/php/php.exe artisan view:cache
```

The JavaScript checks execute isolated handlers with a lightweight DOM fixture, rather than a full browser. The PHP checks use the configured in-memory SQLite test database and synthetic records. Neither suite touches production data.
