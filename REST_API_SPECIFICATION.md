# REST API Specification

## 1. Purpose and Contract

This specification defines the version 1 HTTP API for the Predictive Inventory System. It is the contract between the React web application and Laravel 12 backend. All endpoints are rooted at `/api/v1`, use JSON, and enforce the engineering controls in `CLAUDE.md`.

The API is resource-oriented for ordinary reads and uses explicit action endpoints for controlled business-state transitions. It does not expose database models, internal exceptions, unrestricted exports, or direct stock mutation endpoints.

## 2. Global Standards

### 2.1 Headers and authentication

| Header | Required | Rules |
| --- | --- | --- |
| `Accept: application/json` | Yes | All API responses are JSON. |
| `Content-Type: application/json` | For bodies | Required for JSON request bodies. |
| `X-CSRF-TOKEN` | Session writes | Required by Sanctum cookie-session CSRF protection where applicable. |
| `X-Request-ID` | Recommended | Client-generated UUID for request tracing; server generates one if absent. |
| `Idempotency-Key` | Retry-prone writes | Required for POS finalization, receipt posting, offline sync, and other endpoints marked below. |
| Sanctum session cookie | Protected endpoints | Secure, HttpOnly, SameSite cookie. Tokens are never passed through browser storage. |

Authentication is `None` only for CSRF initialization, sign-in, and password-reset initiation/completion. All other endpoints require a valid Sanctum session. A `401` response means authentication is absent or expired; a `403` response means the authenticated user lacks the required permission, branch scope, or contextual approval authority.

### 2.2 Response envelopes

Successful single-resource responses return:

| Field | Type | Meaning |
| --- | --- | --- |
| `data` | object | Requested resource or operation result. |
| `meta.requestId` | UUID string | Server request correlation ID. |

Successful collection responses return:

| Field | Type | Meaning |
| --- | --- | --- |
| `data` | array | Current server-paginated result page. |
| `meta` | object | `requestId`, `page`, `perPage`, `total`, filter summary, and data freshness when relevant. |
| `links` | object | `first`, `previous`, `next`, and `last` pagination links. |

Successful state-transition responses return `data` with the changed resource, a `result` object that describes the accepted action, and `meta.requestId`. A response never exposes an Eloquent model directly.

### 2.3 Error envelope

All errors return the following safe envelope:

| Field | Type | Meaning |
| --- | --- | --- |
| `error.code` | string | Stable machine-readable code. |
| `error.message` | string | Safe, actionable user-facing message. |
| `error.details` | object | Optional structured details; validation errors are keyed by input field. |
| `error.requestId` | UUID string | Correlation ID for support and logs. |

| HTTP status | Error code family | Meaning |
| --- | --- | --- |
| `400` | `INVALID_REQUEST` | Malformed or unsupported request. |
| `401` | `UNAUTHENTICATED` | Missing, expired, or invalid session. |
| `403` | `FORBIDDEN` | Permission, branch scope, segregation-of-duties, or contextual policy denial. |
| `404` | `NOT_FOUND` | Resource does not exist within authorized scope. |
| `409` | `CONFLICT`, `VERSION_CONFLICT`, `DUPLICATE_OPERATION` | Concurrent edit, illegal state, or incompatible idempotency retry. |
| `422` | `VALIDATION_FAILED`, domain-specific validation code | Well-formed request violates validation or business invariant. |
| `429` | `RATE_LIMITED` | Too many requests. |
| `500` | `INTERNAL_ERROR` | Unexpected failure; no stack trace is disclosed. |
| `503` | `SERVICE_UNAVAILABLE` | Temporary operational unavailability. |

### 2.4 Common resource conventions

- Every identifier in a response is an opaque string public ID even if the database uses an internal numeric primary key.
- All timestamps use ISO 8601 UTC strings. All date-only values use `YYYY-MM-DD`.
- Money, decimal quantities, rates, EOQ, and ROP values are JSON strings to retain decimal precision.
- Collections accept `page`, `perPage` (maximum 100), `sort`, and feature-approved filter parameters. Invalid sort or filter values return `422`.
- Protected reads and writes are branch-scoped by the authenticated user’s authorized context. Owners do not bypass audit logging.
- Mutable resources return `version`. Clients send `If-Match-Version` on updates; missing or stale versions return `409 VERSION_CONFLICT` where an aggregate supports concurrency control.

## 3. Authentication and Session

### 3.1 Initialize CSRF protection

| Item | Specification |
| --- | --- |
| Endpoint | `/sanctum/csrf-cookie` |
| Method | `GET` |
| Authentication | None |
| Authorization | Public browser-session initialization only |
| Request body | None |
| Response body | Empty successful response with secure CSRF cookie. |
| Error responses | `429 RATE_LIMITED`, `503 SERVICE_UNAVAILABLE`. |
| Validation rules | Request must originate from an allowed first-party origin. |

### 3.2 Sign in

| Item | Specification |
| --- | --- |
| Endpoint | `/auth/login` |
| Method | `POST` |
| Authentication | None; CSRF protection required |
| Authorization | Public to eligible active accounts |
| Request body | `email` string; `password` string; optional `remember` boolean. |
| Response body | `data.user` with public profile, roles, permissions, authorized branches, and default branch; `data.session.expiresAt`. |
| Error responses | `401 UNAUTHENTICATED` for generic invalid credentials; `422 VALIDATION_FAILED`; `429 RATE_LIMITED`; `423 ACCOUNT_LOCKED` when policy lockout applies. |
| Validation rules | Email is normalized and must be valid; password is required; account must be active; failure message must not reveal account existence. |

### 3.3 Sign out

