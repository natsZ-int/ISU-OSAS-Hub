## 14. Software Engineering Test Plan Mapping

### 14.1 Test Strategy

Verification combines automated tests with structured inspection. Automated tests exercise the business rules that carry the greatest risk — authorization, workflow transitions, duplicate prevention, and the eligibility and matching calculations. Inspection covers the qualities that automated tests measure poorly: layout, responsive behaviour, accessibility, and the absence of routes that must not exist.

The test suite is organised by concern rather than by screen. Feature tests drive the application through its HTTP surface and assert on status codes, database state and rendered content. Unit tests exercise services directly without the HTTP layer. The suite is executed with PHPUnit.

### 14.2 Requirement-to-Test Mapping

| Requirement family | Primary test artefacts | Verification focus |
| --- | --- | --- |
| FR-AUTH | `Auth/OtpVerificationTest`, `Auth/StudentRegistrationTest`, `Auth/LoginPasswordToggleTest` | Registration validation, OTP issue and consume, expired and exhausted codes, login flow |
| FR-RBAC | `Security/AuthorizationSecurityTest`, `Flow/AdminFlowTest` | Role isolation, direct URL access refusal, gate enforcement |
| FR-PROF | `StudentProfileManagementTest`, `UserManagementTest` | Field restrictions, profile creation, activation and deactivation, administrative editing |
| FR-PASS | `StudentProfileManagementTest` | Passcode set, verify, lock, unlock, administrative reset |
| FR-ENR | `Auth/EnrollmentVerificationTest` | Term scoping, decision recording, enrollment gate behaviour |
| FR-SCH | `ScholarshipSearchTest`, `ScholarshipAnnouncementTest`, `Scholarship/ScholarshipDuplicateAndAccessTest` | Visibility rules, search and sort, announcement targeting, duplicate access |
| FR-ELIG | `Unit/ScholarshipEligibilityServiceTest`, `EligibilityAccessTest` | Per-criterion outcomes, not-applicable handling, overall decision |
| FR-MATCH | `Unit/ScholarshipMatchingServiceTest`, `Unit/ScholarshipRecommendationServiceTest`, `MatchingAccessTest`, `Student/RecommendationTest` | Factor scoring, weight normalisation, ranking order, categorisation |
| FR-APP | `ScholarshipWorkflowTest`, `Unit/ScholarshipApplicationWorkflowTest`, `Scholarship/WorkflowEdgeCasesTest`, `Flow/ScholarshipApplicationFlowTest` | Transition table, approval guard, terminal states, end-to-end cycle |
| FR-DOC | `Flow/ScholarshipApplicationFlowTest`, `ScholarshipWorkflowTest` | Upload validation, verification, rejection remarks, download authorization |
| FR-GUID | `GuidanceAppointmentTest`, `Flow/GuidanceAppointmentFlowTest` | Slot generation, conflict rejection, workflow decisions, cancellation window |
| FR-SVC | `StudentServicesModuleTest`, `Flow/StudentServiceFlowTest` | Submission validation, assignment, transitions, terminal states |
| FR-NOTIF | `NotificationSystemTest`, `EmailNotificationsTest` | Creation, duplicate suppression, read state, queued email |
| FR-ANLYT | `Admin/AnalyticsTest`, `Unit/AnalyticsServiceTest`, `AdminDashboardTest` | Metric correctness, period filtering, export shape |
| FR-AUDIT | `AuditLogSystemTest`, `Audit/AuditLogIntegrityTest` | Entry creation, filtering, immutability, detail view |
| FR-SEARCH | `QuickSearchTest` | Role scoping, term normalisation, result limits |
| NFR-PERF | Inspection plus index review | Index coverage for filters and sorts; pagination on every list |
| NFR-SEC | `Security/AuthorizationSecurityTest`, inspection | Hash storage, private files, absence of destructive routes |
| NFR-PORT / ACC | Inspection at 1440, 1024, 768 and 390 px | Drawer behaviour, single-column forms, focus visibility |

### 14.3 Test Environments

| Environment | Purpose | Data |
| --- | --- | --- |
| Automated | Continuous verification of business rules | Fresh in-memory or file database per run |
| Local development | Manual walkthrough of the interface | Seeded demonstration data |
| Presentation | Defence demonstration | Seeded data refreshed so that at least one scholarship is open |

> A demonstration environment must have an open scholarship, at least one published counselor availability block, at least one appointment type, and a student whose enrollment is verified for the configured current term. Without these, the core screens correctly display empty states, which reads as a defect to an audience.

### 14.4 Known Coverage Gaps

| Area | Gap |
| --- | --- |
| Enrollment record creation | Untestable through the interface because no creation route exists |
| Counselor service-request handling | Untestable because no route exists |
| Self-service password reset | Untestable because no route exists |
| Email delivery | Verified only up to queueing; transport behaviour depends on the environment |
| Appointment reminders | Verified by command execution; scheduling depends on the operating system scheduler |

<!-- pagebreak -->

## 15. Key Computer Science Components

