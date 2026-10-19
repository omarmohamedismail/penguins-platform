# Penguins — GitHub Pages + Supabase

A static coaching workspace that runs without PHP, XAMPP or MySQL. The browser uses Supabase Auth and account-scoped Postgres storage. Compiled assets are included; no runtime Tailwind CDN is required.

## What's included

- Email/password accounts, confirmation flow, sign-out and recovery UI.
- Client creation and editing, email/focus search and status filtering.
- Existing training plans, nutrition tracking, lead pipeline, invoices, tasks, reports and profile screens.
- Debounced cloud saving with explicit status and server-side version conflict checks.
- Device drafts and reconnect retry; recovery never silently overwrites a newer cloud version.
- Up to ten cloud snapshots with restore, JSON export/import and CSV exports.
- Task deadlines, priorities and overdue indicators; twelve-second undo for list removal.
- Workspace activity history, keyboard shortcuts, light/dark themes and existing language controls including Arabic RTL navigation.
- Sanitized dynamic HTML and an allowlisted literal-action interpreter; dynamic handlers do not use eval or Function.
- Compiled Tailwind CSS and locally bundled Supabase, DOMPurify and Chart.js dependencies.

## Hosting

The `docs/` copy in the GitHub repository is the Pages site. Use **Settings → Pages → Source → GitHub Actions**. The Publish Penguins website workflow builds and deploys `docs/`. GitHub Free requires a public repository for Pages. The repository must contain no private credentials or client data.

Supabase project: `batjqwxvjkjjbggfnnct`. Its publishable browser key in `assets/config.js` is intentionally public; authorization comes from Supabase Auth and RLS. Never replace it with a service-role or secret key.

The schema in `supabase/schema.sql` has already been applied to the Penguins project. Do not rerun the initial schema blindly. For another project, apply it once, update `assets/config.js`, and verify policies with two users.

In Supabase **Authentication → URL Configuration**, set the Site URL to the deployed Pages URL and allow that site's `auth.html` and `recovery.html` callback URLs. For local testing allow `http://127.0.0.1:8090/auth.html` and `http://127.0.0.1:8090/recovery.html`.

Supabase's default email service has delivery and rate limits. Configure production SMTP before opening registration to a wider audience; email confirmation and password recovery depend on that service. Existing PHP accounts/passwords are not automatically migrated. Create a new account, then import a JSON workspace backup if needed.

## Local preview

Run `python -m http.server 8090 --bind 127.0.0.1 --directory docs` from the repository root, then open `http://127.0.0.1:8090/`. The workspace uses the configured Supabase database even during local preview.

## Development

The repository root contains pinned dependencies and a lockfile. Run `npm ci`, `npm run build:pages`, and `npm test`. The legacy `utilities.css` is a versioned static compatibility stylesheet; new component styles belong in `product.css` or `portal.css`. The build no longer installs the old Tailwind toolchain. Edit `docs/assets/dashboard-base.js` and `docs/assets/enhancements.js`; the build combines them into `dashboard.js` and bundles dependencies into `vendor.js`.

The original PHP edition remains at the repository root as a legacy version. Pages serves only `docs/`.

## Honest scope

Payment processing, delivered client messaging, hosted video calls, electronic signatures, background automation execution are not connected services in this edition. Their existing management screens record information only. Complete owner-approved privacy/terms, email delivery, backups and end-to-end acceptance tests before accepting sensitive client records.


## Client portal and coach review

Open `auth.html`, create an account, and select Client or Coach. New accounts enter the selected portal after signing in. Existing accounts without a role select one once. Clients enter immediately; coaches remain pending until reviewed by a verified platform administrator. The account role is stored in the database and cannot be changed by editing browser metadata.

`portal.html` provides the administrator review queue (pending / approved / rejected), optional review notes, up to three private coach qualification files (PDF, JPEG or PNG), client requests to approved coaches, coach acceptance, and shared text plans with an optional PDF. Maximum file size: 10 MiB. File links expire after 60 seconds. Use Refresh to see new requests.

The initial `schema.sql` and subsequent `membership.sql` migrations are already applied to the live project; do not rerun them there. The administrator email allowlist is stored in the private database schema, never in client code. A matching confirmed email is required. Provision administrators only through a trusted project-owner database connection.

The existing coach workspace remains private. CRM contacts and workspace plan drafts are separate from registered client accounts; send deliverable plans from the portal after the client connects. Existing users choose their account role on their next visit.


## Security and portal UI update

The `supabase/security-upgrade.sql` migrations are already applied to the live project. Approved coach certificates are immutable until the coach explicitly requests another review. Certificate writes and administrator review lock the same coach profile to serialize decisions. Review decisions reject stale status values and retain an audit record.

A plan with a PDF stays private to the coach until `publish_plan` verifies the stored object. Failed uploads retain a private draft with retry controls. Clients cannot set the publication flag or read unfinished attachments. Approved files use canonical private storage paths and short-lived links. File signature checks in the browser improve input validation; they are not antivirus scanning or a substitute for server authorization.

The portal has an external-script Content Security Policy, private link referrer protection, role-specific headers, counts, request search and pagination, explicit empty states, accessible dialogs and responsive layouts. Email-link login is also available alongside password login; open the message in the browser that requested it.

Run `node --test docs/tests/*.test.cjs` for unit tests. Run `docs/tests/security-regression.sql` against an owner-managed test connection for transaction-only role and storage policy assertions. It rolls back its fixtures. The legacy `membership-rls.sql` path contains the same current suite. Never publish the local `work/portal-preview` UI fixture server.


## Calm studio edition

Arabic landing page, cream and sage palette, redesigned account portal and matching sign-in/recovery screens. Personal weekly organizer and notes use account-scoped browser storage only (no device sync or coach sharing). Rest timer uses elapsed wall time with pause/resume. Coaches get editable plan-writing templates; plans support current-page text search and individual print layouts. Templates contain organizational prompts, not prescribed workouts. No database permissions or paid services changed.
