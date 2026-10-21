# YouCast — Backend Security Audit

Scope: the whole Laravel backend of YouCast Version 1 (models, policies,
middleware, form requests, services, controllers, migrations, configuration,
deployment files and the automated test suite).

Reference framework: OWASP Top 10 (2025) categories and the official Laravel
12 security recommendations. Every finding below was confirmed by reading the
code and, where behaviour was in doubt, by running the application or the test
suite. Nothing here is speculative.

Environment used for the audit and for the fixes: PHP 8.2.12, Laravel 12.70.0,
SQLite, Node 24.20.0, npm 11.19.0, PHPUnit 11.5.

---

## 1. Audited components

| Area | Files inspected |
| --- | --- |
| Authentication | `app/Http/Controllers/Auth/*`, `app/Http/Requests/Auth/LoginRequest.php`, `routes/auth.php`, `config/auth.php`, `config/session.php` |
| Authorization | `app/Policies/*`, `app/Http/Middleware/EnsureUserIsAdmin.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php` |
| Input validation | `app/Http/Requests/*` |
| Business logic | `app/Services/RegistrationService.php`, `app/Services/TeamService.php`, `app/Services/AuditLogger.php` |
| Data layer | `app/Models/*`, `app/Enums/*`, `database/migrations/*`, `database/factories/*`, `config/database.php` |
| Presentation | `resources/views/**` (all Blade templates), `resources/css/app.css`, `resources/js/app.js` |
| Configuration | `config/app.php`, `config/auth.php`, `config/database.php`, `config/logging.php`, `config/services.php`, `config/session.php`, `.env.example`, `.gitignore` |
| Dependencies | `composer.json` / `composer.lock`, `package.json` / `package-lock.json` |
| Tests | `tests/Feature/*`, `tests/Unit/*`, `phpunit.xml` |

---

## 2. Summary of confirmed findings

| ID | Severity | Title | Status |
| --- | --- | --- | --- |
| H-01 | High | Account deletion raised an unhandled foreign-key violation (HTTP 500) | Fixed |
| H-02 | High | Privileged attributes were mass-assignable on six models | Fixed |
| H-03 | Critical | Password reset can be completed with only an e-mail address, and reveals whether an account exists | Fixed: expiring single-use Laravel broker token, generic response, and broker throttling |
| H-04 | High | Registration and team endpoints had no rate limiting | Fixed |
| M-01 | Medium | Foreign-key columns were not indexed (SQLite does not index them automatically) | Fixed |
| M-02 | Medium | "Row locks" do not exist on SQLite — the documented concurrency guarantee was inaccurate | Mitigated / documented |
| M-03 | Medium | Registration cancellation had no policy check | Fixed |
| M-04 | Medium | The idea moderation console used an ad-hoc `abort_unless` instead of a policy | Fixed |
| M-05 | Medium | Impossible registration windows (deadline after the activity start) were accepted | Fixed |
| M-07 | Medium | The activity page loaded every team, every member and every member's user | Fixed |
| M-08 | Medium | `composer.lock` was out of sync with `composer.json` | Fixed |
| M-09 | Medium | The local SQLite database file was not excluded from version control | Fixed |
| L-01 | Low | Zero-length activities (`ends_at` == `starts_at`) are accepted | Not fixed (documented) |
| L-02 | Low | `LIKE` wildcards in the search term are not escaped | Not fixed (documented) |
| L-03 | Low | The learner dashboard loads all of the learner's registrations without pagination | Not fixed (documented) |
| L-04 | Low | `youcast:promote-admin` performs no confirmation | Not fixed (documented) |
| L-05 | Low | Registration still reveals that an email address is already taken | Not fixed (accepted trade-off) |
| L-06 | Low | Email verification is optional (`User` does not implement `MustVerifyEmail`) | Not fixed (by design) |
| L-08 | Low | The database seeder is not idempotent | Not fixed (documented) |
| L-09 | Low | A conversation is identified by a sequential integer id | Accepted (documented) |

### Second review round — product workflow specification

