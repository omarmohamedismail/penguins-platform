# GitHub Pages edition

Converted the existing dashboard from PHP/MySQL to a static browser application connected to Supabase. No old database records or credentials were uploaded. Existing PHP users must create new Supabase accounts.

New features: add/edit clients; full client search; autosave and save status; offline drafts; reconnect retry; save conflict detection; ten cloud versions; backup export/import; CSV exports; task due dates and priorities; undo removal; activity history; password recovery; print action; Ctrl/Cmd+S to save immediately.

Reliability repairs: empty chat startup, missing metrics initialization, blank-data overwrite after failed load, stale sample client dropdowns, broken auth links, and silent save errors.

Security changes: per-user RLS, anonymous database access revoked, invoker-mode save function, payload checks, prototype-key rejection on import, safer CSV, HTML sanitization and restricted dynamic event interpretation. Security and performance advisors returned no findings after schema creation. Database isolation tests denied cross-user read/write/delete and anonymous reads.


## Client access and coach approval

- Clients register without administrator approval. Coaches require explicit approval.
- Private owner review queue with decisions and audit records.
- Up to three coach certificate slots, enforced by database constraints and storage paths.
- Client-to-coach requests, acceptance, and isolated shared workout plans.
- Optional workout PDFs up to 10 MiB, available only to sender and assigned client.
- Logged-out UI and transactional permission tests checked; real email delivery still depends on the configured mail provider.


## Security review and portal redesign

- Freeze approved coach certificates; explicit review reopening is required for edits.
- Lock certificate writes against review decisions, and reject stale administrator actions.
- Hide PDF plans from clients until upload verification; interrupted uploads retain private drafts.
- Add portal CSP and referrer protection, signature checks for uploads, and safe file-opening dialogs.
- Redesign Arabic admin, coach and client views with counts, search, pagination and mobile layouts.
- Add automated validation and regression tests; security SQL uses rollback-only synthetic fixtures.


## Calm studio edition

Arabic landing page, cream and sage palette, redesigned account portal and matching sign-in/recovery screens. Personal weekly organizer and notes use account-scoped browser storage only (no device sync or coach sharing). Rest timer uses elapsed wall time with pause/resume. Coaches get editable plan-writing templates; plans support current-page text search and individual print layouts. Templates contain organizational prompts, not prescribed workouts. No database permissions or paid services changed.


## Owner console and original dark design

The dark public design is restored with restrained, reduced-motion-aware animation. The owner console replaces the admin-only review screen: overview, reviewed coaches, members, coaching relationships, plan moderation, audit history and in-portal announcements. Administrative APIs require the existing verified private allowlist on every call; public wrappers use invoker mode. Account pause uses database/storage policies and privileged-function checks; it is reversible and does not delete or ban the Auth identity. Moderation hides plans and blocks new client file links; already-issued signed URLs expire normally. Private coach workspaces are not exposed to administrators. `supabase/owner-console.sql` is already applied to the live project; do not rerun it there. Use rollback-only `tests/owner-security.sql` for role checks. The personal organizer remains local to the browser for clients/coaches.
