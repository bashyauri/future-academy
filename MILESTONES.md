# Future Academy: Web to Mobile Parity Roadmap

Last updated: 2026-07-17

This roadmap tracks the Laravel web platform and the Expo mobile app with one guiding rule: the mobile app should mirror the working web experience wherever the workflow makes sense on a phone.

## Current Product Snapshot

### Web Platform - Working

- Student, parent/guardian, school, and community registration is available on the web signup form.
- Login, logout, email verification, password reset, profile settings, password changes, two-factor settings, and single-session enforcement are in place.
- Student onboarding supports stream and subject selection.
- Student dashboard, practice, JAMB practice, mock exams, lessons, video progress, analytics, and subscription/trial access controls are implemented.
- Admin/staff Filament resources exist for users, subjects, topics, exam types, quizzes, questions, lessons, roles, permissions, subscriptions, mock group management, logs, maintenance tools, and subscription debugging.

### Parent Dashboard - Working

The parent dashboard is now a real operational dashboard, not just a placeholder.

- Parents can link an existing student by email.
- Parents can create and link a new student account directly from the dashboard.
- New student accounts are created with invitation/reset email flow so the student can finish setup securely.
- Parents can resend invitation emails for students who have not completed onboarding.
- Linked students are loaded with enrolled subjects and subscriptions.
- Per-student subscription mapping is enforced; subscriptions are no longer treated as global access for every child.
- Unassigned legacy subscriptions are surfaced for cleanup/renewal.
- Parent cards show student readiness, paid access status, and subscription state.
- Parents can renew, cancel eligible recurring subscriptions, or pay for a specific student.
- Progress metrics include videos watched, quiz attempts, average score, mock exams taken, best mock score, enrolled subjects, lessons completed, learning time, Bunny video views, watch time, and completion rate.
- Progress/performance links route to analytics with the selected student context.
- Manage Enrollment links route parents into the student's lesson/subject enrollment flow.
- Metrics are gated so unpaid students show unlock/payment prompts instead of progress details.

### Mobile App - Working / In Progress

- Expo Router app shell exists with auth, onboarding, tabs, practice, JAMB, mock, quiz, and lessons routes.
- Mobile login uses the Laravel Sanctum API and stores the token through the shared auth context.
- Mobile onboarding, dashboard, practice setup, JAMB setup, mock setup, quiz players, lessons, offline DB helpers, and shared design components are present.
- Mobile signup has now been added to mirror web registration account types: student, parent/guardian, school, and community.
- The mobile auth stack now includes login, register, and onboarding screens.
- Parent/guardian accounts bypass student onboarding and route directly to the parent dashboard.
- Student-specific tabs (Practice, JAMB, Mock) are hidden when a guardian account is logged in.
- The mobile parent dashboard is fully implemented and mirrors the web parent dashboard.

## Delivery Gates

Apply these gates before each milestone is accepted.

- Engineering: lint/type checks pass, focused tests pass, and no known blocker bugs remain.
- QA: Android smoke test completed on at least one low-end and one mid-range device in light and dark mode.
- UAT: product owner/client validates the workflow against the milestone acceptance criteria.
- Release: release notes, rollback notes, environment values, and build artifacts are ready.

## Backend API Milestones

### Milestone 1: Mobile Auth and Profile - Complete

- Sanctum bearer-token login exists at `POST /api/v1/login`.
- Current user profile exists at `GET /api/v1/user`.
- Logout exists at `POST /api/v1/logout`.
- Login validation, invalid credentials, inactive account, token access, and logout are covered by tests.

### Milestone 2: Mobile Registration Parity - Complete

- Public mobile registration exists at `POST /api/v1/register`.
- Registration accepts the same public web account types: `student`, `guardian`, `school`, and `community`.
- Student, school, and community accounts receive trial access; guardian accounts do not.
- Registration issues a Sanctum token for the current device.
- Registration dispatches the standard Laravel registered event so email verification behavior stays aligned with the web flow.
- Mobile registration validation covers duplicate emails, account type validation, password confirmation, and required device name.

### Milestone 3: Curriculum and Configuration APIs - Complete

- Enrolled subject list and configuration subject list are exposed.
- Exam types, years, and mock format configuration are exposed.
- Subject download and JAMB practice download endpoints are present for mobile/offline flows.

### Milestone 4: Practice, JAMB, and Mock APIs - Complete

- Practice start, active attempts, load, batch load, save, submit, exit, and question count endpoints are present.
- JAMB session initialization, start, load, submit, exit, and download endpoints are present.
- Mock group list, detail, download, and multi-subject session initialization endpoints are present.

### Milestone 5: Lessons, Video Progress, Analytics, and Sync - Complete

- Lessons list/detail/progress/complete endpoints are present.
- Analytics overview, subject performance, quiz history, and study streak endpoints are present.
- Offline sync and sync status endpoints are present.

### Milestone 5b: Parent API - Complete

