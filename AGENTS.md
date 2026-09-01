# AquaGrow AI Agent Instructions

## Scope

AquaGrow AI is a Laravel modular monolith for fish-cultivation management. The project root is `/home/jovia/projects/aquagrow-ai`. Never modify `/home/jovia/projects/examguard` as part of this project.

## Architecture boundaries

- Presentation: Blade, Tailwind CSS, Alpine.js, accessible responsive UI.
- Application: use cases, commands, queries, authorization orchestration.
- Domain: cultivation, monitoring, growth, mortality, reminders, prediction, and harvest rules.
- Infrastructure: persistence, queues, cache, storage, notifications, AI provider, and future IoT adapters.
- Domain code must not depend directly on controllers or UI.

## Data and tenancy

- Every farm-scoped request must be authorized with deny-by-default policies.
- Prevent IDOR and cross-farm access with scoped queries and feature tests.
- Measurement records must preserve value, unit, measured timestamp, source (`MANUAL`, `SIMULATOR`, or `IOT`), actor/device, and validation status.
- Historical cultivation cycles must never be overwritten when a pond is reused.

## AI rules

- AI runs only through a backend adapter; secrets never reach the browser.
- AI may analyze supplied data and perform limited visual screening, but must not invent measurements or give definitive diagnoses.
- Every result must include provenance, analysis time, confidence or limitations, and an appropriate disclaimer.
- Validate structured output server-side. Never store chain-of-thought.
- AI failure must not prevent core data entry and reading workflows.

## Security

- Never commit `.env`, secrets, uploads, dumps, logs, tokens, or credentials.
- Validate all input server-side and use ORM/query binding.
- Apply CSRF protection, secure session handling, upload allowlists, MIME verification, random storage names, rate limits, and security headers.
- Do not expose debug utilities, verbose errors, internal API documentation, or simulator controls in production.
- Redact secrets and sensitive data from logs.

## TDD and verification

- Use RED -> GREEN -> REFACTOR for domain behavior and acceptance criteria.
- Run targeted, related, and full relevant tests before claiming completion.
- Verify authorization, tenant isolation, validation, uploads, queues, cache, ingestion, AI adapter behavior, and error handling.
- Review the diff and Git status. Do not weaken or delete tests.

## Git and changes

- Use small logical commits on feature branches.
- Do not push, merge, rebase, force-push, or create pull requests without explicit instruction.
- Do not use destructive cleanup commands.
- Record important architecture decisions in `docs/adr/`.

## Definition of done

An implementation is complete only when code, tests, security review, runtime verification, relevant documentation, and observed evidence are all reported.
