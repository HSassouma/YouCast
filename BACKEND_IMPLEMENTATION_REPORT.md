# YouCast — Backend Implementation Report

Companion documents: `BACKEND_SECURITY_AUDIT.md` (findings and evidence) and
`SECURITY_CHECKLIST.md` (control status).

> **Second round (product workflow specification).** This document describes two
> consecutive rounds of work. Section 1 to 9 describe the backend security and
> quality pass. **Section 10 describes the second round**, which implemented the
> confirmed product workflow specification: the post-authentication landing
> pages, the administrator "unpublish" action, the registration status on the
> activities page and the private messaging system.

---

## 1. State of the project before the work

The repository was a complete, working Laravel 12 application, not a
greenfield project:

* Complete authentication (Breeze), two roles (learner / BDE administrator),
  an activity catalogue, individual and team registrations, team workflow,
  learner idea submission with a moderation console, and an audit log for
  administrative actions.
* 8 existing migrations, 5 factories, 1 seeder, 10 feature test files
  (52 tests) and 1 unit test file.
* No Git repository was present in the working directory, so no commit history
  or diff was available; the audit was therefore based on reading the code and
  on running the application.

**Baseline established before any change**

| Command | Result |
| --- | --- |
| `composer install` | 113 packages installed (had to be run from a fresh checkout — `vendor/` was absent) |
| `php artisan migrate` | 10 migrations applied cleanly |
| `php artisan test` | **52 passed** (12 failures on the very first run were caused by a missing `public/build/manifest.json`; after `npm install && npm run build` the whole suite passed) |
| `composer validate` | `./composer.json is valid but your composer.lock has some errors` |
| `composer audit` | `No security vulnerability advisories found.` |
| `npm audit` | 7 advisories (2 moderate, 5 high) in the Tailwind CSS 3 build chain |
| `vendor/bin/pint --test` | 4 pre-existing style violations |

---

## 2. Skills and tools actually used

| Skill / tool | How it was used |
| --- | --- |
| `skills-search` | Ran the bundled `local_sources.py list` helper (returned "No source configuration" — the machine has no registered local skill repository) and fell back to `ccpm search laravel` / `ccpm search security` through the registry. No Laravel-, PHP- or security-specific skill existed in the registry, so none was installed and none is claimed. |
| `webapp-testing` | Used to write and run a native Playwright smoke test (Chromium, headless) that drives the real learner and administrator journeys against `php artisan serve`, and to capture console errors and failed requests. Script kept outside the repository in `%LOCALAPPDATA%\Temp\opencode\youcast_smoke.py`. |
| Sub-agents | Not used: the audit needed a single consistent view of one small codebase, and parallel exploration would have duplicated the same reading. |
| Repository tooling | `php artisan` (test, migrate, route:list, migrate:status, tinker), `composer` (validate, audit, update --lock), `npm` (install, run build, audit), `laravel/pint`. |

The instructions in `AI_CONTEXT.md` and `README.md` were followed throughout
(stack unchanged, two-role model preserved, no UI redesign, no new features
outside the agreed backend hardening).

---

## 3. Files changed and why

### Models — mass-assignment hardening and integrity helpers

| File | Change |
| --- | --- |
| `app/Models/Activity.php` | `$fillable` reduced to learner/admin-facing columns; new `createDraft()` (creates an activity as a draft owned by a given administrator, with an optional source idea); new `saveWithUniqueSlug()` (retries when a concurrent save claims the slug); `filledCount()` now reuses the counter loaded by `withCount('activeRegistrations')`; `causedByUniqueConstraint()` helper. |
| `app/Models/ChallengeIdea.php` | `$fillable` reduced to `['title', 'description', 'category']`; ownership and moderation state are assigned explicitly. |
| `app/Models/Team.php` | `$fillable` reduced to `['name']`; `generateJoinCode()` now retries and fails with a readable validation error instead of risking a unique-constraint crash. |
| `app/Models/TeamMember.php` | `$guarded = ['*']` — the membership role is a privilege and is never mass-assigned. |
| `app/Models/Registration.php` | `$guarded = ['*']` — a registration row is entirely server-owned. |
| `app/Models/AuditLog.php` | `$guarded = ['*']`; `record()` assigns each attribute explicitly. |
| `app/Models/User.php` | New `deletionBlockers()` listing the historical records that prevent an account from being deleted. |

