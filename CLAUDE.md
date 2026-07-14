# Predictive Inventory System Engineering Handbook

## Handbook Authority and Interpretation

- This handbook is the default engineering standard for all application code, schema, infrastructure, tests, documentation, and automated changes in this repository.
- More restrictive legal, security, privacy, contractual, or production-operations requirements take precedence over this handbook.
- A documented architectural decision record approved by the engineering owner may supersede a rule only for its explicitly stated scope and expiry or review date.
- If rules conflict, protect data integrity first, then security, auditability, availability, usability, performance, and implementation convenience.
- “Must” and “never” are mandatory requirements; “should” is mandatory unless a written, reviewable reason records why the exception is safe.
- No automation, including AI-assisted changes, may infer approval to relax controls, alter business policy, or make destructive production changes.

## 1. Project Vision

- Build the authoritative inventory and sales platform for Steven Hydrotech Exponent Water Treatment and Supply Services.
- Treat inventory quantities, financial values, operational history, and forecasts as business-critical records.
- Support dependable daily operations in connected and intermittent-network environments.
- Present complex inventory intelligence in a clear, fast, and auditable web experience.
- Design for maintainability, controlled change, and growth across branches, users, and product lines.

## 2. Business Requirements

- Maintain accurate on-hand, reserved, available, and incoming inventory for every stock-managed product.
- Record every stock-affecting event as an immutable movement with an accountable source document.
- Enforce role-specific access to data, workflows, approvals, settings, and reports.
- Support purchasing, receiving, adjustments, point-of-sale transactions, sales recording, and restocking.
- Produce forecast, EOQ, ROP, sales, inventory, supplier, and audit reports from verified operational data.
- Operate safely offline and synchronize pending work without silently losing valid user changes.

## 3. Project Goals

- Deliver correct inventory before optimizing visual novelty or introducing optional features.
- Minimize manual calculation through validated automation and explainable recommendations.
- Make stock exceptions, low-stock conditions, pending approvals, and synchronization state immediately visible.
- Keep the frontend independently testable and the backend independently deployable.
- Make all important state transitions traceable to an authenticated actor, timestamp, and business reason.

## 4. System Scope

- Include authentication, RBAC, master data, procurement, receiving, inventory, POS, forecasting, alerts, reports, audit, and settings.
- Include offline-first workflows only where local persistence and reconciliation are explicitly supported.
- Treat financial accounting, payroll, banking, and supplier payment settlement as out of scope unless separately approved.
- Do not expose direct database administration or unrestricted data exports through normal application screens.
- Implement integrations behind explicit contracts; never couple core workflows to a vendor-specific API.

## 5. Target Users

- Owners require strategic visibility, full governance controls, and controlled access to business-wide information.
- Managers require operational oversight, approvals, procurement control, reporting, and inventory planning.
- Staff require focused, least-privilege access for assigned sales, receiving, and inventory tasks.
- All user interfaces must favor plain operational language over internal technical terminology.
- User flows must support both experienced operators and infrequent users without weakening controls.

## 6. Role Permissions

### Owner

- Owners may manage users, roles, system settings, all master data, approvals, reports, and audit records.
- Owners may view all branches, all financial inventory metrics, and all historical records.
- Owner-only actions that materially alter policy must require explicit confirmation and audit logging.

### Manager

- Managers may manage products, categories, suppliers, purchase orders, receiving, inventory, POS, reports, and alerts.
- Managers may approve or reject controlled workflows only when assigned the applicable permission.
- Managers must not manage owner accounts, alter immutable audit entries, or bypass authorization checks.

### Staff

- Staff may access only assigned operational workflows and the minimum reference data required to complete them.
- Staff may create transactions but may not approve their own controlled transactions unless policy explicitly permits it.
- Staff must never access user administration, global settings, sensitive cost reports, or unscoped exports.

## 7. Development Philosophy

- Prefer boring, explicit, observable solutions over clever abstractions with hidden behavior.
- Optimize for correctness at state boundaries: validation, authorization, persistence, synchronization, and reporting.
- Model business rules once in the backend and mirror them in the frontend only to improve usability.
- Make decisions reversible where possible and preserve historical truth where reversals are not allowed.
- Treat every feature as an operational workflow, not merely a collection of screens.

## 8. Engineering Principles

- Enforce a single source of truth for persisted inventory, authorization, and business calculations.
- Keep domain logic independent from controllers, components, transport details, and vendor libraries.
- Fail closed for access control, stock mutation, synchronization, and financial-impacting operations.
- Design idempotent write operations wherever retries, queues, devices, or offline behavior are involved.
- Measure before optimizing and document any deliberate trade-off in the relevant code or decision record.

## 9. Architecture Rules

- Use a modular monolith: frontend and Laravel API are separate applications with versioned HTTP contracts.
- Dependencies must point inward toward domain rules; UI and framework code must not own business decisions.
- Controllers and React pages orchestrate work but do not contain pricing, inventory, or forecasting logic.
- All stock changes must pass through the inventory domain service and create a movement record atomically.
- Do not introduce microservices, event brokers, or new infrastructure without an approved architectural decision.
- Maintain an explicit branch/location scope in every inventory-bearing aggregate, query, report, cache key, and authorization decision.
- Use UTC for persisted instants and render dates in the configured business timezone; never store ambiguous local timestamps.
- Define stable domain state machines for purchase orders, receipts, sales, alerts, and synchronization operations; reject undocumented transitions.
- Treat notifications, analytics, search indexes, and caches as projections that may be rebuilt from authoritative transactional data.

## 10. Folder Structure Standards

- Organize frontend code by feature, with shared primitives separated from feature-specific modules.
- Organize Laravel code by responsibility and bounded domain, keeping application services discoverable.
- Place tests near their layer conventions and name them after observable behavior.
- Do not create catch-all `utils`, `helpers`, `common`, or `misc` directories for domain logic.
- Keep generated files, build artifacts, local credentials, and machine-specific configuration out of source control.
- Place cross-cutting configuration in named modules with owners; do not scatter policy constants through pages, controllers, and jobs.
- Keep database migrations forward-only, ordered, and independently deployable from application code where rolling deployments require it.