The system is a straightforward web application, but it applies several established computing concepts deliberately. Naming them is useful for the defence, because each corresponds to a specific implementation rather than to a general claim.

| Component | Concept | Implementation |
| --- | --- | --- |
| Workflow control | Finite state machine | Three transition tables constrain application, service-request and appointment lifecycles; invalid transitions throw a dedicated exception |
| Access control | Role-based access control with attribute checks | Route role middleware, named gates, and record-ownership policies combined |
| Eligibility screening | Rule-based expert system with explanation | Five independent predicate evaluators returning status, values and a human-readable justification |
| Ranking | Multi-criteria weighted scoring with dynamic renormalisation | Factor scores in the unit interval, weighted by administrator percentages, divided by the applicable weight sum |
| Repository of record | Relational model with referential integrity | Twenty-two business tables with foreign keys, unique constraints and composite indexes |
| Asynchronous processing | Producer–consumer queue | Notification email dispatched as a queued job; a worker consumes the queue |
| Idempotency | Exactly-once side effect under retry | Duplicate-notification suppression and reminder timestamp guards |
| Auditability | Append-only event sourcing of privileged actions | Audit entries written with actor, action, module and record reference; no update or delete path |
| Credential protection | One-way hashing with per-record salt | Account passwords and profile passcodes stored only as hashes |
| Search | Normalised input with bounded cost | Trim, whitespace collapse, length cap, then parameterised pattern matching |
| Serialisation of time | Term-scoped validity | Verification records keyed by academic year and semester, compared against configuration |
| Progressive enhancement | Unobtrusive client scripting | Server-rendered pages; scripting confined to navigation, confirmation, search suggestions and password visibility |

### 15.1 Architectural Style

The application is a monolithic, server-rendered MVC application with a service layer. This is a deliberate choice for an office system: it deploys as a single artefact, requires no client build pipeline, is debuggable by a single developer, and remains usable on modest hardware. The trade-off is that interactivity is limited to what progressive enhancement can add without a client framework.

### 15.2 Separation of Concerns

| Layer | Responsibility | Examples |
| --- | --- | --- |
| Presentation | Layout, components, progressive enhancement | Blade views, single stylesheet, single script |
| Routing and middleware | Entry control and request gating | Role middleware, active-account check, enrollment check |
| Request validation | Shape and bounds of input | Form request classes |
| Application service | Business rules and orchestration | Eligibility, matching, workflow, scheduling, notification, analytics, audit |
| Domain model | Invariants, relationships, status vocabularies | Eloquent models, enums, status constants |
| Persistence | Schema, constraints, indexes | Migrations |

<!-- pagebreak -->

## 16. Project-Specific Algorithm Notes

This section records the algorithms that determine system behaviour in enough detail to be reimplemented or audited. All are deterministic; none uses randomness or machine learning.

### 16.1 Eligibility Evaluation

Inputs are a student profile and a scholarship. Five criteria are evaluated independently.

```
evaluate(profile, scholarship):
    criteria = []
    for each rule in [GWA, income, program, year level, enrollment status]:
        if scholarship does not configure the rule:
            append (status = NOT_APPLICABLE, mandatory = false)
        elif profile lacks the required value:
            append (status = NOT_MET, mandatory = true)
        else:
            append (status = MET if predicate holds else NOT_MET,
                    mandatory = true,
                    student_value, required_value, explanation)

    overall = NOT_ELIGIBLE if any(c.mandatory and c.status == NOT_MET)
              else ELIGIBLE
```

The predicates are: student GWA is less than or equal to the configured GWA threshold, where a lower value is better on the 1.00–5.00 scale; student family income is less than or equal to the configured income ceiling; the student program, upper-cased and trimmed, appears in the scholarship's eligible program list; the student year level appears in the eligible year-level list; and the student enrollment status satisfies the scholarship's required enrollment status when one is configured.

An unconfigured criterion is not a failure. It is recorded as not applicable and excluded from the decision, which is why a scholarship that restricts only income can still return an eligible verdict.

### 16.2 Match Scoring

```
weigh(factor) = configured_weight / 100
score = sum(factor.score * weigh(factor) for applicable factors)
        / sum(weigh(factor) for applicable factors)
match_percentage = round(score * 100, 2)
```

Each factor produces a score in the unit interval. Academic, financial, program and year-level factors are binary — one if satisfied, zero if not. Document readiness is fractional: the count of ready documents divided by the count of required documents. When the application has reached document verification or approval, only verified documents count as ready; before that, any upload counts. Factors that do not apply are excluded from both the numerator and the denominator, so the score is renormalised over the factors that actually distinguish this student from this scholarship.

A match score is produced only for scholarships the student is eligible for. Ineligible scholarships are reported with the failing mandatory reasons rather than a misleadingly low score.

### 16.3 Ranking

Eligible results are sorted by descending match percentage, then ascending deadline, then ascending title, then ascending identifier. The three tie-breakers guarantee a stable order that does not change between page loads, which matters because students compare lists across sessions.

### 16.4 Slot Generation

