# YouCast — Security Checklist

Legend:

* **[x] Verified** — the control was inspected in the code and its behaviour
  was exercised (by a test, by the browser smoke test, or by both).
* **[~] Partially verified** — the control exists but could not be fully
  exercised in this environment, or it only covers part of the surface.
* **[ ] Missing** — the control is absent.
* **[!] Residual risk** — accepted or documented limitation, see the notes.

> **Known exception (non-production):** the simplified password-reset flow
> does not verify ownership of the e-mail address. See control 1.8 and finding
> H-03 in `BACKEND_SECURITY_AUDIT.md`. Do not deploy this flow as-is.

---

## 1. Authentication

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 1.1 | Passwords are hashed (bcrypt, 12 rounds) | [x] Verified | `User` casts `password` to `hashed`; `BCRYPT_ROUNDS=12`. |
| 1.2 | Password policy enforced on register, reset and change | [x] Verified | `Rules\Password::defaults()`; `confirmed` required. |
| 1.3 | Login is rate limited | [x] Verified | `throttle:login` (5/min, e-mail + IP) **and** an in-request limiter (5 attempts). Verified by test. |
| 1.4 | Lockouts are logged | [x] Verified | `Lockout` event listener logs the source IP only (no personal data). |
| 1.5 | Session is regenerated after login | [x] Verified | `AuthenticatedSessionController::store()`. |
| 1.6 | Logout invalidates the session and CSRF token | [x] Verified | `invalidate()` + `regenerateToken()`; verified by test. |
| 1.7 | Password reset requests and submissions are throttled | [x] Verified | `throttle:5,1` on both POST routes. The request is session-bound and cannot be replayed after success. |
| 1.8 | Password reset requires proof of e-mail ownership | [!] Residual risk | The simplified flow only checks that the account exists and intentionally reveals that fact. No e-mail token, OTP, 2FA or administrator verification is required. **Not production-safe.** |
| 1.9 | Session cookie is `HttpOnly` and `SameSite=lax` | [x] Verified | `config/session.php`. |
| 1.10 | Session cookie is `Secure` in production | [~] Partially verified | `SESSION_SECURE_COOKIE` now defaults to *unset* (HTTPS auto-detection) instead of `false`. A production deployment must set it to `true` — it cannot be `true` by default because the local environment runs over plain HTTP. |
| 1.11 | Registration cannot be used to enumerate accounts | [!] Residual risk | The `unique` rule still answers "the e-mail has already been taken". Accepted trade-off (see `BACKEND_SECURITY_AUDIT.md`, L-05). |
| 1.12 | E-mail addresses are verified | [!] Residual risk | Verification is optional by product design (L-06). |
| 1.13 | Password-reset forms are protected against CSRF | [x] Verified | Both forms contain `@csrf` and the routes use Laravel's `web` middleware group. The reset flow sends no links. |

## 2. Authorization

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 2.1 | Administrator routes require an administrator | [x] Verified | `['auth', 'admin']` middleware group **plus** a policy check on every action. |
| 2.2 | The administrator role can never be requested from the browser | [x] Verified | `role` is not mass-assignable; `RegisteredUserController` forces `UserRole::Learner`; verified by test. |
| 2.3 | Privilege escalation is only possible through a console command | [x] Verified | `youcast:promote-admin` requires shell access and writes an audit entry. |
| 2.4 | Learner permissions are enforced server-side, not only in the UI | [x] Verified | Every learner route calls `authorize()` or a policy-backed Form Request. Verified by test. |
| 2.5 | A user cannot read or modify another user's registration, team or idea | [x] Verified | Scoping is enforced in the policies *and* re-checked in the services; verified by three IDOR tests. |
| 2.6 | A team from another activity cannot be addressed by guessing its id | [x] Verified | `abort_unless($team->activity_id === $activity->id, 404)` in `TeamController`; verified by test. |
| 2.7 | Moderation transitions are validated | [x] Verified | `ChallengeIdeaPolicy::review()` requires a pending idea, `convert()` requires an accepted idea with no activity yet; verified by test. |
| 2.8 | An administrator cannot act outside the permitted scope | [x] Verified | `ActivityPolicy` restricts each lifecycle transition to the allowed source states. |

