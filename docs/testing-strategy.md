# Testing Strategy

Use RED -> GREEN -> REFACTOR. PHPUnit is the baseline testing framework for this foundation phase because it is included by the official Laravel Livewire starter kit. Pest is deferred and requires a separate ADR before installation. Unit tests cover domain rules and deterministic prediction. Feature/integration tests cover authentication, authorization, farm isolation, validation, rate limiting, upload, queues, cache invalidation, simulator/IoT contracts, AI fake adapter, and error handling. Critical journeys receive browser tests: login, farm/pond setup, cycle creation, measurement entry, status review, cycle close, and harvest entry.

CI gates: formatting, static analysis, unit/feature tests, migration verification, frontend checks, secret scan, dependency review, SAST, container build, and image scan.

The standard local command is `./bin/test`. It runs PHPUnit inside the app
container with `APP_ENV=testing`, PostgreSQL database `aquagrow_testing`, and
an explicit guard that refuses the development database. Browser tests are
deferred by architecture decision until critical browser journeys exist.