## 11. React Architecture

- Use React, TypeScript, Vite, React Router, Tailwind CSS, TanStack Query, Zustand, React Hook Form, and Zod as approved foundations.
- Build feature modules with pages, components, hooks, schemas, types, API functions, and tests grouped by capability.
- Keep route loaders, guards, layouts, and route metadata centralized and declarative.
- Use presentational components for rendering and feature hooks or services for orchestration.
- Never call Laravel endpoints directly from JSX; route all remote access through typed API service functions.

## 12. Laravel Architecture

- Use Laravel 12 and PHP 8.4 features conservatively, favoring readable typed code over framework magic.
- Keep controllers thin: authorize, validate, invoke an application service, and return a resource response.
- Use Form Requests for request validation and API Resources for outward response shaping.
- Put transactional workflows in services and persistence queries in repositories when query complexity warrants separation.
- Use policies, gates, queues, events, notifications, and scheduled commands only behind clear domain ownership.

## 13. API Design Standards

- Version externally consumed APIs under `/api/v1` and preserve backward compatibility within a released version.
- Use resource-oriented nouns, correct HTTP methods, predictable plural collection endpoints, and stable identifiers.
- Return a consistent JSON envelope with data, meta, links when paginated, and machine-readable error codes.
- Validate every input server-side; client validation is advisory and never a security boundary.
- Require idempotency keys for retry-prone create operations such as POS sales, receiving, and offline synchronization.
- Document every endpoint, permission, payload, side effect, error code, and pagination behavior.
- Use ISO 8601 UTC timestamps, ISO 4217 currency codes, and explicitly documented decimal string formats in JSON contracts.
- Use `409 Conflict` for version or business-state conflicts, `422 Unprocessable Content` for validation failures, and `403 Forbidden` for denied authorization.
- Do not expose internal exception messages, ORM attributes, database IDs from unrelated scopes, or stack traces in API responses.
- Support request correlation through an accepted or generated correlation ID and return it in error responses and relevant headers.

## 14. Database Standards

- Use MySQL with InnoDB, utf8mb4, strict SQL modes, foreign keys, and UTC timestamps.
- Use migrations for every schema change and seeders only for controlled reference or development data.
- Store money as integer minor units or fixed-point decimals; never use floating-point types for currency.
- Use decimal columns with explicit precision and scale for quantities, costs, rates, and forecast values.
- Enforce uniqueness, non-null constraints, foreign keys, and check constraints where supported by the business rule.
- Preserve stock history through append-only movements; do not update historical quantities in place.
- Store business-effective dates separately from `created_at` and `updated_at` when a transaction can be recorded after it occurred.
- Use soft deletion only for master data that needs recoverability; never soft-delete facts that require immutable historical status.
- Set an application-level concurrency token or version on aggregates vulnerable to concurrent editing, including products, purchase orders, and settings.
- Design migration rollbacks conservatively: production rollback must not discard data created by the forward migration.

## 15. Naming Conventions

- Use clear, domain-first names: `PurchaseOrderApprovalService` is preferred over `ApprovalManager`.
- Use English singular nouns for entities, plural nouns for collections, and verbs for commands and actions.
- Name booleans as questions or states such as `isActive`, `hasPermission`, and `canReceive`.
- Name dates with their meaning and timezone expectation, such as `receivedAt` and `forecastPeriodStart`.
- Avoid abbreviations unless universally understood in the domain, including `SKU`, `EOQ`, `ROP`, and `POS`.

## 16. File Naming Rules

- Use PascalCase for React components, pages, PHP classes, and their matching filenames.
- Use camelCase for TypeScript non-component modules, hooks after the `use` prefix, and utility functions.
- Use kebab-case for route segments, asset names, and migration descriptions.
- Name test files with the unit under test and `.test.tsx`, `.test.ts`, or Laravel test conventions.
- Do not use vague filenames such as `index`, `main`, `new`, `final`, `temp`, or `helpers` except framework entry points.

## 17. Component Rules

- Components must have a single rendering responsibility and explicit typed props.
- Keep components deterministic: derive display values from props, hooks, or declared state rather than hidden globals.
- Prefer composition over prop flags that create unrelated modes and untestable branching.
- Extract a component when it has a reusable semantic role, independently testable behavior, or meaningful complexity.
- Do not embed business calculations, raw API calls, or permission rules inside leaf presentation components.

## 18. Page Rules

- Each page owns route-level layout, title, access guard, data composition, and page-specific empty states.
- Pages must render a clear loading, error, empty, unauthorized, and success state where each is possible.
- Keep pages thin by delegating panels, tables, forms, and workflow mechanics to feature components.
- Maintain URL state for filters, sorting, pagination, dates, and tabs when users need shareable or refresh-safe context.
- Provide descriptive document titles and accessible headings for every route.

## 19. Hooks Rules

- Custom hooks must start with `use` and expose a stable, documented contract.
- Use hooks to encapsulate reusable behavior, query composition, form orchestration, and UI state transitions.
- Do not hide imperative side effects that surprise callers or make authorization decisions obscure.
- Keep dependency arrays correct; do not silence lint rules to mask stale closures.
- Return the minimum necessary API and avoid unstable object or function references unless intentionally memoized.

## 20. Service Layer Rules

- Services encapsulate business workflows that cross validation, authorization, persistence, events, or external boundaries.
- A service method must communicate one business action and return a meaningful domain result.
- Wrap multi-table state changes in a database transaction with explicit failure handling.
- Services must validate invariants even when invoked outside HTTP controllers, including queued jobs and commands.
- Do not let services return unbounded ORM models directly to transport or presentation layers.
- Define domain exceptions or result types for expected business refusals; reserve unexpected exceptions for genuine faults.
- Emit domain events only after the database transaction commits, and make event consumers safe to retry.

## 21. Repository Pattern Rules