## 3. Input validation and data handling

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 3.1 | Every state-changing request is validated | [x] Verified | Form Requests for every create, update and moderation action. |
| 3.2 | Types, formats, lengths and ranges are constrained | [x] Verified | `Rule::enum`, `integer`, `date`, `boolean`, `max:…` on every free-text field. |
| 3.3 | Privileged fields are ignored when they arrive in a payload | [x] Verified | `status`, `created_by`, `author_id`, `reviewer_id`, `role` … are not mass-assignable; verified by test. |
| 3.4 | Relationships are validated against real records | [x] Verified | `Rule::unique('teams','name')->where('activity_id', …)`, `Rule::unique(User::class)`, team ids scoped by activity. |
| 3.5 | Impossible registration windows are rejected | [x] Verified | A registration deadline later than the activity start is refused; verified by test. |
| 3.6 | Validation errors never expose internals | [x] Verified | Laravel's standard validation messages and error bags. |
| 3.7 | Filter inputs are bounded | [x] Verified | Catalogue and administration search terms are truncated to 100 characters. |

## 4. Injection

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 4.1 | All queries use Eloquent or the parameterised query builder | [x] Verified | The only raw SQL in the application is a bound `orderByRaw` in `ActivityController::index`. |
| 4.2 | Dynamic column names / sort directions come from an allow list | [x] Verified | The only dynamic sort is a fixed `orderBy('starts_at')` plus the bound `orderByRaw` case expression; activity type and status filters are validated against the enum with `tryFrom()`. |
| 4.3 | Output is escaped | [x] Verified | No `{!! !!}`, no `v-html`, no `innerHTML` anywhere in `resources/`. Verified by an escaping test. |
| 4.4 | Rich-text / raw HTML rendering | N/A | The application stores plain text only. |

## 5. CSRF and HTTP behaviour

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 5.1 | CSRF protection is active on all web routes | [x] Verified | Default `web` middleware group, no exclusions, `@csrf` in every form. |
| 5.2 | No sensitive operation uses `GET` | [x] Verified | `route:list` shows only `POST`, `PUT`, `PATCH` and `DELETE` for every mutation (logout included). |
| 5.3 | Cookies and redirects follow the framework defaults | [x] Verified | `http_only`, `same_site=lax`, `redirect()->intended()` after login, `back()` after a mutation. |
| 5.4 | CSRF rejection is exercised by an automated test | [!] Residual risk | Laravel skips the CSRF middleware when the application runs in the `testing` environment, so a feature test cannot assert it. It was verified by reading the middleware configuration and by the presence of `@csrf` in every form. |

## 6. Mass assignment

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 6.1 | Ownership attributes are not mass-assignable | [x] Verified | `created_by`, `author_id`, `leader_id`, `user_id`. |
| 6.2 | Workflow state attributes are not mass-assignable | [x] Verified | `status`, `published_at`, `closed_at`, `is_registered`, `registered_at`, `reviewed_at`, `review_notes`. |
| 6.3 | Privilege attributes are not mass-assignable | [x] Verified | `role`, `reviewer_id`. |
| 6.4 | Server-owned tables reject mass assignment outright | [x] Verified | `Registration`, `TeamMember` and `AuditLog` use `$guarded = ['*']`; verified by test. |
| 6.5 | A silently discarded privileged attribute cannot be mistaken for a successful write | [x] Verified | `User::update(['role' => …])` discards `role` (it is not fillable) and leaves the role unchanged — confirmed on the running application. The only supported promotion path is the `youcast:promote-admin` command, which assigns the attribute explicitly and writes an audit entry. |

## 7. Rate limiting and abuse prevention

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 7.1 | Login is limited | [x] Verified | 5 attempts per minute and per e-mail/IP. |
| 7.2 | Registration (account creation) is limited | [x] Verified | `throttle:register`, 5/minute per IP. |
| 7.3 | Password reset is limited | [x] Verified | `throttle:5,1` on both the e-mail-check and reset-submission routes. |
| 7.4 | Idea submission is limited | [x] Verified | `throttle:ideas`, 5/hour per account; verified by test. |
| 7.5 | Registration and team endpoints are limited | [x] Verified | `throttle:registration` and `throttle:teams`, 20/minute per account; verified by test. |
| 7.6 | Administrative endpoints are limited | [~] Partially verified | Administrators are authenticated and authorised, and their actions are rare and audited, but no explicit throttle is applied to the administration routes. |
| 7.7 | Expensive search and statistics endpoints are bounded | [~] Partially verified | Search terms are length-bounded and all lists are paginated. The learner dashboard is not paginated (L-03). |
| 7.8 | Conversation creation and answers are rate limited | [x] Verified | `throttle:message-create` (5/hour) and `throttle:message-reply` (60/hour), keyed by account; verified by test. |

