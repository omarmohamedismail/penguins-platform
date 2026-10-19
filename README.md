# Penguins Coaching Platform

A PHP and MySQL coaching workspace with client management, training and nutrition planning, scheduling, invoices, a sales pipeline, and business reporting. Includes English/Arabic controls and light/dark themes from the original project.

## Status

An improved development baseline, not yet approved for handling real client information. See LAUNCH_CHECKLIST.md for remaining work. Several screens are local management tools rather than connected services: a recorded payment is not a processed payment, an automation toggle does not run background jobs, and a contract status is not an electronic signature service.

## Requirements

- PHP 8.1+ with PDO MySQL; mbstring recommended.
- MySQL with an existing database named `penguins_coaching` (or your configured name).
- Apache for the supplied `.htaccess` restrictions, or equivalent rules on another server.

## Run locally

1. Put this folder in your XAMPP `htdocs` directory and start Apache and MySQL.
2. Create an empty MySQL database, using utf8mb4. No existing database or private client data is included.
3. Copy `config.example.php` to `config.local.php`; enter your database credentials. Alternatively set DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASS as environment variables. Local constants override environment variables.
4. From this folder run `C:\xampp\php\php.exe scripts/setup.php` once. The setup creates tables; normal web requests no longer create databases or tables.
5. Open `http://localhost/penguins-platform/` (adjust to your folder name), and create an account.

For the original database, back it up before applying any changes. The table structure remains compatible with the original application.

## Publish source to GitHub

Create a GitHub repository and upload the contents of this folder, including hidden `.github`, `.gitignore`, and `.htaccess` files. Do not upload config.local.php, database exports, the original data folder, or client records. Choose a license before making reuse permissions public; no license has been imposed on your code.

GitHub Pages cannot run this application's PHP login or MySQL storage. Deploy the repository to a PHP/MySQL host instead. See https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages.

## Deploy the application

1. Complete the launch checklist and the security review.
2. Configure a PHP/MySQL host with HTTPS, the PHP extensions above, and a private database.
3. Configure credentials through server environment variables or an untracked local configuration file. Use a dedicated database user, not root.
4. Run the CLI setup with migration credentials, then use a runtime database user limited to SELECT, INSERT, UPDATE and DELETE.
5. Upload the application; deny direct HTTP access to configuration, scripts, tests, database files, and hidden files. Apache rules are included; verify your host honors them.
6. Test registration, login, logout, saving, reload, and isolation between two different accounts. Configure encrypted backups and perform a restore rehearsal.

The current interface uses external Tailwind, Chart.js, Google Fonts and images; network access is needed. Compile and self-host production assets before launch.

## Checks

Run `python tests/smoke.py` (set PHP_BIN if PHP is not on PATH). It syntax-checks PHP and tests API method, CSRF, malformed JSON and unauthenticated access without touching MySQL. GitHub Actions runs these checks on pushes and pull requests.

Full database and browser acceptance tests are still required. A passing smoke test does not certify production security.
