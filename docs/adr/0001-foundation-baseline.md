# ADR 0001: Foundation Baseline

Status: Accepted
Date: 2026-08-31

## Decisions

- Use Laravel 13 with PHP 8.4.
- Run PHP, Composer, Artisan, and PHP tooling only in pinned containers; never rely on native WSL PHP or the Windows Composer wrapper.
- Use the official Laravel Livewire starter kit with Blade, Livewire 4, Tailwind, Alpine, Vite, Fortify, and session authentication.
- Use PostgreSQL for application and integration tests, and Redis for cache, queue, and rate limiting.
- Use `farm_memberships` for tenant membership. Platform administrator status remains separate from farm membership. Policies are deny-by-default and every farm-scoped record must be verifiably owned by a farm.
- Store measurements in canonical metric units, money as integer minor units or safe decimal representation, timestamps in UTC, and display time in the farm's IANA timezone.
- Defer OpenAI integration to the AI phase. Define an adapter boundary and fake provider only when that phase begins; core workflows must not depend on AI availability.
- Defer automated browser testing by decision until critical browser journeys exist; foundation acceptance uses HTTP smoke tests and PHPUnit authentication coverage.
- Baseline recovery target is RPO 24 hours and RTO 8 hours, with daily database backups, seven daily backups, and four weekly backups.

## Consequences

- Docker build/network availability is required before PHP tests can run.
- No Sanctum or public API authentication is included in this phase.
- SQLite is not the integration-test database.
