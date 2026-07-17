# Deployment Runbook

This runbook covers deploying the Predictive Inventory System (Laravel 12 API +
React/Vite frontend) beyond the local `docker-compose.yml` development setup. It
also records the concrete gaps found when reviewing the current dev configuration
for production readiness (CLAUDE.md §70/§74) — these are **not yet fixed in code**;
they are prerequisites to complete before a production rollout, tracked here so
the gap is explicit rather than discovered during an incident.

## 1. Current state vs. production requirement

The repository's `docker-compose.yml` and `backend/Dockerfile` are **dev-only** and
must not be deployed as-is. Findings from this review:

| Area | Dev configuration (current) | Production requirement |
|---|---|---|
| App server | `php artisan serve` (single-threaded dev server, `Dockerfile` CMD) | php-fpm + nginx, or Laravel Octane behind a reverse proxy |
| Database auth | `root` user, `MYSQL_ALLOW_EMPTY_PASSWORD=yes` | Dedicated least-privilege DB user (CLAUDE.md §56), strong generated password from a secrets manager |
| `APP_KEY` | Not generated anywhere in the image or compose file | Must run `php artisan key:generate --force` once per environment and store the resulting key in managed config; never regenerate on an existing environment (breaks all encrypted session/cookie data) |
| `APP_DEBUG` | `true` (`.env.example` default) | Must be `false` — `true` leaks stack traces, defeating `ApiExceptionRenderer`'s safe-error-envelope design (CLAUDE.md §38) |
| `APP_ENV` | `local` | `production` |
| Session cookies | `SESSION_SECURE_COOKIE=false` | `true` — required once served over HTTPS (CLAUDE.md §57) |
| Queue worker | None running; `QUEUE_CONNECTION=database` but nothing consumes the queue | No code currently dispatches a `ShouldQueue` job (confirmed via `grep -rl ShouldQueue app/` — zero matches), so this is not yet a functional gap, but `ReportExportService` explicitly generates PDF/CSV/XLSX **synchronously in the request** for exactly this reason (see `report_exports` table design, which models the full async lifecycle for a future queue). If any future work adds a queued job, a `queue:work` supervisor process becomes mandatory before that code ships. |
| Scheduler | Not running anywhere | `routes/console.php` registers `Schedule::command('restocking:evaluate-alerts')->hourly()`. Laravel's scheduler only fires commands when something invokes `php artisan schedule:run` — **production must run this every minute via cron or a supervisor process**, or restocking alerts (CLAUDE.md §53) silently stop updating. This is the single most important operational gap found in this review. |
| Health check | Present — Laravel's built-in `GET /up` (framework default, confirmed via `route:list`) | Use as-is for load balancer / container orchestrator liveness checks |
| TLS | None (plain HTTP on `localhost:5188`/`:8001`) | Terminate TLS at a reverse proxy/load balancer; set `SESSION_SECURE_COOKIE=true` and enable HSTS once TLS is confirmed working end-to-end |
| Source mount | `./backend:/var/www/html` bind mount (so `opcache.validate_timestamps=0` is safe for dev — see Dockerfile comment) | Production image must `COPY` the application code at build time, not bind-mount it; with `validate_timestamps=0` and a bind mount removed, an already-correct opcache config becomes safe for production too |
| Static frontend | Served by Vite dev server | Run `npm run build` and serve `frontend/dist` from a static host or CDN in front of the API origin |

## 2. Environment variable checklist (production)

