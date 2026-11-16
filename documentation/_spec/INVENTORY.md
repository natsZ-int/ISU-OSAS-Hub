# ISU OSAS Hub — Canonical Documentation Inventory

This file is the **single source of truth** for naming and identifiers used across the
SRS, UML diagrams, wireframes, mockups, and the traceability matrix. Every artifact
must use the identifiers defined here verbatim.

Derived from a read-only audit of the Laravel source at
`app/`, `routes/web.php`, `database/migrations/`, and `resources/views/`.

---

## 1. Verified System Facts

| Item | Value | Source |
|---|---|---|
| Framework | Laravel 12, PHP ^8.2 | `composer.json` |
| Database | MariaDB, schema `isu_osas_hub` | `.env` |
| Frontend | Blade + vanilla CSS/JS (no Tailwind, no JS framework) | `resources/views/layouts/app.blade.php` |
| Active stylesheet | `public/css/app.css` (~69 KB, 17 sections) | layout `<link>` |
| Active script | `public/js/app.js` (~19 KB) | layout `<script>` |
| Auth | Custom session auth + custom 6-digit OTP email verification | `Auth\*`, `OtpVerificationService` |
| Roles | `student`, `counselor`, `admin` | `Role` model, `RoleSeeder` |
| Domain tables | 22 | `database/migrations/` |
| Blade views | 91 files (55 pages + components/partials) | `resources/views/` |
| Session driver | database (`sessions`) | `.env` |

### Framework tables (excluded from the ERD business model)

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

---

## 2. Actor Registry

| Actor ID | Actor | Implementation | Notes |
|---|---|---|---|
| ACT-01 | Student | `role.name = student` | Self-registers; enrollment-gated services |
| ACT-02 | Counselor | `role.name = counselor` | Guidance owner; enrollment verifier |
| ACT-03 | Administrator | `role.name = admin` | Full administrative authority |
| ACT-04 | Scheduler / Queue Worker | `guidance:send-reminders`, `SendNotificationEmail` job | Non-human actor |

**Requested-but-absent roles.** `Adviser`, `OSAS Staff`, and `Super Admin` do **not** exist
as roles, gates, routes, or UI. "OSAS Staff" appears only as descriptive copy and is
implemented as the union of Administrator + Counselor permissions for enrollment
verification.

---

## 3. Module Registry

| ID | Module | Status | Primary Classes |
|---|---|---|---|
| M01 | Authentication & Account Lifecycle | Implemented | `Auth\LoginController`, `RegisterController`, `VerificationController`, `OtpVerificationService` |
| M02 | Access Control (RBAC) | Implemented | `EnsureRole`, `EnsureActive`, `AuthServiceProvider`, `Policies\*` |
| M03 | Student Profiles | Implemented | `Student\ProfileController`, `Admin\StudentProfileController` |
| M04 | Profile Security (Passcode) | Implemented | `StudentProfilePasscodeService`, `Student\ProfileSecurityController` |
| M05 | Enrollment Verification | Partially Implemented | `Staff\EnrollmentVerificationController`, `EnsureCurrentEnrollmentVerified` |
| M06 | Scholarship Management | Implemented | `Admin\ScholarshipController`, `Admin\RequirementController` |
| M07 | Eligibility Evaluation | Implemented | `ScholarshipEligibilityService` |
| M08 | Matching & Recommendations | Implemented | `ScholarshipMatchingService`, `ScholarshipRecommendationService` |
| M09 | Application Workflow | Implemented | `Student\ApplicationController`, `Admin\ApplicationReviewController`, `ScholarshipApplicationWorkflowService` |
| M10 | Document Management | Implemented | `Student\DocumentController`, `Admin\DocumentVerificationController` |
| M11 | Guidance & Counseling | Implemented | `GuidanceAppointment{Scheduling,Workflow}Service`, counselor/student/admin controllers |
| M12 | Student Services | Partially Implemented | `Student\ServiceRequestController`, `Admin\ServiceRequestController`, `StudentServiceRequestWorkflowService` |
| M13 | Notifications | Implemented | `NotificationService`, `SendNotificationEmail`, `NotificationController` |
| M14 | Analytics & Reporting | Implemented | `AnalyticsService`, `Admin\AnalyticsController` |
| M15 | Audit Logging | Implemented | `AuditLogService`, `Admin\AuditLogController` |
| M16 | Search & Navigation | Implemented | `LiveSearchService`, `QuickSearchController`, `ScholarshipSearchService` |