### Policies and middleware

| File | Change |
| --- | --- |
| `app/Policies/ActivityPolicy.php` | New `cancelRegistration()` ability used by the cancellation route. |
| `app/Policies/ChallengeIdeaPolicy.php` | New `moderate()` ability used by the administration console. |

### Form requests

| File | Change |
| --- | --- |
| `app/Http/Requests/ConvertChallengeIdeaRequest.php` | **New.** Replaces the inline `validate()` call in the conversion controller; authorises through `can('convert', $idea)` and validates the target activity type with `Rule::enum()`. |
| `app/Http/Requests/StoreActivityRequest.php` | Rejects a registration deadline later than the activity start. |
| `app/Http/Requests/UpdateActivityRequest.php` | Same rule as the store request. |
| `app/Http/Requests/JoinTeamRequest.php` | Formatting only (Pint import order and FQCN shortening). |

### Controllers

| File | Change |
| --- | --- |
| `app/Http/Controllers/ActivityController.php` | Removed the eager load of every team/member/user of the activity; search term bounded to 100 characters; `bounded()` helper. |
| `app/Http/Controllers/Admin/ActivityController.php` | Activities are created through `Activity::createDraft()`; ownership and workflow state are assigned explicitly; updates go through `fill()` + `saveWithUniqueSlug()`; the search term is bounded. |
| `app/Http/Controllers/Admin/ChallengeIdeaController.php` | `authorize('moderate', …)` replaces the ad-hoc `abort_unless`; conversion uses the new Form Request; moderation state is assigned explicitly; the converted activity is created through `Activity::createDraft()`. |
| `app/Http/Controllers/ChallengeIdeaController.php` | Ownership and the initial `IdeaStatus::Pending` state are assigned explicitly instead of relying on the database default. |
| `app/Http/Controllers/RegistrationController.php` | The cancellation route now authorizes through the policy (`cancelRegistration`). |
| `app/Http/Controllers/ProfileController.php` | Account deletion is validated and refused with a readable message when the account still owns ideas, activities or led teams (previously an unhandled HTTP 500). |
| `app/Http/Controllers/Auth/PasswordResetLinkController.php` | The simplified flow opens the password form for a known address and shows the registration message otherwise. This intentionally reveals account existence and is **not production-safe** (see H-03). |

### Services

| File | Change |
| --- | --- |
| `app/Services/RegistrationService.php` | All writes assign attributes explicitly; the write transaction runs with three attempts so a SQLite "database is locked" collision is retried with a fresh capacity check; the in-transaction duplicate check was restored after the explicit-assignment rewrite. |
| `app/Services/TeamService.php` | All writes assign attributes explicitly; three-attempt transactions; Pint formatting. |

### Configuration, routing and infrastructure

| File | Change |
| --- | --- |
| `app/Providers/AppServiceProvider.php` | New `registration` and `teams` rate limiters (20/minute, keyed by account then IP); the `Lockout` event is logged with the source IP only; the deprecated `optional()` helper was replaced. |
| `routes/web.php` | `throttle:registration` on the registration routes and `throttle:teams` on the team routes. |
| `config/database.php` | SQLite tuning: `busy_timeout` 5 s, `journal_mode` WAL, `synchronous` NORMAL, `transaction_mode` IMMEDIATE (honoured on PHP ≥ 8.4 only — documented in the file). |
| `.env.example` | Production checklist comment (`APP_DEBUG=false`, `LOG_LEVEL=info`, `SESSION_SECURE_COOKIE=true`, `TrustProxies`); `SESSION_SECURE_COOKIE` now defaults to *unset* (HTTPS auto-detection) instead of `false`; SQLite tuning variables. |
| `.gitignore` | `/database/*.sqlite` and the SQLite sidecar files are excluded. |
| `composer.lock` | Content hash refreshed with `composer update --lock --no-install`; Composer reported *"Nothing to modify in lock file"* — **no package version changed**. |
| `README.md` | Corrected the "transaction with row locks" claim; added a test note, a production checklist and pointers to the three security documents. |