| Item | Specification |
| --- | --- |
| Endpoint | `/auth/logout` |
| Method | `POST` |
| Authentication | Sanctum session |
| Authorization | Authenticated user |
| Request body | None |
| Response body | `data.loggedOut: true`; session is invalidated and user-scoped client state must be cleared. |
| Error responses | `401 UNAUTHENTICATED`. |
| Validation rules | Active session is required; logout is idempotent from the browser perspective. |

### 3.4 Current session

| Item | Specification |
| --- | --- |
| Endpoint | `/auth/me` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | Authenticated user |
| Request body | None |
| Response body | `data` includes user profile, roles, effective permissions, authorized branches, default branch, and session expiry. |
| Error responses | `401 UNAUTHENTICATED`. |
| Validation rules | No body; disabled accounts have no usable session. |

### 3.5 Request password reset

| Item | Specification |
| --- | --- |
| Endpoint | `/auth/forgot-password` |
| Method | `POST` |
| Authentication | None; CSRF protection required |
| Authorization | Public |
| Request body | `email` string. |
| Response body | `data.accepted: true`; response is identical for known and unknown accounts. |
| Error responses | `422 VALIDATION_FAILED`, `429 RATE_LIMITED`, `503 NOTIFICATION_UNAVAILABLE`. |
| Validation rules | Email format required; reset delivery is rate-limited; no account enumeration. |

### 3.6 Complete password reset

| Item | Specification |
| --- | --- |
| Endpoint | `/auth/reset-password` |
| Method | `POST` |
| Authentication | None; CSRF protection required |
| Authorization | Valid reset token required |
| Request body | `email`, `token`, `password`, `passwordConfirmation`. |
| Response body | `data.passwordReset: true`; all prior sessions are invalidated. |
| Error responses | `400 INVALID_RESET_TOKEN`, `422 VALIDATION_FAILED`, `429 RATE_LIMITED`. |
| Validation rules | Token must be valid and unexpired; password must meet configured policy; confirmation must match. |

## 4. Users, Roles, Permissions, and Branch Scope

### 4.1 List users

| Item | Specification |
| --- | --- |
| Endpoint | `/users` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `users.read` within authorized administration scope |
| Request body | None; filters: `search`, `isActive`, `role`, `branchId`, `page`, `perPage`, `sort`. |
| Response body | Collection of user summaries: `id`, `name`, `email`, `isActive`, `roles`, `branches`, `lastLoginAt`, `version`. |
| Error responses | `401`, `403 FORBIDDEN`, `422` for unsupported filters. |
| Validation rules | `perPage` 1–100; role and branch filters must exist and be in caller scope. |

### 4.2 Create user

| Item | Specification |
| --- | --- |
| Endpoint | `/users` |
| Method | `POST` |
| Authentication | Sanctum session |
| Authorization | `users.create`; Owner-only where policy requires |
| Request body | `firstName`, `lastName`, `email`, optional `phone`, `roleIds`, `branchIds`, optional `defaultBranchId`, `isActive`. |
| Response body | Created user resource with assigned roles and branches. |
| Error responses | `401`, `403`, `409 DUPLICATE_EMAIL`, `422 VALIDATION_FAILED`. |
| Validation rules | Email unique and normalized; names required; roles and branches must exist; default branch must be assigned; caller may assign only allowed roles and branches. |

### 4.3 Read or update user

| Item | Read | Update |
| --- | --- | --- |
| Endpoint | `/users/{userId}` | `/users/{userId}` |
| Method | `GET` | `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `users.read` | `users.update`; role-change policy applies |
| Request body | None | Mutable profile fields, `isActive`, `roleIds`, `branchIds`, `defaultBranchId`, and `version`. |
| Response body | Full user resource with effective access. | Updated user resource. |
| Error responses | `401`, `403`, `404`, `422`. | `401`, `403`, `404`, `409 VERSION_CONFLICT`, `422`. |
| Validation rules | N/A. | Cannot remove last active Owner; default branch must be assigned; privileged changes require audit and may invalidate sessions. |

### 4.4 List roles, permissions, and branches

| Item | Roles | Permissions | Branches |
| --- | --- | --- | --- |
| Endpoint | `/roles` | `/permissions` | `/branches` |
| Method | `GET` | `GET` | `GET` |
| Authentication | Sanctum session | Sanctum session | Sanctum session |
| Authorization | `roles.read` | `permissions.read` | `branches.read` within scope |
| Request body | None | None | None; filters `isActive`, `search` |
| Response body | Role collection with permissions. | Permission collection grouped by module. | Branch collection with code, name, status, and scope. |
| Error responses | `401`, `403`. | `401`, `403`. | `401`, `403`, `422`. |
| Validation rules | No body. | No body. | Filter values must be supported. |

### 4.5 Create or update branch

| Item | Create | Update |
| --- | --- | --- |
| Endpoint | `/branches` | `/branches/{branchId}` |
| Method | `POST` | `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `branches.create`, Owner-only by policy | `branches.update`, Owner-only by policy |
| Request body | `code`, `name`, address fields, `countryCode`, optional `phone`, `isActive`. | Same mutable fields plus `version`. |
| Response body | Created branch resource. | Updated branch resource. |
| Error responses | `401`, `403`, `409 DUPLICATE_BRANCH_CODE`, `422`. | `401`, `403`, `404`, `409 VERSION_CONFLICT`, `422`. |
| Validation rules | Code unique; name and country required; active status boolean. | Posted history prevents physical deletion; retirement uses `isActive: false` and controlled soft delete only. |

## 5. Dashboard

### 5.1 Retrieve operational dashboard

