# Launch checklist

## Included in this update

- Corrected landing-page authentication links.
- Environment/local database configuration and ignored secrets/data files.
- Separate CLI schema setup; no table creation during normal requests.
- JSON body size/type checks and dashboard collection validation.
- Authentication throttling across account or IP; registration throttling.
- Atomic account and initial workspace creation.
- CSRF-protected POST logout and non-cacheable authenticated responses.
- Failed loads cannot save a blank replacement workspace.
- Unsaved-change warning when leaving a page.
- Keyboard focus visibility and reduced-motion support.
- Apache restrictions, GitHub checks and setup documentation.

## Required before accepting real clients

- Fix stored DOM injection throughout dashboard templates (see SECURITY.md).
- Add nested entity validation and URL allowlists, then test malicious payloads.
- Add save-version conflict detection and recoverable drafts/retry UX.
- Test complete account lifecycle and database failure/rollback scenarios.
- Verify mobile layouts, Arabic RTL, keyboard navigation and modal focus in real browsers.
- Replace CDN development styling with compiled assets and pin dependencies.
- Review all marketing/pricing claims against actual capabilities; configure real support and legal pages.
- Implement email verification, recovery, and owner-approved privacy/terms policies.
- Configure HTTPS, secret management, monitoring and tested backups.
- Decide which features are internal records and which need real integrations: messaging delivery, payments, video calls, automations and signatures.

## Product improvements after the security pass

- Prioritize the daily coach workflow: onboarding, clients, plans, schedule, progress.
- Break the dashboard into smaller modules with shared forms and accessible components.
- Add real client invitations and role-based coach/client access.
- Add searchable activity history, exports, account deletion and permission controls.