---

## 4. Identifier Conventions

| Prefix | Meaning | Example |
|---|---|---|
| `FR-<GROUP>-nn` | Functional requirement | `FR-AUTH-01` |
| `NFR-<GROUP>-nn` | Non-functional requirement | `NFR-SEC-01` |
| `BR-nn` | Business rule | `BR-07` |
| `AC-nn` | Acceptance criterion | `AC-12` |
| `EIR-nn` | External interface requirement | `EIR-03` |
| `DR-nn` | Data requirement | `DR-05` |
| `UC-nn` | UML use case | `UC-04` |
| `CL-nn` / `ERD-nn` | Class / ER diagram | `CL-01`, `ERD-01` |
| `SQ-nn` | Sequence diagram | `SQ-03` |
| `AD-nn` | Activity diagram | `AD-02` |
| `SM-nn` | State machine diagram | `SM-01` |
| `ARCH-nn` | Architecture diagram | `ARCH-01` |
| `WF-<role>-nn` | Wireframe page (`G`=guest/shared, `S`=student, `C`=counselor, `A`=admin) | `WF-S-05` |
| `MK-nn` | High-fidelity mockup screen | `MK-04` |

Functional requirement groups: `AUTH`, `RBAC`, `PROF`, `PASS`, `ENR`, `SCH`, `ELIG`,
`MATCH`, `APP`, `DOC`, `GUID`, `SVC`, `NOTIF`, `ANLYT`, `AUDIT`, `SEARCH`.

Non-functional groups: `PERF`, `SEC`, `USA`, `REL`, `MAINT`, `COMPAT`, `PORT`, `ACC`, `DATA`.

---

## 5. Screen Registry (Wireframes + Mockups)

`WF` = wireframe identifier, `MK` = mockup identifier (empty when the screen is not
mocked up at high fidelity), `Route` = the Laravel route name, `View` = Blade file.

### Shared / Guest

| WF | Screen | Route | View |
|---|---|---|---|
| WF-G-01 | Login | `login` | `auth/login` |
| WF-G-02 | Student registration | `register` | `auth/register` |
| WF-G-03 | OTP email verification | `verification.notice` | `auth/verify-otp` |
| WF-G-04 | Error / access denied | — | `errors/403`, `errors/404` |
| WF-G-05 | Notification center | `notifications.index` | `notifications/index` |
| WF-G-06 | App shell (sidebar + topbar) | — | `layouts/app`, `components/navigation/*` |

### Student

| WF | Screen | Route | View |
|---|---|---|---|
| WF-S-01 | Student dashboard | `student.dashboard` | `dashboards/student` |
| WF-S-02 | Profile view | `student.profile.show` | `student/profile/show` |
| WF-S-03 | Profile edit | `student.profile.edit` | `student/profile/edit` |
| WF-S-04 | Profile security / passcode | `student.profile.security` | `student/profile/security` |
| WF-S-05 | Scholarship browse + filters | `student.scholarships.index` | `student/scholarships/index` |
| WF-S-06 | Scholarship detail | `student.scholarships.show` | `student/scholarships/show` |
| WF-S-07 | Eligibility preview | `student.scholarships.eligibility` | `student/eligibility/show` |
| WF-S-08 | Recommendations | `student.scholarships.recommendations` | `student/recommendations/index` |
| WF-S-09 | Match list | `student.scholarships.matches` | `student/recommendations/matches` |
| WF-S-10 | My applications | `student.applications.index` | `student/applications/index` |
| WF-S-11 | Application detail + documents | `student.applications.show` | `student/applications/show` |
| WF-S-12 | Guidance counselors | `student.guidance.counselors` | `student/guidance/counselors` |
| WF-S-13 | Appointment slots + booking | `student.guidance.slots` | `student/guidance/slots` |
| WF-S-14 | My appointments | `student.guidance.appointments` | `student/guidance/appointments` |
| WF-S-15 | Appointment detail | `student.guidance.appointments.show` | `student/guidance/show` |
| WF-S-16 | Service catalog | `student.services.catalog` | `student/services/catalog` |
| WF-S-17 | Service request form | `student.services.create` | `student/services/create` |
| WF-S-18 | My service requests | `student.services.index` | `student/services/index` |
| WF-S-19 | Service request detail | `student.services.show` | `student/services/show` |