| Item | Specification |
| --- | --- |
| Endpoint | `/dashboard` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `dashboard.read`; all included data is branch-scoped and permission-filtered |
| Request body | None; query `branchId`, `from`, `to`, optional `timezone`. |
| Response body | `data.kpis`, `data.lowStock`, `data.pendingPurchaseOrders`, `data.recentSales`, `data.salesTrend`, `data.forecastSummary`, `data.syncHealth`; every metric includes scope and freshness metadata. |
| Error responses | `401`, `403`, `422 INVALID_DATE_RANGE`. |
| Validation rules | Branch must be authorized; date range must be complete, bounded, and `from <= to`; timezone must be an approved IANA zone. |

## 6. Catalog Management

### 6.1 Categories

| Item | List | Create | Read / Update |
| --- | --- | --- | --- |
| Endpoint | `/categories` | `/categories` | `/categories/{categoryId}` |
| Method | `GET` | `POST` | `GET`, `PATCH` |
| Authentication | Sanctum session | Sanctum session | Sanctum session |
| Authorization | `categories.read` | `categories.create` | `categories.read`, `categories.update` |
| Request body | Filters `search`, `parentId`, `isActive`. | `code`, `name`, optional `parentCategoryId`, `description`, `isActive`. | None for read; mutable create fields plus `version` for update. |
| Response body | Paginated category summaries. | Created category. | Category resource with parent and child count. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `409 DUPLICATE_CATEGORY_CODE`, `422`. | `401`, `403`, `404`, `409 VERSION_CONFLICT`, `422`. |
| Validation rules | Valid filter IDs. | Code unique; parent cannot create a cycle; name unique under parent. | Referenced categories are deactivated rather than physically removed. |

### 6.2 Units of measure

| Item | List | Create / Update |
| --- | --- | --- |
| Endpoint | `/units-of-measure` | `/units-of-measure` and `/units-of-measure/{unitId}` |
| Method | `GET` | `POST`, `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `units.read` | `units.manage` |
| Request body | Optional `dimension`, `isActive`, `search`. | `code`, `name`, `symbol`, `dimension`, `isActive`; update includes `version`. |
| Response body | Unit collection. | Created or updated unit resource. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `404`, `409`, `422`. |
| Validation rules | Supported dimension filter only. | Code and name unique; dimension is controlled; referenced units are retired, not deleted. |

### 6.3 Products

| Item | List | Create | Read / Update |
| --- | --- | --- | --- |
| Endpoint | `/products` | `/products` | `/products/{productId}` |
| Method | `GET` | `POST` | `GET`, `PATCH` |
| Authentication | Sanctum session | Sanctum session | Sanctum session |
| Authorization | `products.read` | `products.create` | `products.read`, `products.update` |
| Request body | Filters `search`, `categoryId`, `isActive`, `productType`, `barcode`, pagination. | `categoryId`, `stockUnitId`, `sku`, optional `barcode`, `name`, optional `description`, `productType`, tracking flags, `defaultTaxRate`, `isActive`, `units`. | None for read; mutable fields, approved unit configuration, and `version` for update. |
| Response body | Paginated product summaries including stock status if caller has inventory permission. | Created product with units. | Product detail, supplier sources, units, and authorized branch inventory summary. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `409 DUPLICATE_SKU` or `DUPLICATE_BARCODE`, `422`. | `401`, `403`, `404`, `409 VERSION_CONFLICT`, `422`. |
| Validation rules | Filter scope and pagination bounded. | Category and stock unit active; SKU unique; barcode unique when present; stock product requires stock unit; unit conversions positive and dimension-compatible; at most one default purchase and sales unit. | Posted transaction history prevents destructive identity changes; inactive product cannot enter new sale or receipt. |

### 6.4 Product availability

| Item | Specification |
| --- | --- |
| Endpoint | `/products/{productId}/availability` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `inventory.read` or permitted POS product lookup; branch scope required |
| Request body | None; query `branchId`. |
| Response body | `data` includes `onHandQuantity`, `reservedQuantity`, `availableQuantity`, `incomingQuantity`, `lastMovementAt`, and freshness metadata. |
| Error responses | `401`, `403`, `404`, `422 MISSING_BRANCH_SCOPE`. |
| Validation rules | Product and branch must be active and authorized; response is advisory until server-side transaction finalization. |

## 7. Supplier Management

### 7.1 Suppliers

| Item | List | Create | Read / Update |
| --- | --- | --- | --- |
| Endpoint | `/suppliers` | `/suppliers` | `/suppliers/{supplierId}` |
| Method | `GET` | `POST` | `GET`, `PATCH` |
| Authentication | Sanctum session | Sanctum session | Sanctum session |
| Authorization | `suppliers.read` | `suppliers.create` | `suppliers.read`, `suppliers.update` |
| Request body | `search`, `isActive`, pagination. | `code`, `legalName`, optional `taxIdentifier`, contact and address fields, `countryCode`, `defaultCurrencyCode`, `isActive`. | None for read; mutable create fields plus `version` for update. |
| Response body | Supplier summaries. | Created supplier. | Supplier detail with contacts and product sources. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `409 DUPLICATE_SUPPLIER_CODE` or `DUPLICATE_TAX_IDENTIFIER`, `422`. | `401`, `403`, `404`, `409`, `422`. |
| Validation rules | Pagination bounded. | Code unique; legal name, country, and ISO currency required; tax identifier unique when present. | Referenced supplier must be deactivated, never physically deleted. |

### 7.2 Supplier contacts

| Item | Create | Update / retire |
| --- | --- | --- |
| Endpoint | `/suppliers/{supplierId}/contacts` | `/suppliers/{supplierId}/contacts/{contactId}` |
| Method | `POST` | `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `suppliers.update` | `suppliers.update` |
| Request body | `fullName`, optional `jobTitle`, `email`, `phone`, `isPrimary`, `isActive`. | Same mutable fields, optional `deletedAt`, and `version`. |
| Response body | Created contact. | Updated or retired contact. |
| Error responses | `401`, `403`, `404`, `422`. | `401`, `403`, `404`, `409`, `422`. |
| Validation rules | At least one contact channel required; only one active primary contact per supplier; retirement preserves procurement history. |