### Database

| File | Change |
| --- | --- |
| `database/migrations/2026_10_20_090000_add_missing_foreign_key_indexes.php` | **New.** Adds the eight foreign-key indexes that SQLite does not create automatically (`activities.created_by`, `activities.source_idea_id`, `challenge_ideas.author_id`, `challenge_ideas.reviewer_id`, `teams.leader_id`, `team_members.user_id`, `registrations.team_id`, `audit_logs.user_id`). Indexes only — no column is altered, no data is touched. |

### Tests

| File | Change |
| --- | --- |
| `tests/Feature/SecurityRegressionTest.php` | **New.** 23 regression tests, one per confirmed finding (see section 5). |
| `tests/Feature/ChallengeIdeaTest.php` | Idea submission, moderation and conversion are covered; the submission test asserts that no outbound HTTP request is emitted. |

---

## 4. Architecture, validation and authorization improvements

**Responsibility split.** Controllers stay thin: they resolve the request,
authorize, delegate to a service and redirect. Business rules live in
`RegistrationService` and `TeamService`; both are injected through the
constructor, so they can be exercised in isolation. The audit trail is a
separate, single-purpose service.

**One rule for privileged state.** Ownership (`created_by`, `author_id`,
`leader_id`, `user_id`), workflow state (`status`, `published_at`, `closed_at`,
`is_registered`, `registered_at`) and privileges (`role`, `reviewer_id`) are
never mass-assignable. The models enforce this, so a future
`Model::create($request->all())` fails loudly instead of silently corrupting
data.

**Validation.** All state-changing routes use a Form Request. The rules are
typed (`Rule::enum`, `integer`, `date`, `boolean`), length-bounded
(`max:180`, `max:8000`, `max:80`, `max:2000`), relationship-checked
(`Rule::unique('teams', 'name')->where('activity_id', …)`) and cross-checked
(`registration_deadline` ≤ `starts_at`, `registration_deadline` ≥
`registration_opens_at`, `min_team_size` ≤ `max_team_size`, team sizes
mandatory for a team challenge).

**Authorization.** Three policies cover the whole domain. Every learner
action is scoped to the current account by the policy *and* re-checked by the
service. Every administrative action is protected twice: by the `admin`
middleware and by a policy ability.

**Database.** The schema already had the right unique constraints and foreign
keys. The changes are additive: eight missing indexes, plus configuration that
makes SQLite behave under concurrent writes (busy timeout, WAL, retries).

---

## 5. Tests added

`tests/Feature/SecurityRegressionTest.php` — 21 tests:

| Area | Tests |
| --- | --- |
| Authentication | simplified password reset without e-mail (documented non-production limitation) · login is rate limited after repeated failures · logout invalidates the session |
| Authorization / IDOR | a learner cannot cancel another learner's registration · a learner cannot disband a team they do not belong to · a team from another activity is not accepted · a learner cannot reach the administration moderation pages · an idea cannot be reviewed or converted twice |
| Mass assignment | activity ownership and status cannot be forced by the request · idea moderation state cannot be forced by the learner · privileged model attributes are not mass-assignable · public registration never creates an administrator |
| Data integrity | an account owning historical records cannot be deleted · an account without owned records can still be deleted · a failed team registration leaves no partial registrations |
| Registration | registration is rejected for an unpublished activity · the registration endpoint is rate limited |
| Moderation | idea submission is rate limited |
| Validation | a registration deadline after the activity start is rejected · activity fields are length limited |
| Output encoding | user-supplied content is escaped in the rendered page |