## 7b. Private messaging

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 7b.1 | Every conversation read is checked against the participants | [x] Verified | `ConversationPolicy::view()` → `Conversation::hasParticipant()`. Verified by test. |
| 7b.2 | Every message write is checked against the participants | [x] Verified | `ConversationPolicy::reply()` → participant **and** open conversation; enforced by `StoreMessageRequest::authorize()` before any write. Verified by test. |
| 7b.3 | The learner listing only contains the learner's own conversations | [x] Verified | `Conversation::scopeForStudent()`; verified by test. |
| 7b.4 | The administration inbox requires an administrator | [x] Verified | `['auth', 'admin']` middleware group plus the policy; verified by test. |
| 7b.5 | Guests are redirected to the login page | [x] Verified | `auth` middleware group; verified by test. |
| 7b.6 | Only learners open conversations | [x] Verified | `ConversationPolicy::create()` requires the learner role; verified by test. |
| 7b.7 | A closed conversation refuses new answers | [x] Verified | `reply()` requires `isOpen()`; verified by test. |
| 7b.8 | Message contents and notifications never leave YouCast | [x] Verified | Opening a conversation, an administrator reply and a learner reply all keep the content in the application. `PrivateMessagingTest` covers these flows with `Http::fake()` and `Http::assertNothingSent()`. |
| 7b.9 | Message contents never appear on a public page | [x] Verified | Verified by test on the home page and the catalogue. |
| 7b.10 | Message bodies are escaped | [x] Verified | Rendered with `{{ }}`; no `{!! !!}` in `resources/views`. |
| 7b.11 | The conversation and message input is validated | [x] Verified | Subject ≤ 120, body ≤ 4 000; verified by test. |
| 7b.12 | CSRF protection on the messaging forms | [x] Verified | `@csrf` in every messaging form; all writes are `POST`. |
| 7b.13 | Foreign keys, indexes and transactions protect the data | [x] Verified | FKs with `restrictOnDelete` / `cascadeOnDelete`, indexes on `(conversation_id, created_at)` and `(conversation_id, read_at)`, both writes inside `DB::transaction()`. |
| 7b.14 | Privileged attributes are not mass-assignable | [x] Verified | `Conversation::$fillable = ['subject']`; `Message` uses `$guarded = ['*']`. |
| 7b.15 | The private history cannot be destroyed by a cascade | [x] Verified | `User::deletionBlockers()` reports conversations and messages, and the deletion flow refuses instead of cascading. |
| 7b.16 | There is no destructive delete for conversations | [x] Verified | Only close / reopen exist; both are audited. |
| 7b.17 | The message listings do not issue one query per row | [x] Verified | `MessageQueryEfficiencyTest` compares the query count for one and for five conversations. |
| 7b.18 | Conversation identifiers are not sequential | [!] Residual risk | They are sequential, but a non-participant is refused with `403` before any body is rendered, so the id cannot be used to read data (L-09). |

## 8. External notifications

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 8.1 | No outbound notification request is emitted | [x] Verified | Idea submission, opening a conversation and both reply paths are covered by `Http::fake()` / `Http::assertNothingSent()` tests. |
| 8.2 | No third-party notification credentials are required | [x] Verified | YouCast has no external messaging configuration; `.env.example`, `config/services.php` and `phpunit.xml` contain no notification credentials. |

## 9. Database integrity

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 9.1 | Foreign keys are used for every relationship | [x] Verified | All relationships are constrained with an explicit delete behaviour. |
| 9.2 | Duplicate registrations are impossible | [x] Verified | Unique index on `registrations(user_id, activity_id)`. |
| 9.3 | A user belongs to at most one team per activity | [x] Verified | Unique index on `team_members(activity_id, user_id)` and `team_members(team_id, user_id)`. |
| 9.4 | Team names are unique per activity | [x] Verified | Unique index on `teams(activity_id, name)`, plus a matching `Rule::unique`. |
| 9.5 | Join codes and activity slugs are unique | [x] Verified | Unique indexes on `teams.join_code` and `activities.slug`; a concurrent-save retry was added. |
| 9.6 | Frequently filtered foreign keys are indexed | [x] Verified | Eight missing indexes added by the new migration. |
| 9.7 | Multi-step writes are transactional | [x] Verified | Team creation, registration, unregistration and disbanding all run inside `DB::transaction()`; verified by the "no partial registrations" test. |
| 9.8 | Capacity limits are protected against concurrent requests | [~] Partially verified | Unique indexes guarantee no duplicates. On SQLite the capacity check is **not** race-free (no row locks): mitigated by the busy timeout, WAL and three-attempt transactions. A strict guarantee requires MySQL/PostgreSQL. |
| 9.9 | Historical records are never destroyed by a cascade | [x] Verified | `restrictOnDelete` on `created_by`, `author_id` and `leader_id`; the account deletion flow refuses rather than cascades. |
| 9.10 | Nullable and non-nullable columns match the domain | [x] Verified | Inspected column by column; no column is nullable while the domain requires a value (except the explicitly optional ones). |