### 7.3 Supplier-product sources

| Item | List | Create / Update |
| --- | --- | --- |
| Endpoint | `/suppliers/{supplierId}/products` | `/suppliers/{supplierId}/products` and `/supplier-products/{supplierProductId}` |
| Method | `GET` | `POST`, `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `suppliers.read` | `suppliers.update` |
| Request body | Optional `isActive`, `isPreferred`, pagination. | `productId`, `purchaseUnitId`, optional `supplierSku`, `lastUnitCost`, `currencyCode`, `leadTimeDays`, `minimumOrderQuantity`, `orderMultiple`, `isPreferred`, `isActive`; update includes `version`. |
| Response body | Source collection. | Created or updated source with product and supplier details. |
| Error responses | `401`, `403`, `404`, `409 DUPLICATE_SUPPLIER_PRODUCT`, `422`. |
| Validation rules | Product must be active; purchase unit must belong to product and be purchase-enabled; costs and constraints non-negative; preferred-source policy allows at most one active preferred source per product where configured. |

## 8. Purchase Orders

### 8.1 List and create purchase orders

| Item | List | Create draft |
| --- | --- | --- |
| Endpoint | `/purchase-orders` | `/purchase-orders` |
| Method | `GET` | `POST` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `purchase_orders.read` within branch | `purchase_orders.create` within branch |
| Request body | Filters `branchId`, `supplierId`, `status`, `from`, `to`, `search`, pagination. | `branchId`, `supplierId`, `currencyCode`, optional `expectedReceiptAt`, `supplierReference`, `notes`, `lines`. Each line contains `productId`, `productUnitId`, `orderedQuantity`, `unitCost`, `taxRate`, `discountAmount`, optional expected receipt and note. |
| Response body | Paginated PO summaries with status and totals. | Draft PO with line snapshots, calculated totals, and `version`. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `409 DUPLICATE_PO_NUMBER`, `422`. |
| Validation rules | Branch authorized; date range valid. | Supplier active; product and purchase unit valid; quantities positive; money non-negative; at least one unique product line; totals computed server-side. |

### 8.2 Read and update draft purchase order

| Item | Read | Update draft |
| --- | --- | --- |
| Endpoint | `/purchase-orders/{purchaseOrderId}` | `/purchase-orders/{purchaseOrderId}` |
| Method | `GET` | `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `purchase_orders.read` | `purchase_orders.update` and editable state |
| Request body | None. | Same draft fields and complete replacement line set or explicit line operations; `version`. |
| Response body | PO detail, lines, approval history, and receipt summary. | Updated draft PO. |
| Error responses | `401`, `403`, `404`. | `401`, `403`, `404`, `409 VERSION_CONFLICT` or `ILLEGAL_STATE`, `422`. |
| Validation rules | Scoped record only. | Only draft or permitted returned state is editable; no duplicate lines; cannot invalidate existing receipt history. |

### 8.3 Submit, approve, reject, cancel, and close

| Action | Endpoint and method | Authorization | Request body | Response body | Errors and validation |
| --- | --- | --- | --- | --- | --- |
| Submit | `POST /purchase-orders/{id}/submit` | `purchase_orders.submit` | `version` | Submitted PO and required approval stages. | `409` if not draft; `422` if no valid lines, inactive supplier, or totals invalid. |
| Approve | `POST /purchase-orders/{id}/approvals` | `purchase_orders.approve`; separation-of-duties policy | `decision: "approved"`, optional `reason`, `version` | Approval event and updated PO status. | `403` self-approval or threshold denial; `409` duplicate/stale stage; `422` invalid decision. |
| Reject | `POST /purchase-orders/{id}/approvals` | `purchase_orders.approve` | `decision: "rejected"`, required `reason`, `version` | Rejection event and updated PO. | `422` reason required; `409` illegal state. |
| Mark ordered | `POST /purchase-orders/{id}/mark-ordered` | `purchase_orders.order` | `orderedAt`, optional `supplierReference`, `version` | Ordered PO. | `422` date required; `409` order not approved. |
| Cancel | `POST /purchase-orders/{id}/cancel` | `purchase_orders.cancel` | required `reason`, `version` | Cancelled PO. | `403` policy denial; `409` received quantities or illegal state; `422` reason required. |
| Close | `POST /purchase-orders/{id}/close` | `purchase_orders.close` | optional `reason`, `version` | Closed PO. | `409` outstanding receipt policy; `422` reason required if incomplete. |

All actions require Sanctum authentication. Every successful action writes approval or audit history; no action physically deletes a PO.

## 9. Goods Receiving

### 9.1 List and create receipt draft

| Item | List | Create draft |
| --- | --- | --- |
| Endpoint | `/goods-receipts` | `/goods-receipts` |
| Method | `GET` | `POST` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `goods_receipts.read` | `goods_receipts.create` within branch |
| Request body | Filters `branchId`, `purchaseOrderId`, `status`, `from`, `to`, `supplierDeliveryNumber`, pagination. | `purchaseOrderId` or approved direct-receipt context, `branchId`, `receivedAt`, optional `supplierDeliveryNumber`, `notes`, `lines`. Lines contain `purchaseOrderLineId` when applicable, `productId`, `productUnitId`, received, accepted, and rejected quantities, cost, traceability data, rejection reason, and note. |
| Response body | Receipt summary collection. | Draft receipt with validated line context and `version`. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `409 DUPLICATE_DELIVERY_REFERENCE`, `422`. |
| Validation rules | Scope and pagination valid. | PO must be approved/ordered as policy requires; receipt branch matches PO; accepted plus rejected equals received; tracked product requirements enforced; quantity tolerance applied. |

