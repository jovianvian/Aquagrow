# ADR 0002: Cultivation cycle lifecycle and pond state

Status: Accepted for the Cultivation foundation slice.

Cultivation cycles use the transitions `PLANNED -> ACTIVE` and `PLANNED -> CANCELLED`. Completion, harvest, reopening, deletion, and cancellation of an active cycle are deferred.

Each farm has at most one open cycle (`PLANNED` or `ACTIVE`) per pond. PostgreSQL enforces this with a partial unique index. The cycle also carries `farm_id`, and the composite pond/farm foreign key prevents cross-tenant relationships.

Mutations lock the pond row first and the cycle row second inside one transaction. Start, cancel, and pond lifecycle changes re-read state after locking. Concurrent tests use separate PostgreSQL connections with bounded lock timeouts.

Pond display lifecycle is derived from operational status and open-cycle state: `INACTIVE`, `MAINTENANCE`, `ACTIVE_CULTIVATION`, `CYCLE_PLANNED`, or `EMPTY_AVAILABLE`. It is not stored as a duplicate column.

Fish species are global reference data. Inactive species remain readable on historical cycles but cannot be selected for a new cycle or used to start a planned cycle.

Owners may mutate cycles; operators are read-only; non-members are denied. The active farm is verified server-side for every farm-scoped action. Known PostgreSQL conflicts are mapped to safe domain messages; unknown database failures continue to central error handling.