## 10. Secrets and configuration

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 10.1 | `.env` is not committed | [x] Verified | `.gitignore` line `.env`. |
| 10.2 | `.env.example` contains placeholders only | [x] Verified | No real key, URL or credential. |
| 10.3 | No third-party messaging credentials are stored | [x] Verified | The external notification integration and its environment variables were removed; no messaging credential is required. |
| 10.4 | `APP_DEBUG` is off in production | [~] Partially verified | `config/app.php` defaults to `false`; `.env.example` ships `true` for local development, and the production checklist requires `false`. |
| 10.5 | `APP_KEY` handling | [x] Verified | Read from the environment; the key is never logged or rendered. It was **not** rotated by this work. |
| 10.6 | No secret in the source code, the frontend bundle or the log files | [x] Verified | The Vite bundle contains no configuration; the application exposes no environment values to the browser. |
| 10.7 | The local database file is excluded from version control | [x] Verified | Added to `.gitignore` (M-09). |

## 11. Error handling and logging

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 11.1 | Stack traces are not shown to users in production | [x] Verified | Laravel's default handler renders a generic error page when `APP_DEBUG` is off. |
| 11.2 | Passwords and tokens are never logged by application code | [x] Verified | The framework never flashes `password`, `password_confirmation` or `current_password`; YouCast has no third-party messaging notifier. The password-reset flow sends no e-mail and logs no password. The development-only `log` mail transport still writes other outbound e-mail bodies; use Mailpit or SMTP outside local development and never enable it in production. |
| 11.3 | Logs contain no unnecessary personal data | [x] Verified | The lockout listener records the IP address only; the audit log records the actor id, not the payload. |
| 11.4 | Security-relevant events are logged | [x] Verified | Authentication lockouts are logged; administrative actions are recorded in `audit_logs` with the actor and a timestamp. |
| 11.5 | Diagnostic information is preserved for legitimate debugging | [x] Verified | `LOG_LEVEL=debug` locally; exceptions are still reported through the standard handler. |

## 12. Dependencies

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 12.1 | Composer dependencies are free of known advisories | [x] Verified | `composer audit` → *No security vulnerability advisories found.* |
| 12.2 | The lock file matches `composer.json` | [x] Verified | `composer validate` → *./composer.json is valid*. The lock hash was refreshed without changing any package version. |
| 12.3 | npm dependencies are free of known advisories | [!] Residual risk | `npm audit` → 7 advisories (2 moderate, 5 high) in the Tailwind CSS 3 build chain (`braces`, `chokidar`, `fast-glob`, `micromatch`, `postcss-selector-parser`). They are build-time only and never reach the browser. The offered fix (`tailwindcss@4`) is a breaking change to the styling pipeline and was deliberately not applied. |
| 12.4 | No dependency was added or upgraded for this work | [x] Verified | The change set contains no new Composer or npm package. |

## 13. Tests and verification

| # | Control | Status | Notes |
| --- | --- | --- | --- |
| 13.1 | Automated tests run against a disposable database | [x] Verified | `phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`; the project database is never touched by the suite. |
| 13.2 | Access control is covered by tests | [x] Verified | `tests/Feature/AuthorizationTest.php` plus the security regression suite. |
| 13.3 | Every confirmed vulnerability has a regression test | [x] Verified | See `BACKEND_IMPLEMENTATION_REPORT.md`, section 5. |
| 13.4 | The suite passes | [x] Verified | `Tests: 117 passed (428 assertions)`. |
| 13.5 | The code style matches the project's own linter | [x] Verified | `vendor/bin/pint --test` → *passed*. |
| 13.6 | The application still works end to end | [x] Verified | A real HTTP smoke test against a temporary SQLite database covered learner login, opening and reading a conversation, submitting an idea, administrator reply, idea review and idea conversion. The frontend build also passes. |

---

## Summary

* **Verified controls:** 89
* **Partially verified controls:** 5
* **Not applicable:** 1 (rich-text rendering — the application stores plain text only)
* **Documented residual risks:** 6

The application has no known critical finding. The most important remaining
limitations are the absence of row-level locking on SQLite (which weakens the
capacity guarantee, not the duplicate-registration guarantee), the optional
e-mail verification, the sequential conversation identifiers, and the unfixed
Tailwind build-chain advisories. All of them are listed with a recommended next
step in `BACKEND_IMPLEMENTATION_REPORT.md`, sections 9 and 10.
