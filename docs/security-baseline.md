# Security Baseline

- `.env`, credentials, uploads, dumps, logs, coverage, and build artifacts are excluded from Git.
- Validate server-side; use ORM/query binding and mass-assignment protection.
- Enable CSRF, secure cookies, session rotation, logout invalidation, password hashing, security headers, and deny-by-default authorization.
- Apply rate limits to login, password reset, AI, upload, export, simulator, IoT, and other expensive endpoints.
- Keep API keys backend-only. Validate AI structured output and retain provenance, uncertainty, and disclaimer.
- Production uses `APP_DEBUG=false`, generic error responses, protected development utilities, and redacted structured logs.