---

## 6. Commands executed and their actual results

All commands were run from the repository root on Windows / PowerShell.

| Command | Result |
| --- | --- |
| `composer install --no-interaction --prefer-dist` | 113 packages installed. The first attempts failed with *"Failed to open stream: Permission denied"* while writing `vendor/composer/tmp-*.zip`; re-running with `COMPOSER_MAX_PARALLEL_HTTP=1` and a cache directory outside the workspace succeeded. No dependency was changed. |
| `php artisan key:generate` | Application key set. |
| `php artisan migrate --force` | 10 migrations applied, then `2026_10_20_090000_add_missing_foreign_key_indexes` applied separately (batch 2). |
| `php artisan migrate:status` | 11 migrations, all *Ran*. |
| `php artisan migrate --force` + `migrate:rollback --step=1` + `migrate --force` on a disposable `DB_DATABASE` file | The new migration applies, rolls back and re-applies cleanly. **No destructive command was run against the project database.** |
| `php artisan test` (baseline) | `Tests: 52 passed (135 assertions)`. |
| `php artisan test` (final) | `Tests: 75 passed (210 assertions)` — duration ≈ 5.9 s. |
| `vendor/bin/pint --test` | `{"tool":"pint","result":"passed"}` (was failing on 4 files before the work). |
| `composer validate --no-check-publish` | `./composer.json is valid` (was reporting a lock file error). |
| `composer audit` | `No security vulnerability advisories found.` |
| `npm install` / `npm run build` | 160 packages installed; `vite v7.3.7` build succeeded — `public/build/manifest.json`, `app-Ol32SI4O.css` (51.31 kB), `app-WC-ZjLzv.js` (106.90 kB). |
| `npm audit` | 7 vulnerabilities (2 moderate, 5 high) — all in the Tailwind CSS 3 build chain (`braces`, `chokidar`, `fast-glob`, `micromatch`, `postcss-selector-parser`). `npm audit fix --force` would install `tailwindcss@4.3.3`, a breaking change to the visual toolchain. **Not applied** — these are build-time packages that are never shipped to the browser, and the fix would alter the styling pipeline. |
| `php artisan route:list --except-vendor` | 47 application routes. |
| Playwright smoke test (Chromium, headless) | `SMOKE TEST PASSED` — no console errors, no failed requests, no 5xx responses. Covered: home page, catalogue, guest redirect, administrator login, administrator dashboard, activity list, activity creation, publication, rename (slug regeneration), learner login, learner dashboard, registration, cancellation, idea submission, idea moderation, idea conversion, profile page. |

**Failing tests: none.** The 12 failures seen on the very first run were caused
by the missing Vite manifest (no frontend build existed); they disappeared
after `npm run build` and are not related to any code change.

---

## 7. Migration safety

* No existing migration was edited or deleted. The only schema change is the
  new, additive index migration.
* No destructive command (`migrate:fresh`, `db:wipe`, `migrate:reset`) was
  ever run against the project database.
* The new migration was applied to the project database and to a disposable
  SQLite file, including a rollback and a re-apply.
* The migration only adds indexes: no column is created, altered or dropped,
  and no data is rewritten. Rolling it back removes the indexes and nothing
  else.
* `created_by`, `author_id` and `leader_id` keep `ON DELETE RESTRICT`, so the
  history of the BDE can never be destroyed by a cascade.

---

## 8. Performance observations

