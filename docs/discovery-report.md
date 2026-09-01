# AquaGrow AI Discovery Report

Date: 2026-08-31

## Observed

- Environment is Ubuntu 26.04 LTS running on WSL2 kernel `6.18.33.2-microsoft-standard-WSL2`.
- Project root `/home/jovia/projects/aquagrow-ai` did not exist before this discovery.
- PHP is not installed. The `composer` command resolves to a Windows Composer wrapper and cannot run without Linux PHP.
- Available versions: Docker 29.6.2, Docker Compose v5.3.1, Git 2.53.0, Node.js v24.19.0, npm 11.17.0.
- Active ExamGuard ports: `8080` (Moodle) and `5433` (integration PostgreSQL). ExamGuard containers were not changed or stopped.

## Proven

- AquaGrow is initialized as a separate repository at this path.
- No AquaGrow dependency, Laravel scaffold, application code, or test has been installed.
- No ExamGuard file, volume, database, environment file, or configuration was copied.
- Laravel's official release notes list Laravel 12 with PHP 8.2–8.5 support and Laravel 13 with PHP 8.3–8.5 support.

## Recommendation

Use PHP 8.4 and Laravel 12 for the initial baseline. PHP 8.4 is supported by Laravel 12 and avoids depending on an unverified local PHP installation. Re-evaluate Laravel 13 during scaffolding after its package compatibility and ecosystem requirements are checked.

## Not yet verified

- PHP extensions, Composer Linux installation, Laravel installer, database connectivity, Redis connectivity, and Docker health checks.
- Laravel application boot, migrations, tests, CI, security scans, and browser behavior.

## Decisions still required

- Authentication starter choice (Breeze, Fortify, or another approved option).
- PHP installation method and exact extension list.
- Initial species and measurement units.
- Farm membership and permission matrix details.
- AI provider/model and usage limits.
- Object storage, notification provider, browser support, RPO/RTO, and E2E framework.