The confirmed product specification was compared against the code after the
first round. The gaps were behavioural, not vulnerabilities: the landing pages
after authentication, the "unpublish" administrator action, the registration
status on the activities page, and the private messaging system. The security
review of that work produced **no new finding**: the new feature was built with
the controls already in place. The controls that were verified are listed in
section 7 and in `SECURITY_CHECKLIST.md`.

One deliberate trade-off was recorded: conversations are addressed by their
sequential id. Because every read and write is checked against the
participant list, a non-participant receives `403 Forbidden` and can never see
a message body. See L-09 in section 4 for the reasoning.

No **critical** finding was confirmed: there is no unauthenticated path to
administrator privileges, to another learner's data, or to arbitrary code
execution.

---

## 3. Confirmed findings, evidence and fixes

### H-01 — Account deletion with historical records

**Severity:** High — the original delete path raised a foreign-key error for
accounts with historical BDE records.

**Evidence**

* `database/migrations/2026_10_19_100100_create_challenge_ideas_table.php:16`
  — `foreignId('author_id')->constrained('users')->restrictOnDelete()`
* `database/migrations/2026_10_19_100200_create_activities_table.php:16`
  — `foreignId('created_by')->constrained('users')->restrictOnDelete()`
* `database/migrations/2026_10_19_100300_create_teams_table.php:16`
  — `foreignId('leader_id')->constrained('users')->restrictOnDelete()`
* `app/Http/Controllers/ProfileController.php` handles account deletion and
  team-leadership transfer explicitly.
* `database/migrations/2026_10_21_110000_allow_account_deletion_with_anonymized_history.php`
  changes historical author references to nullable `ON DELETE SET NULL`.

**Failure scenario:** a learner or administrator with authored activities,
ideas, teams or messages could not remove their account without either losing
history or receiving a database constraint error.

**Fix**

* Historical content is retained while `created_by`, `author_id`, `leader_id`,
  `student_id` and message `user_id` references are cleared by the database.
* `ProfileController` verifies the current password, transfers a team to an
  existing member when possible, then logs out and deletes the account.
* Logging out before deletion prevents the session guard from saving a rotated
  remember token and recreating the deleted user row.

**Regression test:** `tests/Feature/SecurityRegressionTest.php::test_account_with_historical_records_can_be_deleted_without_losing_bde_history`
and `::test_account_without_owned_records_can_still_be_deleted`.

---

### H-02 — Privileged attributes were mass-assignable

**Severity:** High — a single careless `create($request->all())` or
`update($request->validated())` in a future change would have allowed a
learner to publish activities, take ownership of records or approve their own
idea.

**Evidence (before the fix)**

* `app/Models/Activity.php` — `$fillable` contained `created_by`,
  `source_idea_id`, `status`, `published_at`, `closed_at`.
* `app/Models/ChallengeIdea.php` — `$fillable` contained `author_id`,
  `status`, `reviewer_id`, `review_notes`, `reviewed_at`.
* `app/Models/Team.php` — `$fillable` contained `leader_id`, `is_registered`,
  `registered_at`, `join_code`.
* `app/Models/TeamMember.php` — `$fillable` contained `role`.
* `app/Models/Registration.php` — `$fillable` contained `status`,
  `registered_at`, `cancelled_at`.
* `app/Models/AuditLog.php` — every column was mass-assignable, including
  `user_id` and `action`.

At the time of the audit the request-to-model flow was already safe (the form
requests only expose validated keys), so this was **not** an exploitable
vulnerability yet. It is a defence-in-depth defect: the models did not enforce
the invariant that the rest of the code relied on.

**Fix — a single explicit rule:** *ownership, workflow state and privileges are
never mass-assignable; they are assigned attribute by attribute by server-side
code.*

* `Activity` — `$fillable` now only lists the learner/admin facing content
  columns. `created_by`, `source_idea_id`, `status`, `published_at` and
  `closed_at` are assigned explicitly.
* `ChallengeIdea` — `$fillable` is `['title', 'description', 'category']`.
  `author_id`, `status`, `reviewer_id`, `review_notes` and `reviewed_at` are
  assigned explicitly, and the initial state is now written explicitly as
  `IdeaStatus::Pending` instead of relying on the database default.