```
slots(counselor, date, appointment_type):
    settings = active scheduling settings
    if date is in the past or beyond maximum_future_booking_days: return []
    duration = appointment_type.duration_minutes ?? settings.default_duration
    for each active availability window for date.weekday:
        for cursor from window.start to window.end - duration step duration:
            candidate = [cursor, cursor + duration]
            keep if candidate is inside office hours
            keep if candidate.start >= now + minimum_booking_notice
            drop if candidate overlaps any blocking appointment for the counselor on that date
    return remaining candidates
```

Booking repeats every check inside a transaction, then additionally rejects a candidate that overlaps any blocking appointment for the same student. Blocking statuses are requested and confirmed only.

### 16.5 Business Number Generation

Applications are numbered `SCH-<year>-<six digits>`. The generator reads the highest number for the current year, increments the sequence, left-pads to six digits, and if the candidate already exists recurses to try the next value. Service requests use `SR-<year>-<six digits>` and take a row lock inside a transaction before reading the highest sequence, which serialises concurrent submissions.

### 16.6 Notification Deduplication and Reminder Idempotency

Before creating a notification, the service looks for an existing unread notification with the same recipient, type and related record. If one exists, creation is skipped and the existing record is reused. For reminders, the appointment's reminder timestamp is written before the message is dispatched, so a crash after the stamp cannot produce a duplicate on the next run, and overlapping scheduler runs cannot both send.

### 16.7 Search Normalisation

Every search term is trimmed, internal whitespace is collapsed to a single space, and the term is truncated to two hundred characters. Terms are then embedded in a parameterised pattern. Wildcard characters in user input are escaped so that a user cannot widen the match unintentionally.

<!-- pagebreak -->

## 17. Source Basis and Clarifications

### 17.1 Basis of Preparation

Every statement in this specification was derived from the delivered source tree. No feature was inferred from screenshots, marketing text, or the reference document's project content. Where behaviour could not be established with confidence, it is reported as an open item in Section 13 rather than described as fact.

| Deliverable statement | Source of truth |
| --- | --- |
| Roles and permissions | `roles` seeder, `Role` model, `routes/web.php` groups, `AuthServiceProvider` gates, `app/Policies` |
| Route inventory and screens | `routes/web.php`, `resources/views` |
| Data model | `database/migrations` |
| Status vocabularies and transitions | `app/Enums`, workflow service transition tables, model constants |
| Eligibility and scoring | `ScholarshipEligibilityService`, `ScholarshipMatchingService`, `FactorResult` |
| Scheduling rules | `GuidanceAppointmentSchedulingService`, `GuidanceSchedulingSettings` |
| Notification behaviour | `NotificationService`, `SendNotificationEmail`, reminder command |
| Audit behaviour | `AuditLogService`, `AuditLogController`, audit migration |
| Interface design system | `public/css/app.css`, `resources/views/components` |
| Environment | `composer.json`, `package.json`, environment files, `.env.example` |
| Verification evidence | `tests/Feature`, `tests/Unit` |

### 17.2 Clarifications Made During Verification

1. **GWA semantics.** The scholarship field is named `minimum_gwa`, but the predicate is "student GWA is less than or equal to this value". On the Philippine scale a lower number is better, so the field behaves as a maximum acceptable GWA. The specification follows the actual predicate and flags the naming as a source observation.
2. **Enrollment gate fallback.** The gate permits students who have no verification records at all. This is a deliberate migration safeguard, not an oversight, and is documented as such.
3. **Administrator guidance access.** Administrators can see every appointment but cannot decide one. This is treated as a deliberate separation of duties, recorded as an open decision rather than a defect.
4. **Duplicate application constraint.** The uniqueness rule is permanent across all statuses, which blocks reapplication after rejection. Documented as a constraint with a stated consequence.
5. **Design system.** The stylesheet loaded by the layout, not the utility framework listed in the asset dependencies, is treated as the design system of record.
6. **"OSAS Staff".** Treated as a description of Administrator and Counselor duties, never as a role, because no account can hold it.

### 17.3 Assumptions Recorded for the Record

| ID | Assumption |
| --- | --- |
| A-01 | The deployed environment uses the MariaDB and SMTP configuration present in the working environment file, not the SQLite and log-mail defaults of the example file. |
| A-02 | The current academic term is 2025–2026, first semester, as configured at the time of writing; the value is expected to change each term. |
| A-03 | Demonstration data will be refreshed so that at least one scholarship is open, because the seeded scholarship windows fall in 2025. |
| A-04 | Appointment types will be created by an administrator before any booking demonstration, because the guidance seeder is not registered in the database seeder. |
| A-05 | The three implemented roles are the roles for the purposes of this specification, pending the decisions listed in Section 13.1. |

### 17.4 Closing Statement

This specification describes a complete, internally consistent office system whose scholarship, guidance and student-service workflows are implemented end to end, with four documented gaps that are narrow, well understood, and listed explicitly in Section 13. Every functional requirement carries an identifier and an implementation status; every identifier is traceable to a module, a UML artefact, a wireframe and a mockup through the accompanying Requirements Traceability Matrix.