| Observation | Evidence | Action |
| --- | --- | --- |
| The activity page loaded every team, every membership and every member's user although only the visitor's own team is rendered. | `app/Http/Controllers/ActivityController.php` (before) — `load(['teams.members.user'])`; the view uses only `$membership->team`. | Reduced the eager load to `creator`, `sourceIdea` and the registration counter. |
| `Activity::filledCount()` and `hasCapacityFor()` issued a `COUNT` query on every call, including inside the registration transaction. | `app/Models/Activity.php` (before) — `activeRegistrations()->count()`. | `filledCount()` reuses the counter loaded by `withCount('activeRegistrations')` when it is present, so the catalogue and the dashboard no longer issue an extra query per row. |
| Eight foreign-key columns had no index, so every `where user_id = ?`, `where author_id = ?`, `where team_id = ?` … was a full table scan. | Live SQLite schema inspection before the migration. | Indexes added by the new migration. |
| The catalogue search accepted an unbounded term, so a crafted query string could force a wide `LIKE` scan. | `app/Http/Controllers/ActivityController.php` (before). | The term is now truncated to 100 characters. |
| Every write transaction could fail with "database is locked" under concurrency because SQLite has no row locks. | `Illuminate/Database/SQLiteConnection.php:28-39` (deferred transaction on PHP < 8.4); SQLite ignores `lockForUpdate()`. | `busy_timeout` = 5 s, WAL journaling, and three-attempt transactions that re-evaluate the capacity check. |
| The learner dashboard loads all of the learner's registrations. | `app/Http/Controllers/DashboardController.php`. | Not changed — the list is eager-loaded (no N+1) and bounded by the learner's own activity. Adding a limit would silently hide rows and paginating would change the UI. Documented as L-03. |

---

## 9. Recommended next steps

1. **Move to MySQL or PostgreSQL** for a production deployment. The capacity
   limit is only race-free where `lockForUpdate()` is honoured; the unique
   indexes already protect against duplicate registrations on SQLite.
2. **Configure `TrustProxies`** for the deployment so that the IP-based rate
   limiters see the real client address.
3. **Set the production environment explicitly**: `APP_ENV=production`,
   `APP_DEBUG=false`, `LOG_LEVEL=info`, `SESSION_SECURE_COOKIE=true`.
4. **Upgrade the Tailwind toolchain** (`tailwindcss@4`) in a dedicated change
   that verifies the visual output, which clears the remaining npm advisories.
5. **Add an explicit registration-conflict decision** for L-05, and consider
   making e-mail verification mandatory if the mail transport becomes
   reliable.
6. **Paginate the learner dashboard** once the catalogue grows, and decide
   whether a zero-length activity should be rejected.
7. **Consider a `youcast:promote-admin --dry-run`** mode and a demotion
   command, so administrator lifecycle management does not depend on a
   one-way command.

---

## 10. Second round � the confirmed product workflow specification

### 10.1 What the specification asked for and what the code did

| Specification | State before | Action |
| --- | --- | --- |
| �1 Public home page with Home / Login / Register in the navigation | Already present (`resources/views/layouts/navigation.blade.php`), verified in the browser | No change |
| �2 Redirect a learner to the student activities page after registration and login | Registration and login both redirected to `/dashboard` | Changed |
| �2 Redirect an administrator to the administration dashboard according to the server-verified role | Not implemented � administrators also landed on `/dashboard` | Changed |
| �2 Never allow public registration to assign an administrator role | Already enforced (`User::$fillable` excludes `role`, `RegisteredUserController` forces `UserRole::Learner`) | Verified, no change |
| �3 Student activities page shows the registration status of the authenticated student | The catalogue showed no registration state | Added |
| �3 Student activities page shows the participation requirements | The team size was only on the detail page | Added to the card |
| �4 Activity registration (identity, validation, capacity, duplicate check, unique constraint, transaction) | Already implemented and audited in the first round | Verified, no change |
| �5 Administrator "publish **or unpublish**" | Only publish / close / cancel / archive existed � no way to take a published activity back to draft | Added |
| �5 Prefer a safe cancellation or archival approach over destructive deletion | No destructive route existed; `ActivityPolicy::delete` was unused | Verified, no change |
| �6 Roles and account provisioning | Already implemented (`youcast:promote-admin`, audited) | Verified, no change |
| �7 **Private messaging system** | Not implemented | **Implemented** |
| �8 Database integrity and security | Audited in the first round | Extended to the new tables |
| �9 Tests for the thirteen required behaviours | 5 of 13 were covered | All 13 are now covered |