- `ParentApiController` exists at `app/Http/Controllers/Api/ParentApiController.php`.
- Dashboard overview endpoint at `GET /api/v1/parent/dashboard` returns linked students, combined stats, and subscriptions.
- Link existing student by email at `POST /api/v1/parent/students/link`.
- Create and link a new student account at `POST /api/v1/parent/students/create`.
- Resend invitation email at `POST /api/v1/parent/students/{studentId}/resend-invitation`.
- Student-scoped analytics: overview, subject performance, quiz history, and study streak under `GET /api/v1/parent/students/{studentId}/analytics/*`.
- Student enrollment read at `GET /api/v1/parent/students/{studentId}/subjects`.
- Student enrollment update at `POST /api/v1/parent/students/{studentId}/enrollment`.
- All routes registered in `routes/api.php` under the `parent` prefix with `auth:sanctum` and throttle middleware.

## Mobile Parity Milestones

### Milestone 6: Auth, Signup, and Onboarding - Complete / Verify on Device

- Login screen works with `POST /api/v1/login`.
- Signup screen mirrors web account types and posts to `POST /api/v1/register`.
- Auth context stores the token and user object including `account_type`.
- Unauthenticated users are redirected to login.
- Authenticated students who have not completed onboarding are redirected to onboarding.
- Guardian, school, and community accounts bypass onboarding and go straight to the tabs dashboard.
- Remaining QA: verify email-verification handling on a real mobile registration flow.

### Milestone 7: Student Mobile Dashboard - Complete / Verify on Device

- Mobile home/dashboard mirrors the web student dashboard.
- Shows enrolled subjects, analytics overview, study streak, recent quiz activity.
- Preserves pull-to-refresh and network/error states.

### Milestone 8: Parent Mobile Dashboard - Complete

Mobile mirrors the working web parent dashboard.

- Guardian, school, and community account types are routed to `<ParentDashboard />` on the home tab; student-specific tabs are hidden.
- Dashboard header shows the guardian's name, a family icon, and a mini stats row (students linked, average score, mock exams taken).
- Overview grid shows videos watched, lessons completed, quizzes taken, and combined study time across all linked students.
- Each student card shows: avatar initial, name, email, Ready/Setup-Needed badge, access label badge, and (if not onboarded) a Resend Invitation button.
- Progress metrics (video and lesson progress bars, avg score, study time, mock count) are shown when the parent has a paid subscription for that student; a locked state with a subscribe prompt is shown otherwise.
- Track Progress button navigates to `parent/student-analytics` with the selected student's ID and name as route params.
- Enrollment button opens a bottom-sheet modal that loads all available subjects, shows currently enrolled subjects as checked, allows toggling, and saves via `POST /api/v1/parent/students/{id}/enrollment`.
- Add Students section provides Link Existing (by email) and Create New (name + email + invitation) inline forms.
- Subscriptions section lists all active/pending parent subscriptions with plan name, assigned student, status badge, and expiry date.
- Student analytics screen at `app/parent/student-analytics.tsx` shows streak, quizzes, avg score, total study time, per-subject performance cards, and full quiz history with pull-to-refresh.
- Pull-to-refresh is supported on both the dashboard and the analytics screen.

### Milestone 9: Practice and JAMB Quiz Players - Complete / Verify on Device

- Practice setup and player match web behavior for subject, exam type, year, question count, save, submit, and results.
- JAMB setup and player support four-subject sessions, subject tabs, navigator, countdown, auto-submit, and score breakdown.

### Milestone 10: Mock Exam Player - Complete / Verify on Device

- Mock setup supports single-subject and multi-subject flows.
- Mock player loads server sessions, maintains countdown, submits on expiry, and shows per-subject results.
- Anti-cheat/backgrounding behavior needs to be verified on Android devices.

### Milestone 11: Lessons and Video - Complete / Verify on Device

- Mobile lesson lists and lesson detail screens mirror web lesson access behavior.
- Video progress syncs to the Laravel lesson progress APIs.
- Bunny/video rendering needs to be verified on Android devices and slow networks.

### Milestone 12: Offline Support and Sync - Optional / Hardening

- SQLite schema and offline helpers exist.
- Download manager, local question pack storage, reconnect detection, and sync retry UX still need end-to-end QA.

### Milestone 13: Mobile Payments - Complete / Verify on Device

- `PaymentApiController` implemented in the backend to support stateless payments via Paystack metadata.
- `/pricing` screen created in the mobile app using `expo-web-browser` to securely handle checkout sessions.
- Parent dashboard UI updated with a "Subscribe" prompt.
- Student dashboard UI updated with an "Unlock Full Access" upgrade prompt if unsubscribed.

## Immediate Next Work

1. **QA: Mobile Payments** — smoke test the subscription process on an Android device to ensure the `expo-web-browser` flow correctly opens Paystack and returns to the app.
2. **QA: Parent dashboard on device** — smoke test the parent dashboard on Android in both light and dark mode; verify link, create, resend invitation, enrollment modal, and Track Progress navigation against the local API.
3. **QA: Student features on device** — verify the student dashboard, practice/JAMB quiz players, mock exam player, and video lessons flow end-to-end on an Android device.
4. **[Complete]** Run mobile type/lint checks: `npm exec tsc --noEmit` inside the `mobile/` directory to fix any remaining TS errors.