- Introduce repositories for complex, reusable persistence queries or when they isolate infrastructure from domain services.
- Keep repositories focused on retrieval and persistence; do not place workflow policy inside them.
- Return typed domain-friendly data and avoid leaking query-builder details to callers.
- Use eager loading intentionally and make query filtering, sorting, and pagination explicit.
- Do not create repositories as empty pass-through wrappers around a single model.

## 22. State Management Rules

- Classify state as server state, URL state, local component state, form state, or durable client state before implementation.
- Use the smallest scope that correctly owns state and avoid duplicating the same fact across stores.
- Server state belongs in TanStack Query; forms belong in React Hook Form; global UI state may belong in Zustand.
- Persist client state only when it materially improves continuity and has a defined invalidation strategy.
- Never use client state as the authority for inventory availability, permissions, or finalized transaction status.
- Use explicit state machines or discriminated unions for workflows that can be pending, queued, conflicted, rejected, completed, or reversed.
- Do not persist ephemeral UI concerns such as open popovers, transient errors, or focus targets across browser sessions.

## 23. TanStack Query Rules

- Define query keys centrally by feature and include all variables that affect result identity.
- Set stale times intentionally based on volatility; inventory and POS data require conservative freshness.
- Invalidate or update affected queries after successful mutations; never rely on accidental rerendering.
- Use mutations for server writes and surface pending, success, retry, and error states in the workflow UI.
- Do not cache secrets, credentials, raw access tokens, or sensitive data beyond the authorized session need.
- Cancel or ignore obsolete in-flight queries when filters, user scope, or route identity changes to prevent stale response overwrites.
- Treat mutation success as server acceptance, not merely HTTP transport success; surface business warnings and partial outcomes explicitly.

## 24. Zustand Rules

- Use Zustand only for cross-route client state that is not server-cache or form state.
- Split stores by bounded purpose such as UI preferences, POS cart draft, and synchronization queue indicators.
- Expose selector-friendly state and actions; avoid one monolithic application store.
- Persist only approved, non-sensitive durable state and version persisted schemas for migrations.
- Reset user-scoped stores on logout, user switch, authorization loss, and relevant organization context changes.

## 25. Form Validation Rules

- Define Zod schemas for frontend validation and equivalent Laravel Form Request rules for backend enforcement.
- Validate immediately enough to help users, but avoid disruptive error messages before a field has been meaningfully touched.
- Use semantic field labels and actionable messages that state how to correct invalid input.
- Validate cross-field constraints such as receiving quantities, date ranges, and reorder parameters at form level.
- Disable duplicate submission while a mutation is pending and preserve entered data after recoverable errors.
- Normalize user-entered whitespace, case, decimal separators, and unit inputs consistently before validation without altering meaningful raw notes.
- Require confirmation phrases or second-factor workflow approval only where policy marks an action as high impact; do not use confirmation as authorization.

## 26. TypeScript Rules

- Enable strict TypeScript settings and resolve type errors without `any`, unsafe casts, or lint suppressions.
- Prefer interfaces for object contracts and discriminated unions for workflow states and API outcomes.
- Generate or maintain shared API types deliberately; do not infer critical contracts from unvalidated JSON.
- Use `unknown` for untrusted values and narrow them with schemas or type guards.
- Model nullable, optional, pending, failed, and unavailable states explicitly.
- Keep frontend domain calculations in named pure functions with unit tests; they may guide users but must not replace backend decisions.
- Use branded or opaque types where practical for IDs, currency minor units, quantities, and dates that are easy to confuse.

## 27. Tailwind Rules

- Use Tailwind utilities as the default styling approach and keep styling colocated with component markup.
- Use approved design tokens for color, spacing, typography, radius, shadows, and breakpoints.
- Extract repeated visual patterns into components, variants, or semantic utility classes rather than copying long class strings.
- Do not use arbitrary values without a documented design reason and reviewable consistency.
- Avoid inline style objects except for dynamic values that cannot be represented safely with utilities.

## 28. UI Consistency Rules

- Use one visual language for page headers, cards, fields, tables, badges, empty states, and confirmation patterns.
- Reserve semantic colors for stable meanings: success, warning, danger, information, and neutral state.
- Do not encode status using color alone; pair it with text, iconography, or a visible label.
- Keep terminology consistent across navigation, forms, reports, help text, and backend error messages.
- Prefer clarity and density appropriate for operations work over decorative effects.

## 29. Animation Guidelines

- Use Framer Motion only to clarify hierarchy, feedback, and state transitions.
- Keep standard interactions subtle, short, interruptible, and respectful of reduced-motion preferences.
- Do not delay data visibility, form submission, navigation, or urgent alerts for animation.
- Avoid looping, bouncing, flashing, and large-area movement in operational screens.
- Animate layout changes only when the resulting motion remains predictable for keyboard and screen-reader users.

## 30. Accessibility Rules

- Meet WCAG 2.2 AA for all user-facing workflows and test keyboard-only paths before release.
- Use semantic HTML, labels, headings, landmarks, native buttons, and native inputs whenever possible.
- Ensure visible focus, logical focus order, focus trapping in dialogs, and focus restoration after dialogs close.
- Provide text alternatives for icons, charts, and non-text controls; hide purely decorative elements from assistive technology.
- Verify sufficient color contrast, error identification, zoom behavior, and touch target sizing.
- Ensure data visualizations have an accessible table, summary, or downloadable equivalent that communicates their decision-relevant values.
- Announce asynchronous status changes such as save completion, sync conflict, and export readiness without excessive live-region noise.

## 31. Responsive Design Standards

- Design mobile-first while ensuring data-dense desktop workflows remain efficient.
- Support current evergreen browsers at practical mobile, tablet, laptop, and large desktop widths.
- Reflow controls before shrinking them below usable sizes; preserve essential action visibility.
- Convert complex tables to deliberate responsive variants rather than allowing unreadable horizontal compression.
- Test POS, receiving, stock monitoring, and approval workflows on touch devices and narrow screens.

