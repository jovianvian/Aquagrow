# Runtime Security Configuration

- Production must set `APP_DEBUG=false`; users receive generic error pages while detailed, redacted errors remain server-side.
- Production cookies must be `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, and `SESSION_SAME_SITE=lax` unless a documented cross-site flow requires a stricter review.
- Only the reverse proxy is public. Nginx serves `public/` and forwards PHP to FPM; `.env`, VCS, logs, and database files are denied.
- Trusted proxy and host handling must be explicit in production deployment configuration. Do not trust arbitrary forwarded host/proto headers.
- Debug routes, profiler, simulator controls, database browsers, internal API docs, and development utilities must be disabled or protected outside local development.
- PostgreSQL and Redis are private Docker-network services and are not published to the host by default.