### 10.2 Post-authentication landing pages

`app/Http/Controllers/Auth/RegisteredUserController.php` now redirects a new
learner to `route('activities.index')`.

`app/Http/Controllers/Auth/AuthenticatedSessionController.php` now selects the
landing page from the **server-verified** role:

```php
$landing = $request->user()->isAdmin()
    ? route('admin.activities.index', absolute: false)
    : route('activities.index', absolute: false);

return redirect()->intended($landing);
```

`intended()` is kept so that a user who was redirected to the login page by
the `auth` middleware still lands on the page they asked for; the role-based
value is the default.

### 10.3 Administrator "unpublish" action

* `app/Policies/ActivityPolicy.php` � new `unpublish()` ability: an
  administrator may take a published or closed activity back to draft.
* `app/Http/Controllers/Admin/ActivityController.php` � the action sets the
  status to `Draft` and clears `published_at` / `closed_at`. **No registration
  and no history is deleted** � the activity is hidden from the catalogue, not
  destroyed, which is the safe alternative the specification asks for.
* `routes/web.php` � `POST /admin/activities/{activity}/unpublish`.
* `resources/views/admin/activities/show.blade.php` � the button is rendered
  only when `@can('unpublish', $activity)` passes.
* The action is written to `audit_logs` as `activity.unpublished`.

### 10.4 Registration status and participation requirements on the activities page

`app/Http/Controllers/ActivityController.php` resolves the visitor's
registrations with **one** query and passes the ids to the view:

```php
$registeredActivityIds = $request->user()
    ? Registration::query()
        ->where('user_id', $request->user()->id)
        ->where('status', RegistrationStatus::Registered)
        ->pluck('activity_id')->all()
    : [];
```

`resources/views/components/activity-card.blade.php` gained an optional
`registered` prop that renders a *"You are registered"* badge, plus the team
size and the location. The public home page keeps using the card without the
prop, so a guest never sees a registration state.

### 10.5 Private messaging system

**Data model** � `database/migrations/2026_10_21_090000_create_conversations_and_messages_tables.php`

* `conversations` � `student_id` (FK, `restrictOnDelete`), `subject` (120),
  `status` (`open` / `closed`), `last_message_at`, timestamps.
* `messages` � `conversation_id` (FK, `cascadeOnDelete`), `user_id` (FK,
  `restrictOnDelete`), `body`, `read_at`, timestamps, with indexes on
  `(conversation_id, created_at)` and `(conversation_id, read_at)`.

**Models** � `App\Models\Conversation` (`$fillable = ['subject']`; the owner,
the status and the counters are assigned explicitly), `App\Models\Message`
(`$guarded = ['*']`), and the new `App\Enums\ConversationStatus`.

**Authorization** � `App\Policies\ConversationPolicy`. Every read and write
goes through `Conversation::hasParticipant()`, which resolves the participants
of a conversation: the learner who opened it and the BDE administrators.

| Ability | Rule |
| --- | --- |
| `view` | participant |
| `create` | learner only � an administrator answers, it does not open conversations |
| `reply` | participant **and** the conversation is open |
| `close` / `reopen` | administrator, and only from the matching state |

**Service** � `App\Services\ConversationService` owns the write rules: opening
a conversation and its first message, appending an answer, and marking the
messages of the other side as read. Both writes run inside `DB::transaction()`.

**Controllers**

* `app/Http/Controllers/ConversationController.php` � the learner side:
  `index` (own conversations only), `create`, `store`, `show`.