## 32. Enterprise Dashboard Design Guidelines

- Lead with actionable operational metrics, exceptions, pending work, and time-bounded performance indicators.
- Clearly label the date range, branch scope, currency, refresh time, and data freshness for every KPI.
- Use charts to reveal trends and comparisons, never as a substitute for accessible numerical summaries.
- Prioritize low stock, stockout risk, pending purchase orders, recent sales, and forecast-driven actions.
- Avoid vanity metrics, decorative charts, and dashboard widgets that do not support a user decision.
- Make dashboard calculations link to a drill-down list with the same filters, scope, and snapshot basis whenever feasible.
- Separate observed facts, derived recommendations, and user-entered targets so operators do not confuse their authority.

## 33. Table Design Standards

- Use stable column order, clear headers, predictable alignment, and a unique row identifier.
- Right-align numeric quantities, currency, percentages, and dates where comparison benefits from alignment.
- Provide server-driven filtering, sorting, pagination, empty states, and loading indicators for material datasets.
- Make destructive row actions explicit and avoid icon-only actions without accessible labels and tooltips.
- Preserve filter context when navigating to a record and returning to its list.
- Provide bulk actions only where every selected row is authorized and eligible; validate selection again on the server.
- State whether a displayed total applies to the current page, filtered result set, or full scoped dataset.

## 34. Modal Standards

- Use modals only for focused confirmation, short forms, or decisions that do not require deep navigation.
- Use full pages or drawers for complex workflows such as receiving, purchase order editing, and detailed reports.
- Every modal must have a descriptive title, accessible close control, Escape handling, and explicit primary action.
- Destructive confirmations must state the consequence, affected record, and whether the action is reversible.
- Do not nest modals or use them for passive information that can appear inline.

## 35. Button Standards

- Use a single primary action per visual region and distinguish secondary, tertiary, danger, and loading states.
- Button labels must begin with a clear verb such as Save, Create, Receive, Approve, Export, or Cancel.
- Disable buttons only with an adjacent explanation when the reason is not obvious from the page state.
- Guard destructive actions with confirmation and permission checks at both client and server layers.
- Ensure buttons have adequate target size, visible focus, disabled semantics, and no duplicate-click vulnerability.

## 36. Loading States

- Show loading state within the context being loaded, not as a global blocking overlay unless the workflow cannot proceed.
- Preserve prior valid data during background refetching and communicate freshness when it affects decisions.
- Use progress indicators for known-duration tasks such as exports, bulk imports, and synchronization.
- Do not show indefinite spinners without an error threshold, retry path, or supportable diagnostic identifier.
- Keep layout stable while content loads to prevent accidental actions and visual jumping.

## 37. Skeleton Loaders

- Use skeletons for initial content regions when the final layout is predictable.
- Match the final content hierarchy and dimensions closely enough to prevent layout shift.
- Do not use skeletons for actions requiring immediate status, such as payment, receiving confirmation, or stock mutation.
- Pair prolonged skeleton loading with accessible loading text for assistive technologies.
- Remove skeletons promptly when data, empty state, or error state becomes available.

## 38. Error Handling

- Distinguish validation, authorization, conflict, network, server, and unexpected errors in both API and UI behavior.
- Return safe error messages to users and record technical context server-side without exposing secrets.
- Offer retry only where retry is safe; use idempotency protections for repeatable writes.
- Preserve user input after validation or transient transport failures whenever possible.
- Escalate inventory and synchronization conflicts as actionable workflow states, never silent fallbacks.
- Assign stable machine-readable error codes and correlation IDs to supportable errors so the frontend can act without parsing prose.
- For unexpected failures, show a safe recovery path and retain diagnostics server-side with enough context to investigate.

## 39. Toast Notifications

- Use toasts for brief confirmations and non-blocking errors; use inline messaging for field or page-level problems.
- State what happened, name the affected object when useful, and provide one relevant next action when needed.
- Do not place critical, irreversible, or lengthy information only in a toast.
- Deduplicate repetitive notifications and prevent toast floods during batch actions or synchronization.
- Use accessible live-region behavior and respect user focus without stealing it.

## 40. Offline Mode

- Offline support applies only to explicitly approved workflows and must show an unmistakable connectivity and sync status.
- Cache only the minimum reference and operational data needed for approved offline activities.
- Mark offline-created records as pending until the server acknowledges durable acceptance.
- Block operations requiring live authorization, fresh stock truth, or server-only approval when offline.
- Never imply that a locally stored change is finalized before synchronization succeeds.
- Display the age of cached operational data and warn users when a workflow depends on potentially stale stock or prices.
- Define maximum offline session duration, storage quotas, and forced reauthentication behavior in the security configuration.

## 41. IndexedDB Synchronization Rules

- Use Dexie with versioned schemas, explicit migrations, and a documented retention policy.
- Queue mutations with an immutable client-generated operation ID, actor context, payload version, timestamp, and dependency metadata.
- Synchronize in deterministic order and make server handlers idempotent by operation ID.
- Encrypting sensitive local data requires an approved key-management design; otherwise do not store it offline.
- Clear user-scoped IndexedDB data reliably on logout while preserving recoverable pending operations only when policy permits.
- Include an idempotency key and causal dependency chain for every queued operation; never replay a dependent operation before its prerequisite succeeds.
- Use exponential backoff with jitter for retryable failures and stop automatic retries for validation, authorization, and conflict outcomes.
- Synchronization must be observable through queue counts, last-success time, per-operation status, error code, and user-remediable actions.

## 42. Conflict Resolution Rules

- Detect conflicts using server version, updated timestamp, or concurrency token rather than last-write-wins by default.
- Auto-resolve only commutative, non-sensitive changes with documented deterministic rules.
- Require user or manager review for conflicting stock, price, supplier, purchase order, receiving, and sales data.
- Present local value, server value, change author, timestamps, and allowed resolution actions.
- Record conflict detection and final resolution in the audit trail with correlation to the original operation.
- Never resolve competing financial values, stock quantities, or finalized transaction states by timestamp alone without an approved domain rule.
- Preserve the rejected local operation for inspection until the user dismisses it or a retention policy safely expires it.