### Counselor

| WF | Screen | Route | View |
|---|---|---|---|
| WF-C-01 | Counselor dashboard | `counselor.dashboard` | `dashboards/counselor` |
| WF-C-02 | Weekly availability | `counselor.guidance.availability` | `counselor/guidance/availability` |
| WF-C-03 | Appointment queue | `counselor.guidance.appointments` | `counselor/guidance/appointments` |
| WF-C-04 | Appointment detail + decisions | `counselor.guidance.appointments.show` | `counselor/guidance/show` |
| WF-C-05 | Enrollment verification queue | `enrollment-verifications.index` | `staff/enrollment-verifications/index` |
| WF-C-06 | Enrollment verification review | `enrollment-verifications.show` | `staff/enrollment-verifications/show` |

### Administrator

| WF | Screen | Route | View |
|---|---|---|---|
| WF-A-01 | Admin dashboard | `admin.dashboard` | `dashboards/admin` |
| WF-A-02 | User list | `admin.users.index` | `admin/users/index` |
| WF-A-03 | User create / edit | `admin.users.create`, `admin.users.edit` | `admin/users/create`, `admin/users/edit` |
| WF-A-04 | Password reset (admin-initiated) | `admin.users.reset-password` | `admin/users/reset-password` |
| WF-A-05 | Student profile list | `admin.student-profiles.index` | `admin/student-profiles/index` |
| WF-A-06 | Student profile detail | `admin.student-profiles.show` | `admin/student-profiles/show` |
| WF-A-07 | Student profile edit | `admin.student-profiles.edit` | `admin/student-profiles/edit` |
| WF-A-08 | Scholarship list | `admin.scholarships.index` | `admin/scholarships/index` |
| WF-A-09 | Scholarship create / edit | `admin.scholarships.create` | `admin/scholarships/create` |
| WF-A-10 | Requirements manager | `admin.scholarships.requirements.index` | `admin/scholarships/requirements` |
| WF-A-11 | Matching weights | `admin.scholarship-matching.weights.edit` | `admin/matching/weights` |
| WF-A-12 | Application review queue | `admin.scholarship-applications.index` | `admin/applications/index` |
| WF-A-13 | Application review detail | `admin.scholarship-applications.show` | `admin/applications/show` |
| WF-A-14 | Guidance appointment types | `admin.guidance.types` | `admin/guidance/types` |
| WF-A-15 | Guidance scheduling settings | `admin.guidance.settings` | `admin/guidance/settings` |
| WF-A-16 | Guidance oversight list | `admin.guidance.appointments` | `admin/guidance/appointments` |
| WF-A-17 | Guidance oversight detail | `admin.guidance.appointments.show` | `admin/guidance/show` |
| WF-A-18 | Service type list | `admin.service-types.index` | `admin/services/types/index` |
| WF-A-19 | Service type create / edit | `admin.service-types.create` | `admin/services/types/create` |
| WF-A-20 | Service request queue | `admin.student-service-requests.index` | `admin/services/requests/index` |
| WF-A-21 | Service request detail + assign | `admin.student-service-requests.show` | `admin/services/requests/show` |
| WF-A-22 | Audit log list | `admin.audit-logs.index` | `admin/audit-logs/index` |
| WF-A-23 | Audit log detail | `admin.audit-logs.show` | `admin/audit-logs/show` |
| WF-A-24 | Analytics overview | `admin.analytics.index` | `admin/analytics/index` |
| WF-A-25 | Analytics — scholarships | `admin.analytics.scholarships` | `admin/analytics/scholarships` |
| WF-A-26 | Analytics — guidance | `admin.analytics.guidance` | `admin/analytics/guidance` |
| WF-A-27 | Analytics — student services | `admin.analytics.services` | `admin/analytics/services` |

Total: **58 wireframe screens** (6 shared + 19 student + 6 counselor + 27 admin).

---

## 6. State Machines (verbatim from source)

### SM-01 — Scholarship Application (`ScholarshipApplicationWorkflowService`)

```
draft ──(student submits)──▶ submitted
submitted ──▶ under_review
under_review ──▶ document_verification | rejected
document_verification ──▶ approved | rejected
approved, rejected = terminal
```