* `app/Http/Controllers/MessageController.php` � the single answer endpoint
  used by both sides; the policy decides who may write and the controller
  returns the author to the area they came from.
* `app/Http/Controllers/Admin/ConversationController.php` � the administration
  side: `index` (the inbox, with a status filter), `show`, `close`, `reopen`.
  Closing and reopening are audited.

**Form requests** � `StoreConversationRequest` (subject = 120, body = 4 000)
and `StoreMessageRequest` (body = 4 000); the latter authorizes through the
`reply` ability before validation.

**Routes**

| Method | URI | Name |
| --- | --- | --- |
| GET | `/messages` | `messages.index` |
| GET | `/messages/create` | `messages.create` |
| POST | `/messages` (`throttle:message-create`) | `messages.store` |
| GET | `/messages/{conversation}` | `messages.show` |
| POST | `/messages/{conversation}/replies` (`throttle:message-reply`) | `messages.replies.store` |
| GET | `/admin/messages` | `admin.messages.index` |
| GET | `/admin/messages/{conversation}` | `admin.messages.show` |
| POST | `/admin/messages/{conversation}/close` | `admin.messages.close` |
| POST | `/admin/messages/{conversation}/reopen` | `admin.messages.reopen` |

**Views** � `resources/views/messages/{index,create,show}.blade.php` and
`resources/views/admin/messages/{index,show}.blade.php`. They reuse the
existing components (`x-app-layout`, `x-flash`, `x-status-badge`,
`x-empty-state`, `x-input-label`, `x-text-input`, `x-input-error`) and follow
the existing Tailwind design tokens; no page was redesigned.

**Navigation** � learners get a *Messages* link, administrators an *Inbox*
link, in both the desktop and the mobile navigation.

**Privacy:** message bodies are never sent outside YouCast and no message body
is rendered on a public page. Both properties are covered by
`PrivateMessagingTest`, including `Http::assertNothingSent()` on opening a
conversation and on both reply paths.

**Account deletion** � `User::deletionBlockers()` now also reports *private
conversations* and *messages you wrote*, because the foreign keys restrict the
deletion. The private history therefore cannot be destroyed by a cascade.

### 10.6 Files changed in the second round

**Created**

* `app/Enums/ConversationStatus.php`
* `app/Models/Conversation.php`
* `app/Models/Message.php`
* `app/Policies/ConversationPolicy.php`
* `app/Services/ConversationService.php`
* `app/Http/Requests/StoreConversationRequest.php`
* `app/Http/Requests/StoreMessageRequest.php`
* `app/Http/Controllers/ConversationController.php`
* `app/Http/Controllers/MessageController.php`
* `app/Http/Controllers/Admin/ConversationController.php`
* `database/migrations/2026_10_21_090000_create_conversations_and_messages_tables.php`
* `database/factories/ConversationFactory.php`
* `database/factories/MessageFactory.php`
* `resources/views/messages/index.blade.php`
* `resources/views/messages/create.blade.php`
* `resources/views/messages/show.blade.php`
* `resources/views/admin/messages/index.blade.php`
* `resources/views/admin/messages/show.blade.php`
* `tests/Feature/ProductWorkflowTest.php`
* `tests/Feature/PrivateMessagingTest.php`
* `tests/Feature/MessageQueryEfficiencyTest.php`

**Modified**

* `app/Http/Controllers/Auth/RegisteredUserController.php` � landing page.
* `app/Http/Controllers/Auth/AuthenticatedSessionController.php` � role-based landing page.
* `app/Http/Controllers/Admin/ActivityController.php` � `unpublish()`.
* `app/Http/Controllers/ActivityController.php` � registration state of the visitor.
* `app/Policies/ActivityPolicy.php` � `unpublish()`.
* `app/Providers/AppServiceProvider.php` � policy registration, `message-create` and `message-reply` limiters.
* `app/Models/User.php` � `conversations()`, `messages()`, extended `deletionBlockers()`.
* `routes/web.php` � the messaging routes and the unpublish route.
* `resources/views/components/activity-card.blade.php` � registration badge, team size, location.
* `resources/views/activities/index.blade.php` � passes the registration state.
* `resources/views/admin/activities/show.blade.php` � the unpublish button.
* `resources/views/layouts/navigation.blade.php` � *Messages* / *Inbox* links.
* `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/Auth/AuthenticationTest.php` � the new landing pages.
* `README.md`, `AI_CONTEXT.md` � documentation.