### 9.2 Read or update receipt draft

| Item | Read | Update |
| --- | --- | --- |
| Endpoint | `/goods-receipts/{goodsReceiptId}` | `/goods-receipts/{goodsReceiptId}` |
| Method | `GET` | `PATCH` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `goods_receipts.read` | `goods_receipts.update` and draft state |
| Request body | None. | Draft fields, lines, and `version`. |
| Response body | Receipt detail with lines and linked movements after posting. | Updated draft receipt. |
| Error responses | `401`, `403`, `404`. | `401`, `403`, `404`, `409`, `422`. |
| Validation rules | Scoped record only. | Posted and reversed receipts are immutable; all line validations from creation apply. |

### 9.3 Post or reverse receipt

| Action | Endpoint and method | Authentication / authorization | Request body | Response body | Errors and validation |
| --- | --- | --- | --- | --- |
| Post | `POST /goods-receipts/{id}/post` | Sanctum; `goods_receipts.post`; `Idempotency-Key` required | `version`, optional `postNote` | Posted receipt, movement IDs, updated PO receipt summary, and result status. | `409` stale version, duplicate idempotency key, or illegal state; `422` stock/traceability/tolerance validation. |
| Reverse | `POST /goods-receipts/{id}/reverse` | Sanctum; `goods_receipts.reverse`; manager policy | required `reason`, `version`, `Idempotency-Key` | Reversal receipt or compensating result with linked movements. | `403` threshold/policy denial; `409` already reversed; `422` reason required and downstream reversal policy validation. |

Posting is atomic: receipt state, accepted stock movements, inventory-balance projection, PO line received totals, PO status, idempotency record, and audit event commit together.

## 10. Inventory Monitoring, Adjustments, and Movement History

### 10.1 Inventory balances and monitoring

| Item | Specification |
| --- | --- |
| Endpoint | `/inventory/balances` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `inventory.read` within requested branch |
| Request body | None; filters `branchId` required, `productId`, `categoryId`, `availability` (`in_stock`, `low_stock`, `out_of_stock`), `search`, pagination. |
| Response body | Balance collection with product, `onHandQuantity`, `reservedQuantity`, `availableQuantity`, `incomingQuantity`, reorder status, last movement, and freshness. |
| Error responses | `401`, `403`, `422 MISSING_BRANCH_SCOPE` or invalid filters. |
| Validation rules | Branch required and authorized; stock numbers are read-only; large result sets are server-paginated. |

### 10.2 Inventory movement history

| Item | Specification |
| --- | --- |
| Endpoint | `/inventory/movements` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `inventory.movements.read` within branch |
| Request body | None; filters `branchId`, `productId`, `movementType`, `referenceType`, `referenceId`, `from`, `to`, `actorUserId`, pagination. |
| Response body | Movement collection containing type, signed quantity, balance-after diagnostic, reference summary, actor, effective time, posted time, and correlation ID. |
| Error responses | `401`, `403`, `422 INVALID_DATE_RANGE` or invalid type. |
| Validation rules | Branch scope required; date range bounded; movements are immutable and no write method exists. |

### 10.3 List and create inventory adjustment

| Item | List | Create draft |
| --- | --- | --- |
| Endpoint | `/inventory/adjustments` | `/inventory/adjustments` |
| Method | `GET` | `POST` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `inventory.adjustments.read` | `inventory.adjustments.create` within branch |
| Request body | Filters `branchId`, `status`, `reasonCode`, `from`, `to`, pagination. | `branchId`, `reasonCode`, optional `reasonNote`, `effectiveAt`, `lines`; each line has `productId`, `quantityDelta`, optional `unitCost`, `notes`. |
| Response body | Adjustment summaries. | Draft adjustment showing before, delta, after, cost impact, approval requirement, and `version`. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `422`, `409` on concurrent balance change if preview cannot be safely produced. |
| Validation rules | Authorized scope and date range. | Reason code controlled; at least one line; unique product per document; delta non-zero; effective time valid; negative stock or threshold policy may require approval. |

### 10.4 Read, update, approve, post, or reverse adjustment

| Action | Endpoint and method | Authorization | Request body | Response body | Errors and validation |
| --- | --- | --- | --- | --- |
| Read | `GET /inventory/adjustments/{id}` | `inventory.adjustments.read` | None | Adjustment with lines, approval status, and linked movements. | `401`, `403`, `404`. |
| Update draft | `PATCH /inventory/adjustments/{id}` | `inventory.adjustments.update` | Draft fields, lines, `version` | Updated draft and recalculated preview. | `409` stale/illegal state; `422` line and policy rules. |
| Approve | `POST /inventory/adjustments/{id}/approve` | `inventory.adjustments.approve`; segregation policy | `version`, optional `reason` | Approved adjustment. | `403` self-approval or threshold denial; `409` state conflict. |
| Post | `POST /inventory/adjustments/{id}/post` | `inventory.adjustments.post`; `Idempotency-Key` required | `version` | Posted adjustment, immutable movement IDs, and balance result. | `409` duplicate/stale/illegal state; `422` policy or stock validation. |
| Reverse | `POST /inventory/adjustments/{id}/reverse` | `inventory.adjustments.reverse` | required `reason`, `version`, `Idempotency-Key` | Compensating adjustment and linked reversal movements. | `403`, `409 already reversed`, `422`. |

All adjustment posting and reversal paths atomically write the document state, immutable movement facts, balance projection, idempotency record, and audit event.

## 11. Point of Sale and Sales Recording

### 11.1 POS product lookup

