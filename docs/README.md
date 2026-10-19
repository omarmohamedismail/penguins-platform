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

The `docs/` copy in the GitHub repository is the Pages site. Enable **Settings → Pages → Deploy from a branch → main → /docs**. GitHub Free requires a public repository for Pages. The repository must contain no private credentials or client data.

Supabase project: `batjqwxvjkjjbggfnnct`. Its publishable browser key in `assets/config.js` is intentionally public; authorization comes from Supabase Auth and RLS. Never replace it with a service-role or secret key.

The schema in `supabase/schema.sql` has already been applied to the Penguins project. Do not rerun the initial schema blindly. For another project, apply it once, update `assets/config.js`, and verify policies with two users.

In Supabase **Authentication → URL Configuration**, set the Site URL to the deployed Pages URL and allow that site's `auth.html` and `recovery.html` callback URLs. For local testing allow `http://127.0.0.1:8090/auth.html` and `http://127.0.0.1:8090/recovery.html`.

Supabase's default email service has delivery and rate limits. Configure production SMTP before opening registration to a wider audience; email confirmation and password recovery depend on that service. Existing PHP accounts/passwords are not automatically migrated. Create a new account, then import a JSON workspace backup if needed.

## Local preview

Run `python -m http.server 8090 --bind 127.0.0.1 --directory docs` from the repository root, then open `http://127.0.0.1:8090/`. The workspace uses the configured Supabase database even during local preview.

## Development

The repository root contains pinned dependencies and a lockfile. Run `npm ci`, `npm run build:pages`, and `npm test`. Edit `docs/assets/dashboard-base.js` and `docs/assets/enhancements.js`; the build combines them into `dashboard.js` and bundles dependencies into `vendor.js`.

The original PHP edition remains at the repository root as a legacy version. Pages serves only `docs/`.

## Honest scope

Payment processing, delivered client messaging, hosted video calls, electronic signatures, uploaded resource-file storage, background automation execution and separate client accounts are not connected services in this edition. Their existing management screens record information only. Complete owner-approved privacy/terms, email delivery, backups and end-to-end acceptance tests before accepting sensitive client records.
