# Testing Strategy

Use RED -> GREEN -> REFACTOR. PHPUnit is the baseline testing framework for this foundation phase because it is included by the official Laravel Livewire starter kit. Pest is deferred and requires a separate ADR before installation. Unit tests cover domain rules and deterministic prediction. Feature/integration tests cover authentication, authorization, farm isolation, validation, rate limiting, upload, queues, cache invalidation, simulator/IoT contracts, AI fake adapter, and error handling. Critical journeys receive browser tests: login, farm/pond setup, cycle creation, measurement entry, status review, cycle close, and harvest entry.

CI gates: formatting, static analysis, unit/feature tests, migration verification, frontend checks, secret scan, dependency review, SAST, container build, and image scan.

The standard local command is `./bin/test`. It runs PHPUnit inside the app
container with `APP_ENV=testing`, PostgreSQL database `aquagrow_testing`, and
an explicit guard that refuses the development database. Browser tests are
deferred by architecture decision until critical browser journeys exist.

## Manual acceptance record

Date: 2026-09-01 (Asia/Jakarta)

Scope: Farm and Farm Membership authorization foundation.

- User without a farm: PASS — onboarding/empty state displayed.
- Owner with two farms: PASS — active-farm dashboard displayed.
- Multi-farm switcher: PASS — switching works.
- Owner farm settings: PASS — accessible to owner.
- Dashboard null active-farm regression: PASS — fixed and regression-covered.
- Non-member farm access: PASS — HTTP 403 Forbidden.
- Cross-farm isolation: PASS.
- HTTP 500 on re-test: PASS — not reproduced.
- Sole-owner account deletion: PASS — deletion was rejected with the policy message; the user remained logged in and the farm membership remained available.
- Account-deletion rejection produced no HTTP 500, raw database error, or stack trace. Successful deletion was verified only with an automated disposable-account test.

Responsive and keyboard acceptance were not claimed because they were not
reported as part of the manual evidence. Browser automation remains deferred.
No manual account identifiers, credentials, cookies, sessions, or sensitive
user data are stored in this repository.
Ownership invariant tests must use PostgreSQL and bounded lock timeouts for canonical farm-row locking. Account deletion tests cover sole-owner rejection and successful deletion when every owned farm has another owner.