* `Team` — `$fillable` is `['name']`.
* `TeamMember`, `Registration`, `AuditLog` — `$guarded = ['*']`, so any
  attempt to mass-assign raises a `MassAssignmentException` instead of
  silently succeeding.
* `app/Services/RegistrationService.php`, `app/Services/TeamService.php`,
  `app/Models/AuditLog.php`, `app/Http/Controllers/*` — all writes updated to
  explicit attribute assignment.

**Regression tests:**
`tests/Feature/SecurityRegressionTest.php::test_privileged_model_attributes_are_not_mass_assignable`,
`::test_activity_ownership_and_status_cannot_be_forced_by_the_request`,
`::test_idea_moderation_state_cannot_be_forced_by_the_learner`.

---

### H-03 — Password reset without proof of e-mail ownership

**Severity:** Critical — account takeover and account enumeration.

**Status:** Fixed. Password recovery now uses Laravel's password broker. The
broker stores a hashed token, applies the configured 60-minute expiry and
per-address throttle, and only changes the account password when the submitted
e-mail and token match. A token is deleted after successful use. The response
to a reset request is the same for known and unknown addresses.

Reset links are generated from `APP_URL`, not the request's `Host` header. The
application sends `Referrer-Policy: no-referrer` so the token-bearing URL is
not copied into a cross-origin `Referer`. The request and submission endpoints
are rate-limited, and reset tokens are excluded from flashed validation input.
The production deployment must use a real SMTP provider; the `log` mailer is
for local development only because it writes the complete reset link to logs.

**Regression coverage:** `tests/Feature/Auth/PasswordResetTest.php` verifies
notification delivery, generic responses, token expiry and single use, password
rotation, request throttling, invalid token handling and token flash exclusion.

---

### H-04 — Registration and team endpoints had no rate limiting

**Severity:** High — abuse and resource exhaustion.

**Evidence:** `routes/web.php` applied `throttle:ideas` to `POST /ideas` only.
`POST /activities/{activity}/registrations`, `POST /activities/{activity}/teams`,
`POST /activities/{activity}/teams/join` and the team
register/unregister/leave/disband endpoints had no limit at all.

**Attack scenario:** an authenticated learner (or several coordinated accounts)
can flood the registration endpoint, producing large numbers of write
transactions, lock contention on SQLite and a noisy activity roster.

**Fix**

* `app/Providers/AppServiceProvider.php` — two new named limiters:
  `registration` and `teams`, both `20` requests per minute, keyed by account
  id when authenticated and by IP address otherwise.
* `routes/web.php` — the registration and team routes use
  `throttle:registration` / `throttle:teams`.

The limits are deliberately generous (20/minute) so that legitimate use — a
learner registering for several activities in a row — is never blocked.

**Regression tests:**
`tests/Feature/SecurityRegressionTest.php::test_registration_endpoint_is_rate_limited`,
`::test_idea_submission_is_rate_limited`.

---

### M-01 — Foreign-key columns were not indexed

**Severity:** Medium — performance and, on large catalogues, denial of service
through slow queries.

**Evidence:** MySQL and PostgreSQL create an index for every foreign-key
column; SQLite does not. Inspecting the schema of the live database showed
that `activities.created_by`, `activities.source_idea_id`,
`challenge_ideas.author_id`, `challenge_ideas.reviewer_id`,
`teams.leader_id`, `team_members.user_id`, `registrations.team_id` and
`audit_logs.user_id` had **no** index, while every one of them is used as a
filter (`User::registrations()`, `User::challengeIdeas()`,
`Activity::activeRegistrations()`, `Team::hasMember()`, `AuditLog` lookups).

**Fix:** new migration
`database/migrations/2026_10_20_090000_add_missing_foreign_key_indexes.php`
adds the eight missing indexes. It only adds indexes — no column is altered
and no data is touched. `down()` was verified on a disposable database.

---

### M-02 — "Row locks" do not exist on SQLite

**Severity:** Medium — the README and `AI_CONTEXT.md` promised a guarantee the
database cannot deliver.

**Evidence**