## 43. POS Design Rules

- Optimize POS for fast, keyboard-friendly product lookup, barcode input, cart review, quantity adjustment, and final confirmation.
- Revalidate product availability, selling price, discounts, taxes, and authorization on the server at sale finalization.
- Treat a completed sale as an atomic transaction that creates a sales record, payment record if in scope, and stock movements.
- Support clear suspended, cancelled, refunded, and completed states; never overwrite completed sales.
- Require explicit authorization for price overrides, discounts beyond policy, voids, refunds, and offline finalization.
- Treat cash drawer, payment method, receipt numbering, and tax reporting requirements as configuration-driven and jurisdiction-sensitive.
- On finalization failure, leave the cart recoverable but prevent ambiguous duplicate retry through the original idempotency key.

## 44. Inventory Business Rules

- Stock-managed products must have a unique SKU, unit of measure, category, active status, and valid inventory policy.
- On-hand inventory changes only through approved receiving, sales, adjustments, transfers if introduced, returns, or reversals.
- Available inventory equals on-hand minus committed quantities; never allow negative available stock without explicit policy.
- Costing method, tax treatment, and valuation behavior must be configured centrally and applied consistently.
- Inventory reports must derive from movement history and reconciled balances, not editable summary fields alone.
- Define units of measure and permitted conversions centrally; do not silently convert purchase, stocking, and selling units.
- Support lot, serial, expiry, and water-treatment-specific traceability only when enabled per product policy, then enforce it at receiving and sale.
- Run periodic reconciliation checks that compare balance projections with movement-derived totals and raise controlled exceptions.

## 45. Supplier Rules

- Suppliers require a unique business identity, contact details, active status, lead-time information, and audit history.
- Maintain supplier-product relationships with supplier SKU, quoted or last cost, lead time, minimum order quantity, and preferred flag.
- Prevent duplicate suppliers through normalized identity checks and manager-reviewed merge procedures.
- Deactivate suppliers rather than deleting referenced suppliers with historical procurement records.
- Supplier contact data must be access-controlled and excluded from unauthorized exports.

## 46. Purchase Order Rules

- Purchase orders progress through explicit draft, submitted, approved, ordered, partially received, received, cancelled, and closed states.
- Only authorized users may approve, cancel, reopen, or materially change a purchase order after submission.
- Capture supplier, branch or destination, ordered lines, quantities, unit cost, expected date, tax, notes, and approval history.
- Prevent receiving quantities beyond authorized tolerance without a documented manager override.
- Preserve line-level snapshots of ordered cost and product identity to protect historical reporting from later edits.
- Define approval thresholds using total approved value, category risk, branch scope, and segregation-of-duties policy.
- A requester must not be the sole approver of their own purchase order when the configured approval policy requires independent review.

## 47. Receiving Workflow

- Receive against an approved purchase order unless a policy-approved direct receiving workflow is explicitly enabled.
- Validate supplier, product, remaining quantity, unit of measure, lot or serial requirements, and destination before posting.
- Post received quantities and inventory movements atomically; partial receipt must update purchase order status correctly.
- Record shortages, overages, damage, rejected goods, attachments, notes, receiving user, and receiving timestamp.
- Do not permit editing a posted receipt; correct mistakes through a controlled reversal or adjustment workflow.
- Require receiving reference documents, supplier delivery details, and quality inspection status when configured for a product or supplier.
- Prevent duplicate receipt posting by source document number, supplier, and idempotency key with an override process for legitimate duplicates.

## 48. Inventory Adjustment Workflow

- Require a specific adjustment reason code, quantity delta, source context, and accountable user for every adjustment.
- Require manager approval for adjustments beyond configured thresholds or for any negative-value impact policy requires.
- Calculate and display before quantity, delta, after quantity, cost impact, and related movement before confirmation.
- Post adjustments atomically and create immutable movement, approval, and audit records.
- Correct posted adjustments through compensating adjustments; never edit or delete historical movements.

## 49. Inventory Movement Rules

- Every inventory movement must include product, location or branch, movement type, signed quantity, reference type, reference ID, actor, and timestamp.
- Use controlled movement types such as receipt, sale, adjustment, return, reservation, release, and reversal.
- Movement records are append-only and must retain their original business reference even after source records are archived.
- Reversals must reference the original movement and use an equal and opposite quantity with a documented reason.
- Balance projections may be stored for performance but must be reproducible from validated movement history.

## 50. Forecasting Rules

### Simple Moving Average

- Calculate SMA from finalized historical demand periods using a clearly selected period length and time grain.
- Exclude cancelled, voided, duplicate, test, and unfinalized transactions from demand history.
- Make the selected demand source, period boundaries, and resulting forecast explainable in the UI and reports.
- Calculate demand using a declared net-sales policy that states treatment of returns, cancellations, free goods, and adjustments.
- Run forecast jobs against a consistent data cutoff and do not mix partially posted current periods into completed-period SMA inputs.

### Minimum period

- Require at least two complete historical periods before producing an SMA forecast.
- Use a configured minimum that aligns with the reporting grain and seasonal behavior of the product.
- Clearly label products that do not meet the minimum data requirement as insufficient history.

### Maximum period

- Enforce a configured maximum window to avoid smoothing away operationally relevant changes.
- Reject periods longer than available complete history or approved system limits.
- Store configured defaults centrally and allow overrides only for authorized planning roles.

### Historical forecasts

- Store forecast runs with product, parameters, source data cutoff, generated time, model version, and results.
- Never recalculate saved historical reports using changed source data without clearly marking a new run.
- Preserve prior forecast outputs for auditability, comparison, and accuracy analysis.

### Validation

- Validate positive integer period length, complete date ranges, active product scope, and non-negative demand data.
- Flag anomalous inputs and zero-demand patterns without silently changing the selected method.
- Test forecast calculations against independently reviewed fixtures before release.
- Validate that selected history uses a continuous period series; missing periods represent zero demand only when the business calendar confirms no activity.
- Show forecast confidence limitations and input sufficiency; SMA is a planning aid and never an asserted future fact.

