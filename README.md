# YouCast

YouCast is the YouCode BDE platform for publishing challenges, events, and competitions. Learners discover activities, register individually or as teams, and submit ideas for future challenges.

The user interface is in **French**; the code, the routes and the database keep
English identifiers.

## Design system

The interface uses a mauve blue (`#7777D9`) and cool gray identity, defined as
Tailwind tokens in `tailwind.config.js` (`brand-*`, `slate-*`, `success-*`,
`warning-*`, `danger-*`). The reusable Blade components live in
`resources/views/components/`. The full rationale and the token reference are
in `DESIGN_IMPLEMENTATION_REPORT.md`.

## Stack

- Laravel 12 (PHP 8.2+)
- SQLite
- Blade + Tailwind CSS 3 + Vite
- Alpine.js (navigation, dropdown and modal only)

## Requirements

- PHP 8.2+ with sqlite, mbstring, xml, curl, zip, tokenizer, and bcmath extensions
- Composer
- Node.js 18+ and npm

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Seed demo data (optional):

```bash
php artisan db:seed
```

Demo accounts (password: `password`):

- BDE admin: `admin@youcode.local`
- Learner: `learner@youcode.local`

## Development

```bash
php artisan serve
npm run dev
```

The app is a server-rendered Laravel project. Preview environments should expose the Laravel HTTP port.

## Password reset (simplified, non-production)

The **Mot de passe oublié ?** link on the login page opens
`/forgot-password`. The user enters an e-mail address. If an account exists,
the application displays a new-password form; if not, it displays a styled
message with a link to registration. No e-mail, reset link, OTP or administrator
approval is used.

The two POST routes are rate-limited to five requests per minute per IP. The
password is validated with the project password rules, hashed with Laravel's
hashing mechanism, and the account's remember token is rotated. The request is
bound to the current session and cannot be replayed after a successful change.

> **Security limitation:** this flow only checks that the e-mail exists. It does
> not prove that the visitor owns that address. Anyone who knows a registered
> address can change the corresponding password. It must not be used in
> production without an additional verified factor (e-mail token, SMS OTP,
> TOTP/recovery codes, or in-person administrator verification).

### Mail configuration

`APP_URL` must still be the public origin of the application in every deployed
environment. Other authentication e-mails (for example e-mail verification)
must not build links from the request `Host` header.

Local development with the default `log` transport writes e-mails to
`storage/logs/laravel.log`:

```dotenv
APP_URL=http://localhost:8000
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

The log transport stores complete outbound messages, so it is for local
development only. For a local inbox that does not write messages to application
logs, run [Mailpit](https://mailpit.axllent.org/) and use its SMTP port and web
UI (`http://localhost:8025`):

```bash
docker run --rm -p 1025:1025 -p 8025:8025 axllent/mailpit
```

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_SCHEME=null
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

For real delivery, configure the SMTP credentials supplied by your mail
provider. The exact variables used by this Laravel version are:

```dotenv
APP_URL=https://youcast.example
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS="no-reply@youcast.example"
MAIL_FROM_NAME="${APP_NAME}"
```

Leave `MAIL_SCHEME=null` for the normal STARTTLS setup on port 587; Laravel
selects implicit TLS automatically for port 465. Use a sender address whose
domain is authorized by the SMTP provider (SPF/DKIM). Never commit real SMTP
credentials. After editing `.env`:

```bash
php artisan config:clear   # development
php artisan config:cache   # production
```

### Testing password reset and e-mail delivery

The password-reset tests use `Mail::fake()` and assert that **no e-mail is
sent**. `phpunit.xml` also forces the `array` mailer for the rest of the suite.

```bash
php artisan test tests/Feature/Auth/PasswordResetTest.php
```

To test the reset flow locally, use a seeded test account: enter its e-mail,
choose a new password, then sign in with the new password. Other authentication
e-mails still require the SMTP/Mailpit configuration above; the password-reset
flow itself does not use it.

## Core features

- Public site (home + "À propos") presents the product itself; no activity data is ever rendered for visitors.
- Activity catalogue with search, filtering, capacity, deadlines and team challenges. Members-only: every catalogue or detail URL redirects guests to the login screen.
- Individual registrations and team registrations (create a team, share a join code, register the team).
- Learners can register for an activity and cancel while it is published.
- Learners can submit challenge ideas; administrators review them (pending, accepted, rejected) and turn an accepted idea into a draft activity.
- Private messaging: a learner opens a conversation with the BDE and answers it from the protected administration inbox.
- Administrator dashboard with activity management and statistics.

## Private messaging

Learners open a conversation with the BDE from **Messages** in the navigation.
Administrators answer from **Inbox** in the administration area.

A conversation is private: only the learner who opened it and the BDE
administrators can read or answer it. The contents are never published to a
public page and never sent outside YouCast. Administrators close a conversation
when it is resolved; closing keeps every message.

## Roles

Self-registration always creates a **learner**. The admin role cannot be requested from the browser.

Promote an existing user:

```bash
php artisan youcast:promote-admin user@example.com
```

Only a trusted operator with shell access should run this command.

## Tests

```bash
php artisan test
```

The suite runs against an in-memory SQLite database (`phpunit.xml`), so it never
touches `database/database.sqlite`.

## Security documentation

- `BACKEND_SECURITY_AUDIT.md` — confirmed findings, evidence and fixes.
- `BACKEND_IMPLEMENTATION_REPORT.md` — what changed and why.
- `SECURITY_CHECKLIST.md` — verified controls, gaps and residual risks.
- `DESIGN_IMPLEMENTATION_REPORT.md` — the UI/UX redesign: tokens, pages, browser tests.

## Production checklist

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://youcast.example
LOG_LEVEL=info
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp
```

Configure the remaining SMTP values as described in **Authentication emails and
password reset** above. Never leave `MAIL_MAILER=log` in production. Generate a
real `APP_KEY` with `php artisan key:generate`, keep `.env` out of version
control, and configure `TrustProxies` when the application runs behind a reverse
proxy so that rate limiting sees the real client address.

## Product decisions in this MVP

- Learners may create their own accounts. Admin access is granted only with `youcast:promote-admin`.
- Activities are created as drafts and must be published by a BDE administrator. A published activity can be unpublished back to draft; nothing is ever deleted.
- Individual events and individual challenges register one learner per activity.
- Team challenges require a team. The leader creates the team, shares a join code, then registers the whole team.
- Duplicate registrations are blocked by a unique `(user_id, activity_id)` constraint, checked inside a write transaction.
- Capacity is enforced inside that transaction. SQLite does not support row-level locks, so the guarantee comes from the unique indexes, the database write lock, a busy timeout and transaction retries; a strict capacity guarantee requires MySQL or PostgreSQL.
- Learners may cancel while the activity is published and cancellation is allowed.
- There is no waitlist in this version.
- Accepted ideas can be converted into a draft activity without deleting the original idea.
- Learners open a private conversation with the BDE; administrators answer from the administration inbox. Only the participants can read a conversation.

## Useful commands

```bash
php artisan migrate
php artisan db:seed
php artisan test
npm run build
php artisan youcast:promote-admin email@example.com
```
#   Y o u C a s t  
 