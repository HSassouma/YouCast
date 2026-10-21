# YouCast — AI Coding Context

## 1. Project goal
YouCast is a web platform for the YouCode BDE to publish and manage challenges, events, and competitions. YouCode learners can discover activities, register individually or as teams, and submit ideas for future challenges.

## 2. Required technology stack
- **Backend:** Laravel (PHP)
- **Database:** SQLite for the initial version
- **Frontend:** Laravel Blade + Tailwind CSS
- **Build tooling:** Vite + npm
- **JavaScript:** Use only where it adds real value; keep the app simple and maintainable.
- Optional libraries only when justified: Alpine.js for lightweight interactions, Chart.js for dashboard charts, FullCalendar for calendar views, SweetAlert2 for confirmations, and Lucide icons.
- Check installed versions and official compatibility before adding dependencies. Do not change the agreed stack without asking.

## 3. Main user roles
- **Learner:** Browse published activities, view details, register, join/create a team when allowed, cancel a registration when permitted, submit challenge ideas, and open a private conversation with the BDE.
- **BDE administrator:** Create, edit, publish, unpublish, close, and archive activities; manage registrations and teams; review ideas; answer learner conversations; view activity statistics.
- Public registration to the admin role must never be allowed. Admin access must be explicitly granted by a trusted administrator.

## 4. Core features
1. Authentication and role-based access.
2. Activity catalogue with search/filtering and activity detail pages.
3. Activity types: individual and team challenges/competitions; events may have their own registration rules.
4. Registration management: capacity limits, deadlines, duplicate-registration prevention, and clear statuses.
5. Team workflow: create or join teams when enabled, team-size limits, membership rules, and team registration status.
6. Admin dashboard: manage activities, participants, teams, and activity-specific statistics.
7. Challenge idea submission form for learners.
8. Idea review workflow for admins: pending, accepted, rejected; allow an accepted idea to be turned into an activity without losing its history.
9. Private messaging between learners and the BDE: participants only and never published to a public page. Messages, administrative replies and ideas stay inside YouCast; no external messaging integration is used.
10. Useful feedback, validation messages, empty states, loading states where relevant, and responsive accessible pages.

## 5. Security requirements — mandatory
- Use Laravel authentication and authorization; enforce permissions on the server with middleware, policies, or gates. Hiding a button is not authorization.
- Validate and authorize every state-changing request. Use Form Requests where useful.
- Keep CSRF protection enabled; escape rendered user content in Blade; avoid unsafe raw HTML.
- Protect against mass assignment by explicitly defining fillable/guarded fields and validated data.
- Prevent duplicate registrations and capacity race conditions with database constraints and transactions where appropriate.
- Apply rate limits to login, registration, and idea submission; use safe error messages and secure session settings.
- Keep secrets and credentials out of source control and logs. Use `.env`; commit only `.env.example` with placeholders.
- Validate uploads strictly if uploads are introduced; do not add file uploads unless required.
- Never trust client-side role IDs, activity status, prices, capacities, or team membership.
- Record important admin actions when useful, and avoid logging passwords, tokens, or sensitive personal data.
- Use Laravel migrations and automated tests for access control and critical workflows.

## 6. Product decisions recorded for this MVP
- Learners may create their own accounts. Admin access is granted only with `php artisan youcast:promote-admin {email}`.
- Activities are created as drafts and must be published by a BDE administrator.
- Individual events and individual challenges register one learner per activity.
- Team challenges require a team. The leader creates the team, shares a join code, then registers the whole team.
- Duplicate registrations are blocked by a unique `(user_id, activity_id)` constraint, checked inside a write transaction.
- Capacity is enforced inside that transaction. SQLite does not support row-level locks, so the guarantee comes from the unique indexes, the database write lock, a busy timeout and transaction retries; a strict capacity guarantee requires MySQL or PostgreSQL.
- Learners may cancel while the activity is published and cancellation is allowed.
- There is no waitlist in this version.
- Accepted ideas can be converted into a draft activity without deleting the original idea.
- A published activity can be unpublished back to draft; nothing is ever deleted.
- A learner opens a private conversation with the BDE; only the learner who opened it and the BDE administrators can read or answer it.
- Password reset is deliberately simplified for this MVP: e-mail lookup, then a new-password form, with no e-mail, token, OTP, 2FA or administrator verification. It does **not** prove e-mail ownership and is **not production-safe**; see finding H-03 in `BACKEND_SECURITY_AUDIT.md`.

## 7. Suggested implementation order
1. Audit local environment and repository; confirm Laravel/PHP/Composer/Node versions.
2. Configure Laravel, SQLite, migrations, and basic app layout.
3. Implement authentication and secure role/permission checks.
4. Build activity CRUD and publishing lifecycle.
5. Implement individual registrations and capacity/deadline rules.
6. Add teams and team-registration workflow.
7. Build learner idea submissions and admin review.
8. Add dashboard metrics, filters, and optional charts/calendar if useful.
9. Test permissions, validation, duplicates, capacity, team rules, and failure cases; review accessibility and responsive behavior.

**Priority:** correctness, security, clear user flows, and maintainability before extra animations or nonessential features.