`README.md` and `AI_CONTEXT.md` stated: *"Duplicate registrations are blocked
by a unique `(user_id, activity_id)` constraint plus a transaction with row
locks."* `app/Services/RegistrationService.php` and
`app/Services/TeamService.php` call `->lockForUpdate()`, but
`vendor/laravel/framework/src/Illuminate/Database/Query/Grammars/SQLiteGrammar.php`
compiles `lockForUpdate()` to an empty string, and `PDO::beginTransaction()`
(used on PHP < 8.4, see `Illuminate/Database/SQLiteConnection.php:28-39`) opens
a *deferred* transaction. There is therefore **no row-level locking on
SQLite**: the capacity check and the insert are not serialised by a row lock.

**Fix / mitigation**

* `config/database.php` — `busy_timeout` (5 s) so a concurrent writer waits
  instead of failing immediately, `journal_mode` = `WAL` so readers are not
  blocked by a writer, `synchronous` = `NORMAL`.
* `app/Services/RegistrationService.php` — all write transactions now run with
  three attempts, so a "database is locked" collision is retried and the
  capacity check is re-evaluated.
* `app/Services/TeamService.php` — same.
* `README.md` and `AI_CONTEXT.md` — the claim was corrected.

**Residual risk:** duplicate registrations are still impossible (the unique
index is enforced by the database), but a strict, race-free *capacity* limit
requires MySQL or PostgreSQL, where `lockForUpdate()` is honoured. See
`SECURITY_CHECKLIST.md`.

---

### M-03 — Registration cancellation had no policy check

**Severity:** Medium — missing server-side authorization on a state-changing
route.

**Evidence:** `app/Http/Controllers/RegistrationController.php::destroy` called
`RegistrationService::cancel()` directly. The service did scope the query to
the authenticated user, so no data could be changed, but the route did not go
through the policy layer like every other learner route.

**Fix:** new policy ability `ActivityPolicy::cancelRegistration()` and
`$this->authorize('cancelRegistration', $activity)` in the controller.

---

### M-04 — The idea moderation console bypassed the policy layer

**Severity:** Medium — inconsistent authorization.

**Evidence:** `app/Http/Controllers/Admin/ChallengeIdeaController.php::index`
used `abort_unless($request->user()?->isAdmin(), 403)` while every other
administrative action used `authorize()`.

**Fix:** new policy ability `ChallengeIdeaPolicy::moderate()` used by `index`
and `show`. The `admin` middleware remains in place as a second layer.

---

### M-05 — Impossible registration windows were accepted

**Severity:** Medium — invalid business data accepted by an administrator.

**Evidence:** `app/Http/Requests/StoreActivityRequest.php` and
`UpdateActivityRequest.php` validated `registration_deadline` against
`registration_opens_at` but never against `starts_at`. An activity could be
published with the registration deadline one week *after* it starts.

**Fix:** both form requests now reject a registration deadline that is later
than the activity start, with an explicit message on the `registration_deadline`
field.

**Regression test:**
`tests/Feature/SecurityRegressionTest.php::test_registration_deadline_after_the_activity_start_is_rejected`.

---

### M-07 — The activity page loaded every team, member and member's user

**Severity:** Medium — unnecessary data exposure and query cost.

**Evidence:** `app/Http/Controllers/ActivityController.php::show` executed
`$activity->load(['creator', 'sourceIdea', 'teams.members.user'])`, i.e. it
loaded every team of the activity, every membership and the user record of
every member, while the Blade view only ever renders the visitor's own team
(`resources/views/activities/show.blade.php:53-60`).

**Fix:** the eager load was reduced to `['creator', 'sourceIdea']` plus the
registration counter; the visitor's own team is still loaded with
`TeamMember::with('team.members.user')`.

---

### M-08 — `composer.lock` was out of sync with `composer.json`

**Severity:** Medium — `composer install` printed
*"The lock file is not up to date with the latest changes in composer.json"*
and `composer validate` reported a lock file error. A deployment could have
installed versions that do not match the declared constraints.

**Fix:** `composer update --lock --no-install` was executed. Composer reported
*"Nothing to modify in lock file"* — **no package version was changed**, only
the lock file metadata was refreshed. `composer validate` now reports
`./composer.json is valid` and `composer audit` reports
*"No security vulnerability advisories found."*

---

### M-09 — The local SQLite database was not excluded from version control