| Item | Specification |
| --- | --- |
| Endpoint | `/pos/products` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `pos.use`; branch scope required |
| Request body | None; query `branchId`, `query`, optional `barcode`, `page`, `perPage`. |
| Response body | Product collection with sale-enabled units, authorized current price, available quantity advisory, tax, and tracking flags. |
| Error responses | `401`, `403`, `422 MISSING_BRANCH_SCOPE`. |
| Validation rules | Branch authorized; search length bounded; inactive or non-sale products excluded. |

### 11.2 Finalize sale

| Item | Specification |
| --- | --- |
| Endpoint | `/sales` |
| Method | `POST` |
| Authentication | Sanctum session |
| Authorization | `pos.finalize` within branch; overrides require explicit additional authority |
| Request body | `branchId`, `soldAt`, `currencyCode`, `lines`, `payments`, optional `notes`, optional `approvedByUserId`; `Idempotency-Key` required. Each line includes `productId`, `productUnitId`, `quantity`, optional requested `unitPrice`, optional `discountAmount`, and required `overrideReason` where policy applies. Payments include `paymentMethod`, `amount`, optional `externalReference`. |
| Response body | Completed sale with receipt number, line and payment snapshots, totals, inventory movement references, and operation result. |
| Error responses | `401`, `403 PRICE_OVERRIDE_FORBIDDEN` or `DISCOUNT_FORBIDDEN`, `409 DUPLICATE_OPERATION` or `STOCK_CONFLICT`, `422 INSUFFICIENT_STOCK`, `PAYMENT_TOTAL_MISMATCH`, `INVALID_PRODUCT_UNIT`, or field validation errors. |
| Validation rules | At least one unique product line; quantities positive; product and unit sale-enabled; server recalculates price, tax, discounts, and total; payment total equals sale total; stock availability is revalidated in transaction; no raw payment credentials accepted. |

### 11.3 List and read sales

| Item | List | Read |
| --- | --- | --- |
| Endpoint | `/sales` | `/sales/{saleId}` |
| Method | `GET` | `GET` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `sales.read` within branch; sensitive cost fields separately permissioned | `sales.read` within branch |
| Request body | Filters `branchId`, `status`, `cashierUserId`, `from`, `to`, `saleNumber`, pagination. | None. |
| Response body | Sale summaries with number, status, totals, cashier, and time. | Sale detail with immutable lines, payments, movements, reversals, and audit summary. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `404`. |
| Validation rules | Branch and date scope required by policy. | Scope enforcement prevents cross-branch lookup. |

### 11.4 Void or refund sale

| Action | Endpoint and method | Authorization | Request body | Response body | Errors and validation |
| --- | --- | --- | --- | --- |
| Void | `POST /sales/{id}/void` | `sales.void`; manager or approved override policy | required `reason`, `version`, `Idempotency-Key` | Voided sale and compensating movement/payment outcome. | `403`, `409 already reversed`, `422` reason and return-stock policy. |
| Refund | `POST /sales/{id}/refunds` | `sales.refund`; manager or approved override policy | `lines` with quantities, `payments`, required `reason`, `Idempotency-Key` | Refund sale document and linked original sale. | `403`, `409 quantity already refunded`, `422` line, payment, and policy rules. |

Voids and refunds never edit a completed sale; they create auditable compensating documents and stock movements.

## 12. Forecasting, EOQ, ROP, and Restocking

### 12.1 Forecast runs

| Item | List | Create run | Read run |
| --- | --- | --- | --- |
| Endpoint | `/forecast-runs` | `/forecast-runs` | `/forecast-runs/{forecastRunId}` |
| Method | `GET` | `POST` | `GET` |
| Authentication | Sanctum session | Sanctum session | Sanctum session |
| Authorization | `forecasting.read` | `forecasting.run` | `forecasting.read` |
| Request body | Filters `branchId`, `modelCode`, `status`, `from`, `to`, pagination. | `branchId`, `modelCode: "sma"`, `periodGrain`, `windowPeriods`, `historyStartDate`, `historyEndDate`, optional `productIds`. | None. |
| Response body | Forecast run summaries. | Queued or completed run with parameters, cutoff, and status. | Run detail with result items, input summary, cold-start status, and model version. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `422 INSUFFICIENT_HISTORY`, `INVALID_PERIOD_WINDOW`, `INVALID_DATE_RANGE`; `409` if duplicate active run policy applies. | `401`, `403`, `404`. |
| Validation rules | Scope valid. | SMA only; window must meet configured minimum and maximum; history periods complete; product IDs active and in scope; partial current period is excluded. |

### 12.2 Forecast item detail and manual planning override

| Item | Detail | Override |
| --- | --- | --- |
| Endpoint | `/forecast-runs/{forecastRunId}/items/{productId}` | `/forecast-runs/{forecastRunId}/items/{productId}/manual-plan` |
| Method | `GET` | `POST` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `forecasting.read` | `forecasting.override` |
| Request body | None. | `manualQuantity`, `reason`, `expiresAt`. |
| Response body | Item with demand periods, forecast result, source cutoff, and limitations. | Updated immutable planning annotation or follow-on override record. |
| Error responses | `401`, `403`, `404`. | `401`, `403`, `404`, `422`. |
| Validation rules | Item must belong to run. | Quantity non-negative; reason required; expiry future and bounded; override is audited and does not rewrite original SMA output. |

### 12.3 EOQ calculations

