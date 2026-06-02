# CrIServe Production Rollout Checklist

This checklist turns the scaling hardening work into a deployment sequence you can execute before exposing the system to public traffic.

## 1. Environment

- Copy [.env.production.example](/Applications/XAMPP/xamppfiles/htdocs/criserve-portal/.env.production.example) to your real production environment values.
- Set a real `APP_KEY`.
- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL` to the exact public HTTPS origin.
- Do not reuse local or testing databases.

## 2. Database

- Use a dedicated production MySQL or MariaDB instance.
- Run:

```bash
php artisan migrate --force
```

- Confirm the large-query indexes from the recent hardening migrations are present.
- Enable slow query logging on the database server.
- Schedule backups before first public launch.

## 3. Redis

- Provision Redis for:
  - queues
  - cache
  - sessions
- Set:
  - `CACHE_STORE=redis`
  - `SESSION_DRIVER=redis`
  - `SESSION_STORE=redis`
  - `QUEUE_CONNECTION=redis`

## 4. Storage

- Use S3-compatible object storage for uploads and workflow files.
- Set:
  - `FILESYSTEM_DISK=s3`
  - `SECURE_DOCUMENT_DISK=s3`
  - `WORKFLOW_UPLOAD_DISK=s3`
  - `PAYOUT_PROOF_DISK=s3`
- Verify:
  - document preview/download still works
  - dedup uploads still process
  - payout imports still process
  - payout proof photos still display

## 5. Document Scanning

- If you want synchronous blocking scans, keep:
  - `DOCUMENT_SCAN_MODE=inline`
- For high public upload volume, prefer:
  - `DOCUMENT_SCAN_MODE=quarantine`
  - `DOCUMENT_SCAN_ENABLED=true`
  - `DOCUMENT_SCAN_QUEUE=documents`
- In quarantine mode:
  - uploads are accepted first
  - files stay unavailable while `pending_scan`
  - failed scans remain blocked

## 6. Queue Workers

- Use Supervisor or another process manager.
- Example worker config:
  - [deploy/supervisor/criserve-workers.conf](/Applications/XAMPP/xamppfiles/htdocs/criserve-portal/deploy/supervisor/criserve-workers.conf)
- Recommended queues:
  - `default`
  - `notifications`
  - `mail`
  - `documents`
  - `exports`
  - `imports`
  - `deduplication`
  - `payouts`
  - `audit`

## 7. Laravel Optimization

Run these during deployment:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Optional:

```bash
php artisan event:cache
```

## 8. Web Tier

- Use Nginx plus PHP-FPM.
- Terminate HTTPS at the load balancer or reverse proxy.
- Enforce request body limits for uploads.
- Add rate limiting in front of public endpoints if possible.

## 9. Smoke Tests

Before launch, verify:
- public home page
- login
- client application page
- support form
- document preview/download
- dedup upload
- payout batch upload
- GL workflow document pages

## 10. Load Testing

Starter script:
- [scripts/load/k6-web-smoke.js](/Applications/XAMPP/xamppfiles/htdocs/criserve-portal/scripts/load/k6-web-smoke.js)

Example:

```bash
k6 run \
  -e K6_BASE_URL=https://your-domain.example \
  -e K6_CLIENT_EMAIL=client@test.com \
  -e K6_CLIENT_PASSWORD=password \
  scripts/load/k6-web-smoke.js
```

Track:
- p95 latency
- p99 latency
- queue lag
- failed jobs
- DB CPU
- storage growth

## 11. Test Safety

- Keep the PHPUnit cache isolation in [phpunit.xml](/Applications/XAMPP/xamppfiles/htdocs/criserve-portal/phpunit.xml).
- Do not point test runs at the live local or production MySQL database.
- If you use local MySQL for development, add a dedicated testing database or keep PHPUnit on in-memory SQLite.

## 12. Launch Gate

Do not call the system production-ready for massive public traffic until all of these are true:
- Redis is live
- object storage is live
- worker supervision is live
- malware scanning mode is chosen and verified
- load test passes are reviewed
- DB backups and monitoring are in place