**Severity:** Medium — personal data (names, e-mail addresses, registrations)
could be committed.

**Evidence:** `.gitignore` did not list `database/database.sqlite`, and the
application stores the whole database in that single file.

**Fix:** `.gitignore` now excludes `/database/*.sqlite` and the SQLite
sidecar files (`-journal`, `-shm`, `-wal`).

---

## 4. Findings that were not fixed

| ID | Finding | Why not fixed |
| --- | --- | --- |
| L-01 | `ends_at` equal to `starts_at` is accepted | The admin form uses two independent `datetime-local` inputs and administrators legitimately fill the same day. Rejecting equality would block existing workflows. `ends_at >= starts_at` is still enforced. |
| L-02 | `LIKE` wildcards (`%`, `_`) in the catalogue search are not escaped | The value is passed as a bound parameter, so there is no SQL injection; the only effect is a broader match. Escaping it requires raw SQL with an `ESCAPE` clause, which adds a raw-SQL construct to a controller for a marginal benefit. The search term is now length-bounded (100 characters), which removes the pathological-input case. |
| L-03 | The learner dashboard loads all of the learner's registrations without pagination | Bounded by the learner's own behaviour and eager-loaded, so there is no N+1. Adding a limit would silently hide rows; paginating would change the UI, which is out of scope. |
| L-04 | `youcast:promote-admin` performs no confirmation | The command is the documented, deliberate way to grant the administrator role and requires shell access. Adding an interactive prompt would break scripted usage. The action is written to `audit_logs`. |
| L-05 | Registration still answers "the e-mail has already been taken" | Enumeration through the registration form is accepted: blocking it would degrade a legitimate UX signal that the account already exists, and the same information is not needed for an attacker who can simply attempt a login (the login error is generic). |
| L-06 | E-mail verification is optional | `User` does not implement `MustVerifyEmail`; the verification routes exist but are never enforced. Making verification mandatory would lock out learners on a platform where the mail transport is `log` by default. Documented as a product decision. |
| L-08 | `database/seeders/DatabaseSeeder.php` is not idempotent | The two demo accounts are created with `firstOrCreate()` (idempotent), but the eight extra learners and the four demo activities are created with factories on every run, so `php artisan db:seed` executed twice duplicates them. Observed during the audit: 26 users and 13 activities after two runs. Fixing it would change the seeding behaviour that the README documents, so it was left alone and is recorded here instead. |
| L-09 | A conversation is addressed by its sequential id | `GET /messages/{conversation}` and `POST /messages/{conversation}/replies` use the primary key. Because `ConversationPolicy::view()` and `ConversationPolicy::reply()` both go through `Conversation::hasParticipant()`, a learner who does not own the conversation receives `403` and never reaches a message body, so the id cannot be used to read data. A random public identifier would hide the *existence* of a conversation as well; that was judged unnecessary for a closed BDE platform and would have added a column and an index for no security benefit. |

---

## 5. Security controls that were verified and left unchanged