| Item | Calculate | List history |
| --- | --- | --- |
| Endpoint | `/reorder-policies/{reorderPolicyId}/eoq-calculations` | `/reorder-policies/{reorderPolicyId}/eoq-calculations` |
| Method | `POST` | `GET` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `planning.eoq.calculate` | `planning.eoq.read` |
| Request body | `annualDemandQuantity`, `orderingCost`, `annualHoldingCostPerUnit`, `currencyCode`, optional constraint snapshot. | None; pagination. |
| Response body | EOQ snapshot with raw quantity, recommendation, formula version, input values, constraints, and validity status. | Calculation history collection. |
| Error responses | `401`, `403`, `404`, `422 INVALID_EOQ_INPUT`. | `401`, `403`, `404`, `422`. |
| Validation rules | Values must be compatible and non-negative; holding cost must be greater than zero; recommendation considers MOQ, order multiple, and policy constraints but does not create a PO. |

### 12.4 Reorder policies and ROP

| Item | List | Create | Read / Update | Recalculate |
| --- | --- | --- | --- | --- |
| Endpoint | `/reorder-policies` | `/reorder-policies` | `/reorder-policies/{id}` | `/reorder-policies/{id}/recalculate-rop` |
| Method | `GET` | `POST` | `GET`, `PATCH` | `POST` |
| Authentication | Sanctum | Sanctum | Sanctum | Sanctum |
| Authorization | `planning.rop.read` | `planning.rop.manage` | `planning.rop.read`, `planning.rop.manage` | `planning.rop.calculate` |
| Request body | Filters `branchId`, `productId`, `isActive`. | `branchId`, `productId`, optional `preferredSupplierProductId`, `safetyStockQuantity`, `safetyStockBasis`, optional `leadTimeDaysOverride`, `leadTimeBasis`, `isActive`. | None for read; mutable policy values plus `version` for update. | Optional `forecastRunId`; no arbitrary ROP value. |
| Response body | Policy collection with latest ROP and source data freshness. | Created policy. | Full policy, ROP breakdown, selected lead time, safety stock, and alert status. | Updated policy with derived ROP result. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `409 DUPLICATE_REORDER_POLICY`, `422`. | `401`, `403`, `404`, `409`, `422`. | `401`, `403`, `404`, `422 MISSING_DEMAND_OR_LEAD_TIME`. |
| Validation rules | Branch scope required. | One policy per product-branch; supplier source must match product; safety stock non-negative. | ROP is derived, never directly edited. | Lead time uses configured calendar/business-day convention; demand and safety stock units must match stock unit. |

### 12.5 Restocking alerts

| Item | List | Read | Acknowledge | Resolve / dismiss |
| --- | --- | --- | --- | --- |
| Endpoint | `/restocking-alerts` | `/restocking-alerts/{alertId}` | `/restocking-alerts/{alertId}/acknowledge` | `/restocking-alerts/{alertId}/resolve` and `/restocking-alerts/{alertId}/dismiss` |
| Method | `GET` | `GET` | `POST` | `POST` |
| Authentication | Sanctum | Sanctum | Sanctum | Sanctum |
| Authorization | `restocking.read` | `restocking.read` | `restocking.acknowledge` | `restocking.resolve` |
| Request body | Filters `branchId`, `status`, `severity`, `assignedToUserId`, pagination. | None. | optional `assignedToUserId`, `note`, `version`. | Resolve: `reason`, `version`; dismiss: required `reason`, `version`. |
| Response body | Alert summaries with quantities, severity, and recommendation. | Alert detail with calculation snapshots and event history. | Updated alert and event. | Updated alert and event. |
| Error responses | `401`, `403`, `422`. | `401`, `403`, `404`. | `401`, `403`, `404`, `409`, `422`. | `401`, `403`, `404`, `409`, `422`. |
| Validation rules | Branch scope required. | Scoped record only. | Only active alert can be acknowledged. | Resolution requires verified recovery or approved replenishment state; dismissal reason is mandatory and auditable. |

## 13. Reports and Exports

### 13.1 Report catalog

| Item | Specification |
| --- | --- |
| Endpoint | `/reports` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `reports.read`; catalog is permission-filtered |
| Request body | None. |
| Response body | Available report definitions with code, title, permitted formats, filters, data classification, and required permission. |
| Error responses | `401`, `403`. |
| Validation rules | No body; unauthorized definitions are omitted. |

### 13.2 Run interactive report

| Item | Specification |
| --- | --- |
| Endpoint | `/reports/{reportCode}` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | Report-specific permission and branch scope |
| Request body | None; report-approved filters including `branchId`, date range, product, supplier, status, category, pagination, and sort. |
| Response body | `data.columns`, paginated `data.rows`, aggregates, filter summary, timezone, currency, generated time, data cutoff, freshness, and access classification. |
| Error responses | `401`, `403`, `404 UNKNOWN_REPORT`, `422 INVALID_REPORT_FILTER`. |
| Validation rules | Only documented filters accepted; branch scope enforced; range and pagination bounded; report cannot expose unauthorized financial or contact data. |

### 13.3 Request export

| Item | Specification |
| --- | --- |
| Endpoint | `/report-exports` |
| Method | `POST` |
| Authentication | Sanctum session |
| Authorization | Corresponding report export permission; export-specific access policy |
| Request body | `reportCode`, `format` (`pdf`, `csv`, `xlsx`), `filters`, optional `timezone`; `Idempotency-Key` required for retry-safe export creation. |
| Response body | Export resource with `id`, `status`, report/filter snapshot, `requestedAt`, optional `expiresAt`, and polling link. |
| Error responses | `401`, `403`, `409 DUPLICATE_OPERATION`, `422 INVALID_EXPORT_FORMAT` or invalid filters, `429 EXPORT_LIMIT_REACHED`. |
| Validation rules | Format must be supported by report; filters must be authorized; export request limits and retention policy apply. |

### 13.4 Read or download export