### Cold Start Strategy

- For insufficient history, display no numerical SMA forecast and provide a clear operational status.
- Allow authorized planners to use approved manual planning inputs with reason, expiry, and audit record.
- Do not fabricate demand using unrelated products unless an approved forecasting policy defines a similarity model.

## 51. EOQ Rules

### Formula

- Calculate EOQ as the square root of `(2 × annual demand × ordering cost) ÷ annual holding cost`.
- Define each factor in documented units and ensure annual demand, ordering cost, and holding cost use compatible periods and currency.
- Return no EOQ when denominator values are zero, negative, missing, or operationally invalid.
- Treat holding cost as annual cost per unit, whether derived from carrying-cost rate times unit cost or configured directly; never mix the two definitions.

### Implementation

- Execute EOQ calculations in a tested domain service with decimal-safe values and explicit rounding policy.
- Allow calculation parameters to come from controlled system defaults or authorized product overrides.
- Present the recommendation with its assumptions, date of calculation, and constraint-aware final order quantity.

### Validation

- Validate demand, ordering cost, holding cost rate, unit cost, supplier constraints, and product activation before calculation.
- Flag outlier recommendations requiring manager review rather than automatically creating a purchase order.
- Never treat EOQ as a mandatory quantity when cash, storage, shelf-life, or supplier constraints conflict.

### Storage

- Persist EOQ calculation snapshots with input parameters, formula version, output, rounding, and actor or scheduler identity.
- Retain prior snapshots for reports and auditability; do not overwrite a historical recommendation.
- Invalidate displayed recommendations when a relevant product, cost, demand, or supplier parameter changes.

## 52. ROP Rules

### Formula

- Calculate reorder point as expected demand during lead time plus safety stock.
- Use consistent time units when converting average demand and lead-time values.
- Evaluate ROP against available inventory and approved incoming supply according to documented replenishment policy.

### Safety Stock

- Store safety stock as a non-negative quantity with an explicit basis: policy minimum, service level, or approved planner override.
- Require authorization and audit logging for manual safety-stock overrides.
- Surface safety stock separately from expected lead-time demand in planning views.

### Lead Time

- Use supplier-product lead time as the primary value and product default only when supplier data is unavailable.
- Store lead time in whole or decimal days according to a single system convention.
- Recalculate ROP recommendations when supplier lead time changes materially.
- Define whether lead time uses calendar days or business days once at system level and apply that convention consistently to all calculations.

## 53. Restocking Rules

- Generate alerts when available inventory reaches or falls below the applicable reorder point.
- Rank alerts by stockout risk, demand velocity, lead time, safety stock exposure, and business criticality.
- Deduplicate active alerts by product and scope while retaining history of alert state transitions.
- Alert generation must not automatically purchase stock; users must review recommendation, constraints, and supplier choice.
- Resolve an alert only through verified stock recovery, approved replenishment state, or documented dismissal reason.
- Include open purchase order quantities only when their status and expected receipt date meet the configured reliability policy.
- Evaluate alerts on schedule and after material stock, sales, receipt, demand, lead-time, or reorder-policy changes.

## 54. Reports Rules

- Reports must declare scope, filters, timezone, currency, generated time, source freshness, and access classification.
- Use server-side pagination, aggregation, and export generation for large report datasets.
- Support PDF through DomPDF and CSV or XLSX through Laravel Excel using consistent column definitions.
- Protect exports with authorization, audit logging, file naming standards, and retention controls.
- Reconcile inventory and sales reports against source transactions and movement records before release.
- Generate exports asynchronously when they exceed interactive limits, authorize download at retrieval time, and expire files under retention policy.
- Stamp generated reports with version, filter summary, data cutoff, and whether values are live, cached, or snapshot-based.

## 55. Audit Trail Rules

- Audit create, update, delete, approval, authentication, authorization failure, export, synchronization, and inventory-affecting actions.
- Capture actor, role, action, entity type, entity ID, correlation ID, IP where available, timestamp, and before/after changes.
- Make audit records append-only and restrict access to authorized owner and manager roles.
- Redact secrets, credentials, tokens, and unnecessarily sensitive personal data from audit payloads.
- Never use audit logging as a substitute for domain history; preserve both when their purposes differ.
- Store audit changes as structured, schema-versioned data and make diff rendering tolerant of renamed or retired fields.
- Access to audit records must itself be audited, including searches and exports of sensitive operational history.

## 56. Security Standards

- Apply defense in depth across browser, API, database, infrastructure, logging, and operational processes.
- Use HTTPS, secure headers, CSRF protection where applicable, input validation, output encoding, and rate limiting.
- Store secrets only in managed environment configuration; never commit them, return them, or log them.
- Follow least privilege for accounts, database users, storage, queues, CI, and operational access.
- Patch dependencies deliberately, review advisories, and test security-impacting upgrades before deployment.
- Define data classification for public, internal, confidential, and restricted data, then enforce storage, display, export, and retention controls accordingly.
- Use centralized security headers, content security policy, secure cookies, clickjacking protection, and strict transport security in production.
- Perform dependency scanning, secret scanning, static analysis, and periodic access review in the delivery pipeline.
- Establish incident response ownership, severity definitions, evidence preservation, notification thresholds, and post-incident follow-up.

## 57. Authentication Standards

- Use Laravel Sanctum with secure cookie-based session authentication for the first-party web application.
- Rotate session identifiers after login and invalidate sessions appropriately on logout, password change, and account disablement.
- Enforce password policy, rate limits, account lockout safeguards, and secure reset flows.
- Provide generic authentication failure messages that do not reveal whether an account exists.
- Never persist authentication tokens in localStorage or IndexedDB.
- Require multifactor authentication for owners and privileged administration when the organization’s identity capability supports it.
- Record successful and failed sign-in, password reset, session revocation, and privilege-change events without logging credentials.

