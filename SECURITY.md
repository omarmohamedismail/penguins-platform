# Security

Do not post credentials or client information in public issues. Establish a private reporting address or enable GitHub private vulnerability reporting before inviting public use.

The application uses prepared SQL statements, hashed passwords, session cookies, CSRF checks and per-user storage. This update adds strict session IDs, private no-store responses, bounded JSON-object parsing, broader authentication throttling, POST-only logout and transactional registration.

Known limitations requiring review before production:

- Many dashboard templates insert user-controlled fields into innerHTML and inline event attributes. Audit every sink, use textContent/DOM event listeners, validate URLs and remove unsafe HTML paths. This has not been comprehensively fixed.
- The content security policy still permits inline scripts, styles and remote assets. Replace inline handlers and compile local assets before tightening it.
- Dashboard storage is one JSON document per coach. Concurrent tabs can overwrite newer changes. Add version checks and move core entities to normalized tables before multi-user collaboration.
- Rate limiting uses the database and the direct peer IP. Configure trusted proxies explicitly and add edge-level throttling. Registration currently shares the authentication limiter.
- There is no verified email, password reset, MFA, audit trail, or consent/data retention workflow.
- The original static prototype and local SQLite data were intentionally excluded from this distribution.