| Control | Evidence |
| --- | --- |
| CSRF protection on every state-changing browser route | `bootstrap/app.php` applies the default `web` group; no route is excluded from `VerifyCsrfToken`; every Blade form contains `@csrf`. No state-changing operation uses `GET`. |
| Session fixation protection | `AuthenticatedSessionController::store()` calls `$request->session()->regenerate()` after a successful login; `destroy()` calls `invalidate()` and `regenerateToken()`. |
| Password storage | `User` casts `password` to `hashed` (bcrypt); `BCRYPT_ROUNDS=12` in `.env.example`; `Rules\Password::defaults()` on registration, reset and password change. |
| Login throttling | `routes/auth.php` applies `throttle:login` (5/minute, keyed by e-mail + IP) and `LoginRequest::ensureIsNotRateLimited()` (5 attempts). The `Lockout` event is now logged (IP only). |
| Password reset controls | Laravel broker with hashed 60-minute single-use tokens, 60-second per-address broker throttle, generic response, route throttles, remember-token rotation, and SMTP delivery requirement. Verified by `PasswordResetTest`. |
| Host and redirect protections | Laravel `TrustHosts` allows only the hostname in `APP_URL` outside local/testing; `SafeRedirect` keeps intended redirects same-origin. Reset email links are built from `APP_URL`. |
| Browser security headers | `AddSecurityHeaders` adds `nosniff`, `DENY`, `no-referrer`, a restrictive Permissions-Policy and production HSTS over HTTPS. Verified by `SecurityHardeningTest`. |
| Session cookie flags | `config/session.php` — `http_only` true, `same_site` `lax`; `secure` defaults on in production and the deployment checklist requires `SESSION_SECURE_COOKIE=true`. |
| No privilege escalation from the browser | `User::$fillable` does not contain `role`; `RegisteredUserController` assigns `UserRole::Learner` explicitly; the only promotion path is the `youcast:promote-admin` console command, which is audited. |
| Administrator route protection | `routes/web.php` applies `['auth', 'admin']`; `EnsureUserIsAdmin` aborts 403; each action additionally authorizes through a policy. |
| Resource-level authorization | `TeamController` rejects a team from another activity with `abort_unless($team->activity_id === $activity->id, 404)`; `ActivityPolicy`, `TeamPolicy` and `ChallengeIdeaPolicy` scope every learner action to the current account. |
| SQL injection | All queries go through Eloquent or the parameterised query builder. The only raw SQL in the application is `orderByRaw('case when status = ? then 0 else 1 end', [ActivityStatus::Published->value])` in `app/Http/Controllers/ActivityController.php:23`, which binds the value. |
| XSS | No `{!! !!}` unescaped output anywhere in `resources/views`; no `v-html`/`innerHTML` in `resources/js`; all user content is rendered with `{{ }}`. |
| Database integrity | Unique indexes protect registrations, teams and users. Historical author references use `nullOnDelete` to preserve content while anonymizing deleted accounts; dependent rows retain their explicit `restrictOnDelete` or `cascadeOnDelete` rules. |
| Transactional multi-step writes | Team registration, team creation, team disbanding and individual registration all run inside `DB::transaction()`. |
| Administrative audit trail | `activity.created`, `activity.updated`, `activity.published`, `activity.closed`, `activity.cancelled`, `activity.archived`, `idea.accepted`, `idea.rejected`, `idea.converted` and `user.promoted_admin` are written to `audit_logs` with the responsible administrator and a timestamp. |
| Secret management | `.env` is git-ignored; `.env.example` contains placeholders only; YouCast requires no third-party messaging credentials. |
| Error handling | `APP_DEBUG` defaults to `false` in `config/app.php`; the production checklist requires `APP_DEBUG=false`. Laravel's default handler renders a generic error page when debug is off. |
| Input validation | Every state-changing route uses a Form Request with typed rules; `Rule::enum()` is used for the activity type, `Rule::unique()` for team names and e-mail addresses, and lengths are bounded on every free-text field. |

---

## 6. Remaining limitations

1. **Capacity is not race-free on SQLite.** Duplicate registrations are
   impossible (unique index), but two simultaneous registrations can both pass
   the capacity check. Moving to MySQL or PostgreSQL restores the intended
   `lockForUpdate()` behaviour. Mitigation in place: busy timeout, WAL,
   transaction retries.
2. **TrustProxies is not configured.** Behind a reverse proxy, the IP-based
   limiters would see the proxy address. The account-scoped limiters are
   unaffected. Deployment requirement, documented in `README.md`.
3. Production must configure `APP_URL` to its public HTTPS hostname, set
   `SESSION_SECURE_COOKIE=true`, disable debug mode, configure trusted proxies
   when deployed behind a proxy, and use SMTP rather than the local log mailer.
4. **The npm dependency tree still reports advisories** in the Tailwind CSS 3
   build chain (`braces`, `chokidar`, `fast-glob`, `micromatch`,
   `postcss-selector-parser`). They are build-time only — none of these
   packages is shipped to the browser — and the remediation offered by npm is
   `tailwindcss@4`, a breaking change that would alter the visual design.
   See `BACKEND_IMPLEMENTATION_REPORT.md` for the exact audit output.
5. **E-mail verification is optional**, so a learner can register with an
   address they do not control. This is a product decision, not a defect, but
   it removes one of the usual signals used to detect duplicate/fake accounts.