### 10.7 Tests added in the second round

`tests/Feature/ProductWorkflowTest.php` (14 tests) covers the specification's
required behaviours: a learner can log in and reach the activities page, the
catalogue shows the registration status, a registration references the existing
authenticated user, registering for another activity creates **no** additional
user, closed / unpublished / full activities reject registration, a learner
cannot see another learner's registration information, a learner cannot create
or edit an activity, an administrator can create and update one, an
administrator can unpublish without deleting, and public registration never
creates an administrator.

`tests/Feature/PrivateMessagingTest.php` (15 tests) covers the messaging
feature: a learner can open a conversation, an administrator cannot, a learner
cannot read or answer another learner's conversation, a guest is redirected, an
administrator can read and answer, a learner can answer, a closed conversation
refuses answers, an administrator can close and reopen, a learner cannot close
a conversation or reach the inbox, message bodies never leave YouCast or appear
on a public page, opening a conversation is rate limited, and the body is
validated. The tests assert that no outbound HTTP request is emitted when a
conversation is opened or answered.

`tests/Feature/MessageQueryEfficiencyTest.php` (2 tests) guards the message
listings against an N+1 query: the query count is compared for one and for five
conversations and the test fails if it grows.

`tests/Feature/Auth/AuthenticationTest.php` gained one test for the
administrator landing page. **32 tests were added in this round.**

### 10.8 Commands executed in the second round

| Command | Result |
| --- | --- |
| `php artisan test` | **107 passed (315 assertions)** � was 75 before this round |
| `vendor/bin/pint --test` | `{"tool":"pint","result":"passed"}` |
| `php artisan migrate --force` | `2026_10_21_090000_create_conversations_and_messages_tables` applied |
| `migrate` ? `rollback --step=1` ? `migrate` on a disposable `DB_DATABASE` | The new migration applies, rolls back and re-applies cleanly |
| `php artisan route:list --except-vendor` | 57 application routes (47 before this round) |
| Playwright smoke test | **SMOKE TEST PASSED** � 15 journeys, no console error, no request with a status = 400 |
| `php artisan db:seed --force` | Still runs (not idempotent, see L-08) |

The browser smoke test covered: the home page links, the guest redirect from
`/messages`, the registration landing page, registering for an activity, the
registration badge on the catalogue, opening a private conversation, a second
learner not seeing it, the administrator landing on the administration
dashboard, reading and answering the conversation, closing it, the learner
reading the answer and seeing the closed state, and unpublishing an activity
until it disappears from the catalogue.

### 10.9 Notes and deliberate decisions

* **The learner button still reads "Register", not "S'inscrire".** The
  specification quotes the French label of the original product. The whole
  application is in English, so the label was kept in English: changing it
  would have made this one page inconsistent with the rest of the interface.
* **Conversation ids are sequential.** A non-participant receives `403` before
  any body is rendered, so the id cannot be used to read a message. See L-09.
* **Administrators are participants by role.** The workflow is "a learner
  writes to the BDE", so the participants are the learner who opened the
  conversation and the BDE administrators. A learner who is not the owner is
  refused on every route.
* **Notifications stay inside YouCast.** Opening or answering a conversation
  and submitting an idea emit no external notification. No third-party
  credential, queue worker or mail transport is required for these workflows.
* **No pagination was added to the message bodies.** A conversation is a
  thread; the current implementation loads the whole thread. If a conversation
  ever grows very large, the thread query would need a page size.
