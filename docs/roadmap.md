# Roadmap

1. Foundation: Laravel, Docker, PostgreSQL, Redis, CI, `.env.example`, ADRs, health checks.
2. Identity/farm/pond: authentication, roles, policies, farm isolation, pond status.
3. Cultivation foundation: species reference data, planned/active/cancelled
   cycles, initial stocking, pond lifecycle derivation, and farm isolation.
4. Monitoring: growth, water quality, health, mortality.
5. Feeding/reminders: stock consistency, queued notifications, idempotent jobs.
6. Prediction/harvest: deterministic prediction with assumptions and uncertainty.
7. AI: backend adapter, fake provider, validation, provenance, limits, graceful failure.
8. Simulator/reporting: protected simulator, ingestion idempotency, authorized exports.
9. IoT readiness: authenticated ingestion contract, deduplication, replay protection.

## Planned product backlog

The items below are planned requirements, not implemented functionality. Ordering
will be refined against domain dependencies after the Cultivation milestone.

### Product foundations

- Google login (planned): add a real “Continue with Google” OAuth flow with
  client and redirect URI configuration per environment. Provider secrets stay
  server-side; callback state and provider identity must be validated. Account
  linking must use an explicit policy and must not silently merge an existing
  account only because email addresses match. Google onboarding continues to
  use AquaGrow's farm membership and tenant rules; the provider cannot grant a
  role or farm automatically. Implementation requires approved OAuth client
  configuration, redirect URIs, consent/scopes, and an account-linking decision.

- Localization: Indonesian and English, user language preference, consistent
  translation strings, and locale-aware date, number, and unit formatting. User
  input data and timezone remain separate concerns.
- Settings: user preferences for language and light/dark/system theme; farm
  settings for timezone, units, and relevant operational preferences; restricted
  system-operational settings according to the application architecture.
- Profile and security: profile name/photo, verified email change, password and
  session security using the existing authentication capabilities, and authorized
  photo upload with validation.
- Master data: Fish Species, Locations, and Ponds are available in the current
  foundation. Additional references will be added only when required by a domain
  module, with inactive/archive behavior that preserves historical references.
- Membership and access: owner/operator management and a deliberately selected
  invitation or member-addition mechanism, while preserving tenant isolation and
  the last-owner invariant.

### Data stewardship

- Backup and restore: define database/file scope, storage, retention, access
  limits, and restore verification. Tenant backup and system-operational backup
  scopes must be decided before implementation. Backup is distinct from report
  export; unrestricted database download is not planned.
- Soft delete and change history: decide per entity which records can be archived
  and restored. Do not apply universal soft delete. Revision history/audit trail
  requirements (including safe correction of stock and report-impacting
  transactions) remain to be specified per entity.

### Domain sequence

- Monitoring: growth, water quality, health, and mortality records.
- Feeding and reminders: feed stock consistency and queued operational reminders.
- Harvest and reporting: cycle completion, harvest records, and authorized
  operational reports. Cultivation completion/harvest is not implemented in the
  current slice.
- Prediction and AI: deterministic prediction with assumptions/uncertainty, then
  a backend AI adapter with provenance, validation, limits, and graceful failure.
- Simulator and IoT readiness: protected simulation, ingestion idempotency,
  authenticated ingestion, deduplication, and replay protection.

No global administrator role, arbitrary color settings, public data ingestion,
or default predictable user credentials are part of this backlog.

## Current visual direction

The public landing page follows a light-first AquaGrow direction: a soft
green-white background, white panels, dark readable text, teal accents, and
light borders/shadows. Existing dark mode remains supported, with enough
contrast between page and panel backgrounds to preserve hierarchy. Shared
tokens in `resources/css/app.css` are the foundation for later dashboard
refinement; the wider application theme remains subject to visual review.

The landing composition is informed by the [21st.dev Shadcn SaaS Landing
template](https://21st.dev/@nobruf/templates/shadcn-saas-landing), its
[preview](https://shadcn-saas-landing-v1.21st.app/), and the
[MIT-licensed source repository](https://github.com/nobruf/shadcn-landing-page).
Only the compact navigation, strong hero hierarchy, large product-preview
focus, modular section rhythm, and restrained theme/motion ideas are adapted.
The Next.js/React implementation, sponsor/testimonial/pricing content, and
template claims are not copied into AquaGrow.
