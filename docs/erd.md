# Initial ERD

```mermaid
erDiagram
  USERS ||--o{ FARM_MEMBERSHIPS : has
  FARMS ||--o{ FARM_MEMBERSHIPS : contains
  FARMS ||--o{ LOCATIONS : owns
  LOCATIONS ||--o{ PONDS : contains
  PONDS ||--o{ CULTIVATION_CYCLES : hosts
  FISH_SPECIES ||--o{ CULTIVATION_CYCLES : classifies
  CULTIVATION_CYCLES ||--o{ GROWTH_SAMPLES : records
  CULTIVATION_CYCLES ||--o{ WATER_QUALITY_RECORDS : records
  CULTIVATION_CYCLES ||--o{ FEEDING_RECORDS : receives
  CULTIVATION_CYCLES ||--o{ HEALTH_OBSERVATIONS : observes
  CULTIVATION_CYCLES ||--o{ MORTALITY_RECORDS : records
  CULTIVATION_CYCLES ||--o{ HARVEST_PREDICTIONS : predicts
  CULTIVATION_CYCLES ||--o{ HARVESTS : produces
  CULTIVATION_CYCLES ||--o{ AI_ANALYSES : analyzes
  AI_ANALYSES ||--o{ AI_ANALYSIS_SOURCES : references
```
# Farm slice

`farms` owns tenant identity (`name`, globally unique `slug`, IANA `timezone`,
and allowlisted `currency`). `farm_memberships` joins users to farms with an
enum-backed role and unique `(farm_id, user_id)`. Foreign keys cascade only
when a farm/user is removed; application policy protects farm operations.

## Location and pond slice

`locations` belongs to a farm and has a farm-local unique slug. `ponds` stores
an explicit `farm_id` and references `(location_id, farm_id)` through a
PostgreSQL composite foreign key, preventing cross-farm relationships. Pond
measurements are nullable canonical metric values; empty does not mean zero.
