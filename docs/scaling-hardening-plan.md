# CrIServe Scaling And Hardening Plan

This document describes the production hardening work needed before exposing CrIServe to public traffic at very large scale.

## Current Status

The codebase is suitable for pilot and moderate traffic after the first optimization pass, but it is not yet ready for millions of applications per week without additional infrastructure and query work.

The first hardening pass already completed:

- Added GL workflow indexes on `applications`
- Added document retrieval indexes on `documents`
- Paginated the GL payment processor queue
- Moved several dashboard totals from PHP collection processing to SQL aggregates

## Immediate Priorities

1. Move all remaining dashboard statistics to SQL aggregates
2. Remove any remaining operational `->get()` calls on large workflow datasets
3. Add read-optimized search strategy for large application datasets
4. Move all file and document generation work to queues
5. Separate hot operational tables from historical/archive tables

## Application Layer

### Queues

Use Redis-backed queues and long-running workers in production.

- Use `php artisan queue:work`
- Do not use `queue:listen` in production
- Split queues by concern:
  - `default`
  - `audit`
  - `notifications`
  - `mail`
  - `documents`
  - `deduplication`
- `exports`
- `payouts`

Recommended worker segregation:

- lightweight workers for audit logs, notifications, and routine state changes
- dedicated workers for spreadsheet imports and deduplication
- dedicated workers for ORS/DV/LDDAP generation and exports

### Cache

Use Redis for:

- application cache
- sessions
- queue backend
- rate limits
- dashboard/stat caching

Suggested cache candidates:

- library tables
- service points
- banks
- finance fund sources
- positions
- frequently used aggregate counters

Recommended production env direction:

- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `SESSION_STORE=redis`
- `QUEUE_CONNECTION=redis`
- run dedicated workers per queue instead of a single catch-all worker

### Search

Current `%LIKE%` search patterns will degrade badly at large scale.

Recommended evolution:

1. Add exact and prefix search where possible for reference numbers
2. Add normalized searchable columns for full names
3. For very large public search workloads, move to a search index:
   - Meilisearch
   - OpenSearch
   - Elasticsearch

## Database Layer

### Primary Database

Use MySQL or MariaDB in managed production configuration with:

- connection pooling
- slow query logging
- query plan inspection
- automated backups
- replica support

### Read Replicas

Introduce read replicas for:

- dashboards
- report pages
- operational history views
- exports

### Partitioning And Archiving

At millions per week, `applications` and `documents` will grow very quickly.

Recommended strategy:

- keep active workflow records in primary hot tables
- archive completed and older records into historical tables
- consider time-based partitioning for:
  - `applications`
  - `documents`
  - `audit_logs`
  - `notifications`

### Index Review

Repeat index review for:

- `notifications`
- `documents`
- `audit_logs`
- `support_tickets`
- public application lookup flows
- client and service provider dashboards

## Files And Documents

### Object Storage

Do not keep uploads only on local disk for internet scale.

Use object storage such as:

- Amazon S3
- Cloudflare R2
- MinIO

Store:

- compliance uploads
- SOA files
- supporting documents
- payout proof photos
- exports

The application is now able to process deduplication uploads and payout import spreadsheets even when they are stored on non-local disks by creating a temporary local processing copy at runtime. For production rollout:

- set `FILESYSTEM_DISK` to your S3-compatible disk
- set `WORKFLOW_UPLOAD_DISK` for deduplication and payout import source files
- set `SECURE_DOCUMENT_DISK` for secure document uploads
- set `PAYOUT_PROOF_DISK` for payout proof images

Large public upload volume will still need a second hardening pass around malware scanning and file lifecycle management:

- offload virus scanning from inline request handling where possible
- prefer asynchronous quarantine and release flows for large documents
- apply lifecycle cleanup rules to generated exports and temporary artifacts

The application now supports an asynchronous document scan mode:

- `DOCUMENT_SCAN_MODE=inline` keeps the legacy synchronous behavior
- `DOCUMENT_SCAN_MODE=quarantine` stores uploads immediately, marks them as `pending_scan`, and scans them on the `documents` queue
- pending or failed documents are blocked from preview/download until they are cleared

### Generated Files

Generate ORS, DV, and LDDAP through background jobs and optionally cache rendered output or generated PDFs.

## Web Layer

Use:

- Nginx
- PHP-FPM
- HTTPS termination
- CDN for static assets

Add:

- rate limiting on public application submission
- file upload throttling
- request body limits
- bot protection

## Observability

Add production monitoring for:

- request latency
- queue depth
- failed jobs
- database CPU
- database slow queries
- storage growth
- PHP memory usage

Recommended tools:

- Laravel Horizon
- Sentry or Bugsnag
- Prometheus + Grafana
- managed DB metrics

## Load Testing

Before public launch, simulate:

- concurrent public application submission
- heavy service provider uploads
- parallel GL workflow review activity
- export generation spikes
- payout proof image uploads

Metrics to capture:

- p95 and p99 response times
- queue lag
- DB slow queries
- worker saturation
- memory usage

## Recommended Next Engineering Steps

1. Optimize the remaining dashboards and public flows to SQL aggregates
2. Add read-model caching for dashboards and summary cards
3. Introduce Redis and Horizon
4. Move storage to S3-compatible backend
5. Add archive strategy for completed workflow records
6. Run load tests and profile the slowest endpoints