Guard: `approved` requires every required requirement to have an uploaded document whose
`verification_status = verified`.

### SM-02 — Student Service Request (`StudentServiceRequestWorkflowService`)

```
submitted ──▶ processing | requires_information | cancelled
processing ──▶ requires_information | completed | cancelled
requires_information ──▶ processing | cancelled
completed, cancelled = terminal
```

### SM-03 — Guidance Appointment (`GuidanceAppointmentWorkflowService`)

```
requested ──▶ confirmed | rejected | cancelled
confirmed ──▶ completed | cancelled
completed, cancelled, rejected = terminal
Blocking statuses: requested, confirmed
```

### SM-04 — Scholarship Lifecycle (`Scholarship` constants)

```
draft ⇄ open ⇄ closed ⇄ archived
Visible to students only when status = open AND today within [start, deadline]
```

### SM-05 — Enrollment Verification (`EnrollmentVerificationStatus`)

```
pending ──▶ verified | rejected | unable_to_verify
```

---

## 7. Design Tokens (verbatim from `public/css/app.css`)

| Token | Value |
|---|---|
| `--isu-green` | `#0b5d3b` |
| `--isu-green-dark` | `#083f28` |
| `--isu-green-deep` | `#062e1d` |
| `--isu-gold` | `#c9a227` |
| `--bg` | `#f4f7f5` |
| `--text` | `#1f2937` |
| `--muted` | `#6b7280` |
| `--border` | `#e5e7eb` |
| Radius | `6px` / `10px` / `14px` |
| Sidebar width | `264px` |
| Topbar height | `60px` |
| Font stack | `system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif` |
| Breakpoints | `1100px`, `1024px`, `768px`, `480px` |
| Status | success `#0f7a4d`, warning `#b45309`, danger `#b91c1c`, info `#1d4ed8`, neutral `#4b5563` |

---

## 8. Blade Component Inventory (reusable UI contract)

`alert`, `card`, `confirm-button`, `empty-state`, `filter-bar`, `filter-field`,
`form-error`, `live-search`, `loading-indicator`, `metric-card`, `page-header`,
`pagination-wrapper`, `section-tabs`, `status-badge`, `table-wrapper`,
`charts/{bar,card,donut,empty-state,legend,line,stacked-bar}`,
`navigation/{link,sidebar,topbar}`, `partials/fsm-progress`, `partials/student-profile-fields`.

---

## 9. Documented Gaps (must appear in SRS §13 and the traceability matrix)

| Ref | Gap | Evidence |
|---|---|---|
| GAP-01 | No UI or route creates `student_enrollment_verifications` rows; staff can only review existing records | `Staff\EnrollmentVerificationController` has index/show/update only; no `::create` call anywhere |
| GAP-02 | Students with **zero** verification records bypass the enrollment gate | `User::hasVerifiedEnrollmentForCurrentTerm()` returns `true` when no rows exist |
| GAP-03 | Counselor can be assigned a service request in workflow logic but has no routes/UI | `StudentServiceRequestWorkflowService`, `handled_by`; no `counselor.service-requests.*` routes |
| GAP-04 | `counselor_notes` / `student_notes` are displayed but cannot be authored | columns + views only; no request validation or route |
| GAP-05 | No self-service password reset; admin-initiated only | `password_reset_tokens` + `config/auth.php` exist; no routes |
| GAP-06 | `GuidanceSeeder` is not registered in `DatabaseSeeder` | appointment types empty on fresh install |
| GAP-07 | Seeded scholarship dates are 2025 (expired at time of writing) | `ScholarshipSeeder` |
| GAP-08 | Admin guidance screens are view-only; workflow needs a counselor actor | `Admin\GuidanceController` has index/show only |
| GAP-09 | Duplicate application block is permanent (no re-apply after rejection) | `uniq_student_scholarship` unique index |
| GAP-10 | `withdrawn` status exists in the badge map but has no FSM transition | `status-badge` component vs workflow service |
| GAP-11 | Legacy dual schema on appointments (`appointment_type` string + `appointment_type_id`) | `2025_07_16_000003` migration |
| GAP-12 | Duplicate scholarship search-index migrations | `2025_01_06_*` and `2025_07_15_*` |
