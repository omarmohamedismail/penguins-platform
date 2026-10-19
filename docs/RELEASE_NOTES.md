# GitHub Pages edition

Converted the existing dashboard from PHP/MySQL to a static browser application connected to Supabase. No old database records or credentials were uploaded. Existing PHP users must create new Supabase accounts.

New features: add/edit clients; full client search; autosave and save status; offline drafts; reconnect retry; save conflict detection; ten cloud versions; backup export/import; CSV exports; task due dates and priorities; undo removal; activity history; password recovery; print action; Ctrl/Cmd+S to save immediately.

Reliability repairs: empty chat startup, missing metrics initialization, blank-data overwrite after failed load, stale sample client dropdowns, broken auth links, and silent save errors.

Security changes: per-user RLS, anonymous database access revoked, invoker-mode save function, payload checks, prototype-key rejection on import, safer CSV, HTML sanitization and restricted dynamic event interpretation. Security and performance advisors returned no findings after schema creation. Database isolation tests denied cross-user read/write/delete and anonymous reads.