| Item | Status | Download |
| --- | --- | --- |
| Endpoint | `/report-exports/{exportId}` | `/report-exports/{exportId}/download` |
| Method | `GET` | `GET` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | Requester or explicitly authorized report-download role; access rechecked at download | Same |
| Request body | None. | None. |
| Response body | Export metadata: status, format, requested time, cutoff, expiry, safe failure code. | `data.downloadUrl` short-lived signed URL or a controlled file response; download is audited. |
| Error responses | `401`, `403`, `404`. | `401`, `403`, `404`, `409 EXPORT_NOT_READY`, `410 EXPORT_EXPIRED`. |
| Validation rules | Export must be within scope and retention window. | Completed, unexpired file required; authorization is evaluated again even for known export ID. |

## 14. Audit Trail

### 14.1 Search audit trail

| Item | Specification |
| --- | --- |
| Endpoint | `/audit-logs` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `audit.read`; Owner or authorized Manager only |
| Request body | None; filters `branchId`, `actorUserId`, `eventType`, `entityType`, `entityId`, `correlationId`, `from`, `to`, pagination. |
| Response body | Append-only event collection with actor, role snapshot, scope, event type, entity references, timestamp, correlation ID, and redacted change summary. |
| Error responses | `401`, `403`, `422 INVALID_DATE_RANGE` or invalid filter. |
| Validation rules | Date range bounded; sensitive fields are redacted; audit-log search itself is audited. |

### 14.2 Read audit event

| Item | Specification |
| --- | --- |
| Endpoint | `/audit-logs/{auditLogId}` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | `audit.read` within applicable branch scope |
| Request body | None. |
| Response body | Audit event with structured, redacted before/after values, metadata, schema version, request ID, and correlation ID. |
| Error responses | `401`, `403`, `404`. |
| Validation rules | Immutable record; viewing is audited; no write or delete method exists. |

## 15. System Settings

### 15.1 List and read settings

| Item | List | Read |
| --- | --- | --- |
| Endpoint | `/settings` | `/settings/{settingKey}` |
| Method | `GET` | `GET` |
| Authentication | Sanctum session | Sanctum session |
| Authorization | `settings.read` | `settings.read` |
| Request body | Query `branchId`, optional `prefix`. | Optional `branchId`. |
| Response body | Permission-filtered setting collection with key, type, scope, value where non-sensitive, version, and description. | Setting resource. |
| Error responses | `401`, `403`, `404`, `422`. | `401`, `403`, `404`, `422`. |
| Validation rules | Branch override scope must be authorized; sensitive values are redacted unless caller has explicit sensitive-setting permission. |

### 15.2 Create or update setting

| Item | Specification |
| --- | --- |
| Endpoint | `/settings/{settingKey}` |
| Method | `PUT` |
| Authentication | Sanctum session |
| Authorization | `settings.manage`; Owner-only for policy and security settings |
| Request body | `branchId` nullable for global scope, `valueType`, `value`, `version` for existing setting. |
| Response body | Updated setting metadata and safe value representation. |
| Error responses | `401`, `403`, `409 VERSION_CONFLICT`, `422 INVALID_SETTING_VALUE`. |
| Validation rules | Key must exist in approved registry; type must match registry; value must meet range and schema rules; sensitive values are encrypted/redacted and never echoed; every change is audited. |

## 16. Offline Synchronization

### 16.1 Synchronize queued operations

| Item | Specification |
| --- | --- |
| Endpoint | `/sync/operations` |
| Method | `POST` |
| Authentication | Sanctum session |
| Authorization | Approved offline-operation permission, active user, and branch scope per operation |
| Request body | `operations` array in dependency order. Each operation includes `clientOperationId` UUID, `operationType`, `branchId`, `payloadVersion`, optional `dependencyOperationId`, `idempotencyKey`, and typed `payload`. |
| Response body | `data.results` array keyed by `clientOperationId`; each result contains `status` (`accepted`, `rejected`, `conflicted`, `pending_dependency`), `serverResource`, `error` when applicable, and `serverVersion`. |
| Error responses | `401`, `403`, `409 DUPLICATE_OPERATION` or `SYNC_CONFLICT`, `422 UNSUPPORTED_OFFLINE_OPERATION` or payload validation failure, `429`, `503`. Per-operation business failures return in `data.results` so unrelated operations can be reported deterministically. |
| Validation rules | Maximum batch size configured; every ID must be unique; payload version supported; dependency must be accepted first; operation type must be approved offline; same operation ID or idempotency key cannot carry a changed payload hash. |

### 16.2 Read synchronization operation status

| Item | Specification |
| --- | --- |
| Endpoint | `/sync/operations/{clientOperationId}` |
| Method | `GET` |
| Authentication | Sanctum session |
| Authorization | Originating user or authorized support role within branch scope |
| Request body | None. |
| Response body | Operation status, resolution time, safe error code, server resource reference, and conflict payload when user is allowed to resolve it. |
| Error responses | `401`, `403`, `404`. |
| Validation rules | Client operation ID must be UUID; status cannot be altered by this endpoint. |

## 17. API Workflow Guarantees

- All stock-affecting endpoints use a backend transaction that commits source document state, inventory movement facts, balance projection, idempotency record, and audit event together.
- Finalized sales, posted receipts, inventory movements, audit logs, forecast snapshots, and completed export records are immutable. Corrections use explicit reversal or compensating action endpoints.
- Domain actions return `409` for concurrent edits and illegal state transitions rather than silently applying last-write-wins semantics.
- Forecasting, EOQ, ROP, dashboard, and reports expose their source scope, cutoff, assumptions, and freshness. Their outputs are planning information, not automatic purchasing commands.
- All exports, audit views, reports, and branch-scoped resources re-evaluate authorization on every request, including download requests.
- API documentation, OpenAPI artifacts when introduced, validation schemas, frontend types, and automated contract tests must remain aligned with this specification.