6. **Conversations are addressed by a sequential id** (L-09). A non-participant
   is refused with `403` before any message body is rendered, so the id cannot
   be used to read data.

---

## 7. Second review round — the private messaging feature

The private messaging feature was implemented after the first review round and
was audited with the same checklist. The controls below were verified by the
automated tests and by a real HTTP smoke test against a temporary database.

| Control | Evidence |
| --- | --- |
| Participant-level authorization on every conversation read | `ConversationPolicy::view()` delegates to `Conversation::hasParticipant()`; the learner listing is additionally scoped with `Conversation::scopeForStudent()`. Verified by `PrivateMessagingTest::test_a_learner_cannot_read_another_learners_conversation`. |
| Participant-level authorization on every message write | `ConversationPolicy::reply()` requires participation **and** an open conversation; it backs `StoreMessageRequest::authorize()`, so the check happens before validation and before any write. Verified by `test_a_learner_cannot_reply_to_another_learners_conversation`. |
| The administration inbox is protected | The routes live in the `['auth', 'admin']` group; a learner receives `403`. Verified by `test_a_learner_cannot_reach_the_administration_inbox`. |
| Guests are redirected to the login page | The routes live in the `auth` group. Verified by `test_a_guest_cannot_read_a_conversation`. |
| Only learners open conversations | `ConversationPolicy::create()` requires the learner role; an administrator receives `403`. Verified by `test_an_administrator_cannot_open_a_conversation_as_a_learner`. |
| A closed conversation cannot be answered | `reply()` requires `isOpen()`; the administration area can close and reopen a conversation, and both transitions are audited. Verified by `test_a_closed_conversation_rejects_new_answers`. |
| Message contents and notifications never leave YouCast | Opening a conversation, an administrator reply and a learner reply are covered by `Http::fake()` and `Http::assertNothingSent()`. |
| Message contents never appear on a public page | The home page and the catalogue are rendered from the activity tables only. Verified by `test_message_contents_never_appear_on_public_pages`. |
| The message body is escaped | Every message body is rendered with `{{ }}`; no `{!! !!}` exists in `resources/views`. |
| Validation | `StoreConversationRequest` (subject ≤ 120, body ≤ 4 000) and `StoreMessageRequest` (body ≤ 4 000). |
| Rate limiting | `throttle:message-create` (5/hour) on opening a conversation, `throttle:message-reply` (60/hour) on answering. Verified by `test_opening_a_conversation_is_rate_limited`. |
| CSRF | Every messaging form contains `@csrf`; the endpoints are `POST`. |
| Database integrity | Foreign keys on `conversations.student_id` and `messages.user_id` (`nullOnDelete`), `messages.conversation_id` (`cascadeOnDelete`); indexes on `(conversation_id, created_at)` and `(conversation_id, read_at)` for the thread listing and the unread counter. |
| Mass assignment | `Conversation::$fillable` is `['subject']`; the owner, the status and the counters are assigned explicitly by `ConversationService`. `Message` uses `$guarded = ['*']`. |
| Transactions | Opening a conversation and writing an answer both run inside `DB::transaction()`. |
| History is preserved | Closing a conversation keeps every message. Deleting an account keeps private history and clears its author reference (`nullOnDelete`). |
| No destructive operation | The administration area can close and reopen a conversation; there is no delete route for conversations or messages. |
| Audit trail | `conversation.closed` and `conversation.reopened` are written to `audit_logs` with the responsible administrator. |

**Reviewer note.** The learner conversation list and the administration inbox
use `withCount()` for the unread counter and `with('lastMessage')` for the
preview, so the number of queries does not grow with the number of rows. This
is not assumed: `tests/Feature/MessageQueryEfficiencyTest.php` counts the
queries for one conversation and for five, and fails if the count grows. Both
assertions pass.

---

## Addendum — External notification integration removed

The former external notification integration has been removed. Opening a
conversation, replying and submitting an idea now perform no outbound HTTP
request. All messages, replies, ideas and moderation remain available in
YouCast. The current controls are listed in `SECURITY_CHECKLIST.md` §7b.8 and
§8.