Set all of the following through the platform's managed environment configuration
(CLAUDE.md §56 — never commit secrets):

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` (generated once, stored securely)
- `APP_URL` — the real public API origin
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — dedicated
  least-privilege MySQL user, strong password, TLS to the DB if it is not co-located
- `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN` set to
  the real cookie domain
- `SANCTUM_STATEFUL_DOMAINS` and `CORS_ALLOWED_ORIGINS` — the real frontend origin(s)
  only; never wildcard these for a production deployment
- `FRONTEND_URL` — the real frontend origin
- `LOG_CHANNEL` / `LOG_LEVEL` — see §6 below
- `QUEUE_CONNECTION=database` (unchanged; revisit if a future feature adds a
  queued job, per the table above)
- `CACHE_STORE` — `database` is functionally correct at current scale; revisit only
  if profiling shows it as a bottleneck (CLAUDE.md §59 — measure before optimizing)
- `MAIL_*` — required if password-reset email delivery (CLAUDE.md §57) is enabled
  for production; `.env.example` currently defaults to `log`, which is dev-only

## 3. Migration procedure

1. Take a verified database backup immediately before migrating (§4).
2. Run `php artisan migrate --force` against the target environment. `--force` is
   required because `APP_ENV=production` blocks interactive migration prompts.
3. All migrations in this repository are additive/forward-only per CLAUDE.md §14 —
   none of them drop or rewrite existing columns destructively. Confirm this holds
   for any new migration before it ships (§10: "migrations forward-only, ordered,
   and independently deployable from application code").
4. Verify with `php artisan migrate:status` that the migration ledger matches
   expectations before routing production traffic to the new application version.

### Rollback

- Because migrations are additive-only, the standard rollback path is **forward-fix,
  not `migrate:rollback`** — CLAUDE.md §14 explicitly requires migration rollbacks
  to never discard data created by the forward migration, so a blind `rollback`
  against a live production database is not a safe default action.
- If a deployed application version must be rolled back, redeploy the previous
  application code against the *already-migrated* schema (safe, since the schema
  only ever adds columns/tables) rather than reversing the migration.
- Only run `php artisan migrate:rollback` for a migration confirmed safe to reverse
  (e.g. one that has not yet been used to write any production data), and only
  after a fresh backup.

## 4. Backup and restore

- Back up the MySQL `predictive_inventory` database on a schedule that meets the
  organization's recovery point objective (CLAUDE.md §63 requires this to be
  explicitly defined — no RPO/RTO is currently documented for this project; that
  decision belongs to the engineering/product owner, not this runbook).
- Use `mysqldump` (or the managed database provider's native snapshot mechanism)
  with `--single-transaction` for a consistent InnoDB snapshot without locking
  writers.
- **Test the restore path**, not just the backup job — CLAUDE.md §63 requires
  evidence of a working restore, not just a backup file existing. Restore into a
  scratch environment and run `php artisan migrate:status` plus a basic data sanity
  check (row counts on `inventory_movements`, `sales`, `audit_logs`) after every
  restore test.
- `report_exports`-generated files (PDF/CSV/XLSX) live on `FILESYSTEM_DISK=local`
  storage in the current configuration — back this up alongside the database, or
  move to a durable object store before production if export retention matters
  beyond the `report_exports.expires_at` window already enforced by the app.

## 5. Health checks

- `GET /up` — Laravel's built-in liveness endpoint. Point the load balancer /
  container orchestrator health check here.
- `mysql` container/service — use its own `mysqladmin ping` health check (already
  configured in the dev `docker-compose.yml`; carry the same check into the
  production database's monitoring).
- Neither endpoint currently verifies DB connectivity from the app process itself
  (`/up` is a framework-level check, not app-specific). If deeper readiness
  checking becomes necessary, add a dedicated `/api/v1/health` route backed by a
  real DB query — do not repurpose `/up` for this, since it is a Laravel framework
  convention external tooling may already assume is lightweight.

## 6. Logging and monitoring

Per CLAUDE.md §65:

- Set `LOG_CHANNEL` to a structured, centrally-shipped channel in production
  (`.env.example`'s `stack`/`single` file-based default is dev-only and will not
  survive container restarts or scale past one instance).
- Set `LOG_LEVEL=info` or higher in production; `debug` (the `.env.example`
  default) is too verbose and risks incidentally logging request payloads.
- Route PHP/Laravel errors to a centralized error tracker with release version,
  environment, and correlation ID (the app already generates and threads a
  correlation ID through `ApiExceptionRenderer` and `AuditLogger` — wire the error
  tracker to capture it).
- Alert on: authentication failure rate spikes, `403`/`409` rate spikes on
  stock-mutating endpoints, sync (`SyncOperation`) rejection/conflict rate growth,
  scheduled `restocking:evaluate-alerts` job failures or missed runs, and MySQL
  backup job failures — none of these alerts exist yet; standing them up is a
  prerequisite for calling this system production-ready per CLAUDE.md §70.

## 7. Phased rollout plan

1. **Staging parity check.** Deploy the production-shaped configuration (§1 table,
   right-hand column) to a staging environment first. Do not skip this — the
   current dev compose file differs from production in enough ways (app server,
   DB credentials, TLS, scheduler) that "works in dev" is not evidence it works
   under the production topology.
2. **Data migration dry run.** Run the full migration set (`php artisan migrate --force`)
   against a staging copy of production-shaped data (or a fresh seed) and confirm
   `php artisan migrate:status` and the full backend test suite pass against that
   environment's config.
3. **Scheduler and health-check verification.** Confirm `schedule:run` is actually
   firing every minute (check `restocking:evaluate-alerts`'s effect on `RestockingAlert`
   rows over a real hour boundary) and `/up` responds correctly behind the real
   load balancer, before allowing any real user traffic.
4. **Read-only or single-branch soft launch.** If the business can tolerate it,
   route one branch's traffic (or a read-only pilot group) to the new environment
   first, watching the alerts from §6, before cutting over remaining branches.
5. **Full cutover.** Point `SANCTUM_STATEFUL_DOMAINS`/`CORS_ALLOWED_ORIGINS`/DNS at
   the production environment for all users. Keep the previous environment warm
   and reachable (not torn down) for a defined rollback window, per CLAUDE.md §70's
   requirement for a rollback or forward-fix plan.
6. **Post-launch reconciliation.** Run the reconciliation checks CLAUDE.md §44
   requires (movement-derived totals vs. stored balances) against real production
   data within the first 24 hours, and confirm no discrepancies were introduced by
   the cutover itself.

## 8. Known, explicitly out-of-scope-for-now items

These are not blocking a first production deployment but are flagged so they are
tracked rather than silently deferred:

- No RPO/RTO has been defined by the business for this system (§4) — needs an
  accountable owner decision, not an engineering default.
- No queue worker is currently required (§1), but `ReportExportService`'s
  synchronous PDF/CSV/XLSX generation is a documented trade-off that will need
  revisiting if export volume or file size grows enough to risk request timeouts.
- MFA for Owner accounts (CLAUDE.md §57) depends on organizational identity
  capability that is not yet selected; this runbook does not prescribe a specific
  provider.
