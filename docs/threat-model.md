# Initial Threat Model

| Asset/boundary | Threat | Control |
|---|---|---|
| Farm data | IDOR/cross-farm access | Policies, scoped queries, isolation tests |
| Sessions | Theft/brute force | Secure cookies, rotation, hashing, rate limits |
| Uploads | Malware/path traversal/unrestricted upload | Allowlist, MIME verification, size limit, random non-public storage |
| AI | Prompt injection/data leakage/fabricated claims | Data minimization, fixed instructions, schema validation, provenance, disclaimer |
| Simulator/IoT | Abuse, spoofing, replay, duplicates | Environment guard, device auth, timestamps, idempotency |
| Exports | Unauthorized disclosure | Policy checks and expiring downloads |
| Secrets | Leakage in Git/logs/images | `.gitignore`, secret scan, redaction, secret manager |
| Availability | AI/provider outage | Queue failure handling and graceful degradation |