## 58. Authorization Standards

- Enforce authorization in Laravel policies, gates, or middleware for every protected resource and action.
- Treat frontend guards as usability features only; API authorization is mandatory on every request.
- Scope records by authorized branch, ownership, role, and assigned capabilities before returning data.
- Deny by default when a permission, tenant scope, or contextual rule is absent or ambiguous.
- Test authorization separately for Owner, Manager, and Staff across read, create, update, approve, export, and delete actions.

## 59. Performance Rules

- Set measurable budgets for page load, interaction latency, API response time, query count, and export completion.
- Optimize high-frequency workflows first: POS search, stock lookup, receiving, dashboard exceptions, and report filtering.
- Paginate large collections and avoid loading unbounded records into browser memory or JSON responses.
- Profile actual bottlenecks before adding caches, indexes, queues, or complex client optimizations.
- Preserve correctness and observability when improving speed.
- Establish service-level objectives for interactive requests, queued work, synchronization recovery, and critical data freshness.
- Load test high-concurrency inventory writes and demonstrate that retries do not produce duplicate movements or sales.

## 60. Caching Rules

- Cache derived, non-authoritative, read-heavy data only with explicit keys, TTLs, invalidation triggers, and ownership.
- Do not cache personalized authorization results or inventory quantities without safe scope and freshness guarantees.
- Include organization, branch, role, locale, filters, and version in cache keys whenever they affect output.
- Invalidate related cache entries on writes rather than relying solely on expiration.
- Instrument hit rate, miss rate, invalidation failures, and stale-data incidents for significant caches.

## 61. React Performance Rules

- Avoid premature memoization; use profiling to justify `memo`, `useMemo`, and `useCallback`.
- Virtualize only genuinely large lists and preserve keyboard navigation, accessibility, and predictable row measurement.
- Code-split route-level modules and defer noncritical charts, exports, and heavy dialogs.
- Keep query data normalized enough to prevent repeated transformations on every render.
- Avoid rendering hidden expensive panels, repeatedly recreating charts, and broad store subscriptions.

## 62. Laravel Performance Rules

- Prevent N+1 queries through intentional eager loading and query-count tests for high-volume endpoints.
- Move long-running exports, bulk imports, report generation, alert evaluation, and notifications to queues.
- Keep queue jobs idempotent, bounded, retry-safe, observable, and protected from duplicate execution.
- Use pagination, cursor pagination where appropriate, database aggregation, and streaming exports for large data.
- Do not perform network calls inside database transactions unless the business operation explicitly requires it.

## 63. Database Performance Rules

- Index foreign keys, high-selectivity filters, common sort keys, and composite query patterns verified by query plans.
- Review `EXPLAIN` output for material report, dashboard, POS, and inventory-monitoring queries.
- Avoid wildcard searches and leading-wildcard predicates on large operational tables without an approved search strategy.
- Partitioning, denormalization, and summary tables require benchmarks, ownership, reconciliation, and migration plans.
- Schedule backups, verify restores, monitor replication or availability strategy, and test recovery procedures.
- Define recovery point and recovery time objectives, test restores against production-like data, and record evidence of each recovery exercise.
- Monitor connection saturation, lock waits, deadlocks, slow queries, storage growth, backup age, and failed migration status.

## 64. Testing Standards

- Write tests for behavior, invariants, authorization, validation, state transitions, calculations, and regression risks.
- Use Laravel feature tests for API workflows, unit tests for domain rules, and frontend tests for components and user flows.
- Test SMA, EOQ, ROP, stock movements, receiving, POS finalization, adjustments, and synchronization with deterministic fixtures.
- Include negative and boundary cases: zero demand, missing lead time, duplicate operations, partial receiving, and concurrent updates.
- Do not merge skipped, flaky, timing-dependent, or assertion-free tests as evidence of coverage.
- Use factories and builders with valid defaults; make invalid states explicit in tests rather than accidentally producing inconsistent fixtures.
- Test concurrent edits, idempotency retries, queue retry behavior, authorization scope leaks, and rollback paths for critical workflows.
- Maintain contract tests for versioned APIs and migration tests for data transformations that change persisted semantics.

## 65. Logging Standards

- Use structured logs with timestamp, level, request or correlation ID, actor context where safe, operation, and relevant entity IDs.
- Log server failures, authorization denials, integration failures, queue failures, sync failures, and performance thresholds.
- Never log passwords, sessions, CSRF tokens, access tokens, personal contact details, or full payment information.
- Use appropriate levels: debug for development diagnostics, info for lifecycle events, warning for recoverable risk, error for failures.
- Retain logs according to approved policy and make critical incidents traceable across API, job, and client correlation IDs.
- Use centralized error tracking with release version, environment, correlation ID, and safe user context to accelerate incident diagnosis.
- Alert on user-impacting failure rates, queue backlogs, sync failure growth, stock reconciliation failures, and backup or restore failures.

## 66. Git Workflow

- Use short-lived branches named with type and scope, such as `feat/pos-sale-finalization` or `fix/rop-rounding`.
- Keep each pull request focused on one logical change with reviewable commits and no unrelated formatting churn.
- Rebase or merge according to repository policy before merge and resolve conflicts with full understanding of both changes.
- Do not force-push shared protected branches or rewrite published history without explicit team authorization.
- Keep environment files, generated artifacts, debug output, and local database dumps out of commits.
- Require protected branches, required checks, review approval, and linear or documented merge policy for production-bound branches.
- Do not merge changes with unresolved review comments, known security defects, failed migrations, or unexplained test failures.

## 67. Commit Message Convention

- Use Conventional Commits: `type(scope): imperative summary`.
- Use approved types: `feat`, `fix`, `refactor`, `test`, `docs`, `build`, `ci`, `perf`, `chore`, and `security`.
- Keep the summary under 72 characters, imperative, specific, and free of trailing punctuation.
- Explain breaking changes, migrations, data effects, and operational actions in the commit body when relevant.
- Do not use ambiguous messages such as `update`, `fix stuff`, `changes`, or `wip` in merged history.

## 68. Documentation Rules

- Document public APIs, complex workflows, calculation assumptions, role policies, operational runbooks, and deployment changes.
- Keep documentation close to the code or operational system it describes and update it in the same change.
- State invariants, rationale, constraints, and failure behavior rather than narrating obvious code syntax.
- Use diagrams only when they clarify a flow, ownership boundary, or state machine better than concise text.
- Remove stale documentation promptly; incorrect instructions are worse than absent instructions.
- Maintain runbooks for deployment, rollback, backup restore, incident response, queue remediation, synchronization recovery, and data correction.
- Record data retention, deletion, privacy, and export policies in operational documentation with accountable owners.

## 69. Code Review Checklist

- Confirm the change satisfies a defined business outcome and does not widen scope accidentally.
- Verify validation, authorization, transaction boundaries, audit behavior, error handling, and rollback or reversal paths.
- Review API compatibility, query count, indexes, data migration safety, and cache invalidation implications.
- Verify accessibility, responsive behavior, loading, empty, error, offline, and permission-denied states.
- Require meaningful automated tests and readable names before approving implementation complexity.
- Confirm migrations are safe for existing records, online deployment, backfill duration, locking behavior, and rollback or forward-fix strategy.
- Confirm observability, alerting, retention, privacy, and runbook impacts for changes to critical operational workflows.

## 70. Definition of Done

- Requirements, acceptance criteria, role behavior, and business invariants are implemented and reviewed.
- Code is typed, formatted, linted, tested, documented, accessible, responsive, and free of debug artifacts.
- Database changes include safe migrations, indexes, rollback considerations, and deployment notes where needed.
- Monitoring, logs, audit records, error states, and support diagnostics are adequate for production operation.
- Product owner or delegated reviewer has validated the user-visible workflow in a production-like environment.
- Release notes, feature flags, migration sequencing, rollback or forward-fix plan, and operator communications are ready when the change affects production behavior.
- No high-severity security, integrity, accessibility, or data-loss risk remains open without explicit accountable acceptance.

## 71. Things Claude MUST NEVER Do

- Never bypass authentication, authorization, validation, audit logging, inventory movement creation, or transaction boundaries.
- Never delete, mutate, or fabricate historical stock, sales, receiving, audit, or forecast records to make data appear correct.
- Never expose secrets, credentials, personal data, internal tokens, or unrestricted production access in code or documentation.
- Never use `any`, unsafe type suppression, raw unparameterized SQL, or client-only enforcement for security-critical rules.
- Never silently change business formulas, inventory status transitions, role permissions, or offline conflict outcomes.
- Never introduce unapproved dependencies, infrastructure, schema resets, destructive commands, or broad refactors without explicit need.
- Never disable tests, rate limits, security headers, logging, policies, or constraints merely to make a feature appear to work.
- Never merge a temporary workaround into a permanent business workflow without an owner, expiry, auditability, and follow-up issue.

## 72. Things Claude SHOULD ALWAYS Do

- Always inspect existing conventions and preserve compatible architecture before proposing or making changes.
- Always state and enforce business invariants at backend boundaries, especially for stock and financial-impacting workflows.
- Always consider authorization, auditability, concurrency, error recovery, accessibility, and offline implications.
- Always use explicit types, descriptive names, focused functions, deterministic tests, and safe migration practices.
- Always surface uncertainty, missing requirements, operational trade-offs, and consequential assumptions before irreversible decisions.
- Always leave the codebase clearer, more testable, and better documented than it was before the change.
- Always preserve backward compatibility or provide a versioned migration plan for contracts consumed by the frontend, jobs, exports, and integrations.
- Always verify changes using the narrowest relevant automated checks and report exactly what was and was not verified.

## 73. Common Mistakes to Avoid

- Do not calculate available stock by trusting a stale browser value or an unverified cached response.
- Do not allow direct edits to posted receiving, sales, adjustments, or movement records.
- Do not treat a successful local IndexedDB write as a successful server-side business transaction.
- Do not expose all table data to solve filtering, sorting, or reporting requirements in the browser.
- Do not hide authorization failures, conflicts, or stock shortages behind generic error messages.
- Do not let dashboard visualizations obscure data scope, freshness, assumptions, or operational action.
- Do not allow two browser tabs or two users to overwrite a purchase order, settings record, or planning parameter without concurrency detection.
- Do not calculate period-based demand from timezone-ambiguous timestamps or an incomplete current period.
- Do not make the fallback path for a failed external dependency silently mutate inventory or financial records.

## 74. How Claude Should Respond

- Lead with the implemented outcome or direct answer, then provide only the necessary supporting details.
- Be precise about files changed, verified behavior, assumptions, risks, and any remaining required decision.
- Ask concise clarifying questions only when a missing answer materially affects security, data integrity, scope, or user experience.
- Prefer concrete recommendations grounded in this handbook over generic framework advice.
- Do not claim tests, deployments, approvals, or production validation that did not actually occur.
- When reviewing code, identify severity, evidence, user impact, and a practical remediation path.
- Separate confirmed facts from assumptions and label any recommendation that needs product, security, or operations approval.
- Preserve user changes and existing repository conventions unless the requested task explicitly requires a compatible migration.

## 75. Coding Style Examples

- Prefer `calculateReorderPoint(product, demandProfile)` over `calcROP(p, d)`.
- Prefer `if (!policy.canReceive(purchaseOrder, user))` over scattered role-name comparisons.
- Prefer a typed `InventoryAdjustmentService` transaction over controller-level model updates.
- Prefer `const isSubmitting = createSaleMutation.isPending` over manually duplicated loading flags.
- Prefer `POST /api/v1/purchase-orders/{id}/approve` for an explicit state transition with authorization and audit behavior.
- Prefer a compensating inventory movement labeled `reversal` over editing the original movement quantity.
- Prefer `decimal:18,4` quantities and fixed-point money over JavaScript or database floating-point financial values.
