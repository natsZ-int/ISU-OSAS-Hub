# Unified Modeling Language Diagrams

**ISU OSAS Hub — Integrated Student Affairs and Services Management System**

Isabela State University · Office of Student Affairs and Services

| Item | Details |
| --- | --- |
| Deliverable | Final UML diagram set |
| Diagrams | 26 diagrams across the 7 required types |
| Source format | Mermaid (`.mmd`), editable |
| Rendered format | SVG (scalable) and PNG (2× raster) |
| Source directory | `documentation/UML/source/` |
| Rendered directory | `documentation/UML/export/` |
| Basis | Read-only audit of `app/`, `routes/web.php`, `database/migrations/`, `resources/views/` |
| Version | 1.0 |
| Date | 15 November 2026 |

> Every diagram is generated from the delivered source code. Status vocabularies,
> transition tables, actor names and relationship cardinalities are reproduced
> verbatim from the implementation, not inferred from the project brief.

<!-- pagebreak -->

## Contents

<!-- toc -->

<!-- pagebreak -->

## 1. How to read this set

The set contains 26 diagrams covering the seven types required by the project brief. Each diagram exists twice: as an editable Mermaid source file and as a rendered image. The source file is the artefact of record; the rendered image is what appears in this document.

| Type | Count | Identifier range |
| --- | --- | --- |
| Use case diagram | 1 | `UC-01` |
| Class diagram | 1 | `CL-01` |
| Entity relationship diagram | 1 | `ERD-01` |
| Sequence diagrams | 10 | `SQ-01` … `SQ-10` |
| Activity diagrams | 6 | `AD-01` … `AD-06` |
| State machine diagrams | 5 | `SM-01` … `SM-05` |
| Architecture diagrams | 2 | `ARCH-01`, `ARCH-02` |

### 1.1 Naming conventions

| Element | Convention | Example |
| --- | --- | --- |
| Actor | The role key exactly as stored in `roles.name`, with an initial capital | `Student`, `Counselor`, `Administrator` |
| Class | The Eloquent model name | `ScholarshipApplication` |
| Table | The database table name in upper snake case (ERD) | `SCHOLARSHIP_APPLICATIONS` |
| Status value | The literal stored value in the database, in lower snake case | `document_verification` |
| Transition | `current -> target`, matching the workflow service transition table | `under_review -> rejected` |

### 1.2 Rendering conventions

Diagrams were produced with Mermaid 12.2.0 and rendered through a headless browser. The following conventions are used where Mermaid's native vocabulary differs from classical UML:

| UML construct | Rendering used | Note |
| --- | --- | --- |
| Use case | Rounded capsule with a `UC-nn` identifier | A flowchart is used because Mermaid has no native use-case renderer; associations are undirected lines |
| Actor | Stadium shape, filled institutional green | Human actors only; the scheduler is drawn as a rectangle because it is a process |
| `«include»` | Dashed arrow, labelled | Direction points from base to included use case |
| `«extend»` | Dashed arrow, labelled | Direction points from extension to base use case |
| Class | Compartmented rectangle | Base framework classes (`Model`, `Controller`) are omitted |
| Entity | Table block with PK / FK / UK markers | Framework tables are omitted |
| State | Rounded state with labelled transition | Notes carry guards and side effects |

<!-- pagebreak -->

## 2. Diagram inventory

| ID | Type | Title | Source file | Rendered |
| --- | --- | --- | --- | --- |
| UC-01 | Use case | System use cases and actors | `source/UC-01-use-case.mmd` | `export/UC-01-use-case.svg` / `.png` |
| CL-01 | Class | Domain class model | `source/CL-01-class.mmd` | `export/CL-01-class.svg` / `.png` |
| ERD-01 | Entity relationship | Physical data model, 22 tables | `source/ERD-01-erd.mmd` | `export/ERD-01-erd.svg` / `.png` |
| SQ-01 | Sequence | Registration and OTP verification | `source/SQ-01-registration-otp.mmd` | `export/SQ-01-registration-otp.svg` / `.png` |
| SQ-02 | Sequence | Login, session and role-based access | `source/SQ-02-login-rbac.mmd` | `export/SQ-02-login-rbac.svg` / `.png` |
| SQ-03 | Sequence | Enrollment gate and staff decision | `source/SQ-03-enrollment-verification.mmd` | `export/SQ-03-enrollment-verification.svg` / `.png` |
| SQ-04 | Sequence | Application start, upload and submission | `source/SQ-04-application-submit.mmd` | `export/SQ-04-application-submit.svg` / `.png` |
| SQ-05 | Sequence | Review, document verification and approval | `source/SQ-05-review-approval.mmd` | `export/SQ-05-review-approval.svg` / `.png` |
| SQ-06 | Sequence | Eligibility evaluation and matching | `source/SQ-06-eligibility-recommendations.mmd` | `export/SQ-06-eligibility-recommendations.svg` / `.png` |
| SQ-07 | Sequence | Slot generation and appointment booking | `source/SQ-07-guidance-booking.mmd` | `export/SQ-07-guidance-booking.svg` / `.png` |
| SQ-08 | Sequence | Service request submission and processing | `source/SQ-08-service-request.mmd` | `export/SQ-08-service-request.svg` / `.png` |
| SQ-09 | Sequence | Notification dispatch and reminders | `source/SQ-09-notification-dispatch.mmd` | `export/SQ-09-notification-dispatch.svg` / `.png` |
| SQ-10 | Sequence | Analytics query and CSV export | `source/SQ-10-analytics-export.mmd` | `export/SQ-10-analytics-export.svg` / `.png` |
| AD-01 | Activity | Student onboarding | `source/AD-01-student-onboarding.mmd` | `export/AD-01-student-onboarding.svg` / `.png` |
| AD-02 | Activity | Application lifecycle | `source/AD-02-application-lifecycle.mmd` | `export/AD-02-application-lifecycle.svg` / `.png` |
| AD-03 | Activity | Document verification sub-process | `source/AD-03-document-verification.mmd` | `export/AD-03-document-verification.svg` / `.png` |
| AD-04 | Activity | Guidance appointment lifecycle | `source/AD-04-guidance-lifecycle.mmd` | `export/AD-04-guidance-lifecycle.svg` / `.png` |
| AD-05 | Activity | Service request lifecycle | `source/AD-05-service-request-lifecycle.mmd` | `export/AD-05-service-request-lifecycle.svg` / `.png` |
| AD-06 | Activity | Scholarship authoring and publication | `source/AD-06-scholarship-authoring.mmd` | `export/AD-06-scholarship-authoring.svg` / `.png` |
| SM-01 | State machine | Scholarship application | `source/SM-01-application.mmd` | `export/SM-01-application.svg` / `.png` |
| SM-02 | State machine | Student service request | `source/SM-02-service-request.mmd` | `export/SM-02-service-request.svg` / `.png` |
| SM-03 | State machine | Guidance appointment | `source/SM-03-appointment.mmd` | `export/SM-03-appointment.svg` / `.png` |
| SM-04 | State machine | Scholarship lifecycle | `source/SM-04-scholarship.mmd` | `export/SM-04-scholarship.svg` / `.png` |
| SM-05 | State machine | Enrollment verification | `source/SM-05-enrollment.mmd` | `export/SM-05-enrollment.svg` / `.png` |
| ARCH-01 | Architecture | Layered system architecture | `source/ARCH-01-layered.mmd` | `export/ARCH-01-layered.svg` / `.png` |
| ARCH-02 | Architecture | Deployment architecture | `source/ARCH-02-deployment.mmd` | `export/ARCH-02-deployment.svg` / `.png` |

---

## 3. Actors

The role model contains exactly three roles. A fourth actor, the scheduler and queue worker, is non-human and appears only where a scheduled or asynchronous behaviour is modelled.

| ID | Actor | Kind | Implementation | Primary diagrams |
| --- | --- | --- | --- | --- |
| ACT-01 | Student | Human | `roles.name = student` | UC-01, SQ-01, SQ-04, SQ-06, SQ-07, SQ-08, AD-01, AD-02, AD-04, AD-05 |
| ACT-02 | Counselor | Human | `roles.name = counselor` | UC-01, SQ-03, SQ-07, AD-04, SM-03 |
| ACT-03 | Administrator | Human | `roles.name = admin` | UC-01, SQ-02, SQ-03, SQ-05, SQ-10, AD-02, AD-03, AD-06 |
| ACT-04 | Scheduler / Queue Worker | Non-human | `guidance:send-reminders`, `SendNotificationEmail` job | UC-01, SQ-09, ARCH-01, ARCH-02 |

### 3.1 Requested actors that do not exist

The project brief named four actors: Student, Adviser, OSAS Staff, and Super Admin. Only Student exists as a role. This diagram set deliberately does **not** invent the others.

| Requested actor | Present in code | What actually exists | How it is represented in this set |
| --- | --- | --- | --- |
| Student | Yes | `student` role | Drawn as ACT-01 |
| Adviser | No | No adviser role, gate, route or endorsement transition | Omitted; recorded as open decision CF-01 in the SRS |
| OSAS Staff | No, as a role | The label appears as descriptive prose only; enrollment verification is performed by Administrator **and** Counselor | Represented as the shared use cases UC-09 and UC-28, performed by ACT-02 and ACT-03 |
| Super Admin | No | `admin` is a single flat role with no hierarchy or impersonation | Omitted; recorded as open decision CF-03 in the SRS |

> Drawing Adviser, OSAS Staff or Super Admin as actors would misrepresent the system, because no account can hold those roles. The permission matrix in SRS §3.2 and the use case catalogue below are the authoritative statement of who can do what.

<!-- pagebreak -->

## 4. Use case catalogue

| ID | Use case | Actor(s) | Module | Status | Requirements |
| --- | --- | --- | --- | --- | --- |
| UC-01 | Register account | Student | M01 | Implemented | FR-AUTH-04, FR-PROF-01 |
| UC-02 | Verify email with OTP | Student, Counselor, Administrator | M01 | Implemented | FR-AUTH-05, FR-AUTH-06 |
| UC-03 | Log in / log out | Student, Counselor, Administrator | M01 | Implemented | FR-AUTH-01, FR-AUTH-02, FR-AUTH-03, FR-AUTH-08 |
| UC-04 | Reset own password | All users | M01 | Proposed | FR-AUTH-10 |
| UC-05 | Manage user accounts | Administrator | M03 | Implemented | FR-PROF-06, FR-PROF-07, FR-PROF-08 |
| UC-06 | Maintain own profile | Student | M03 | Implemented | FR-PROF-02, FR-PROF-03 |
| UC-07 | Maintain student profiles | Administrator | M03 | Implemented | FR-PROF-04, FR-PROF-05, FR-PASS-04 |
| UC-08 | Protect profile with passcode | Student | M04 | Implemented | FR-PASS-01, FR-PASS-02, FR-PASS-03 |
| UC-09 | Review enrollment verification | Administrator, Counselor | M05 | Partially Implemented | FR-ENR-01 … FR-ENR-06 |
| UC-10 | Manage scholarships and requirements | Administrator | M06 | Implemented | FR-SCH-01 … FR-SCH-05 |
| UC-11 | Announce scholarship | Administrator | M06 | Implemented | FR-SCH-08 |
| UC-12 | Browse and search scholarships | Student | M06 | Implemented | FR-SCH-06, FR-SCH-07, FR-SCH-09 |
| UC-13 | Preview eligibility | Student | M07 | Implemented | FR-ELIG-01 … FR-ELIG-04 |
| UC-14 | View recommendations | Student | M08 | Implemented | FR-MATCH-01, FR-MATCH-04, FR-MATCH-05, FR-MATCH-06 |
| UC-15 | Configure matching weights | Administrator | M08 | Implemented | FR-MATCH-02, FR-MATCH-03 |
| UC-16 | Start application | Student | M09 | Implemented | FR-APP-01, FR-APP-02, FR-APP-03 |
| UC-17 | Upload or remove documents | Student | M10 | Implemented | FR-APP-05, FR-DOC-01, FR-DOC-02 |
| UC-18 | Submit application | Student | M09 | Implemented | FR-APP-04 |
| UC-19 | Review application | Administrator | M09 | Implemented | FR-APP-06, FR-APP-07, FR-APP-08, FR-APP-09 |
| UC-20 | Verify or reject documents | Administrator | M10 | Implemented | FR-DOC-03, FR-DOC-04 |
| UC-21 | Book guidance appointment | Student | M11 | Implemented | FR-GUID-05 … FR-GUID-08, FR-GUID-10 |
| UC-22 | Publish counselor availability | Counselor | M11 | Implemented | FR-GUID-01, FR-GUID-02 |
| UC-23 | Confirm, reject or complete appointment | Counselor | M11 | Implemented | FR-GUID-09 |
| UC-24 | Configure guidance settings | Administrator | M11 | Implemented | FR-GUID-03, FR-GUID-04 |
| UC-25 | Oversee all appointments | Administrator | M11 | Implemented | FR-GUID-11 |
| UC-26 | Submit service request | Student | M12 | Implemented | FR-SVC-01, FR-SVC-03, FR-SVC-04 |
| UC-27 | Track own service requests | Student | M12 | Implemented | FR-SVC-05, FR-SVC-08, FR-SVC-09 |
| UC-28 | Process service request | Administrator | M12 | Partially Implemented | FR-SVC-06, FR-SVC-07, FR-SVC-10 |
| UC-29 | Manage service types | Administrator | M12 | Implemented | FR-SVC-02 |
| UC-30 | Receive notifications | Student, Counselor, Administrator | M13 | Implemented | FR-NOTIF-01 … FR-NOTIF-06 |
| UC-31 | View analytics and export CSV | Administrator | M14 | Implemented | FR-ANLYT-01 … FR-ANLYT-06 |
| UC-32 | View audit log | Administrator | M15 | Implemented | FR-AUDIT-01 … FR-AUDIT-05 |
| UC-33 | Search records | Student, Counselor, Administrator | M16 | Implemented | FR-SEARCH-01 … FR-SEARCH-03 |
| UC-34 | Send appointment reminders | Scheduler | M11 | Implemented | FR-GUID-12 |

Two use cases are marked Partially Implemented for specific, documented reasons. UC-09 has no screen that creates the verification record it reviews (GAP-01). UC-28 is exercised only by administrators, because no counselor route exists for service requests (GAP-03). UC-04 is Proposed: only administrator-initiated password reset exists (GAP-05).

<!-- pagebreak -->

## 5. Structural diagrams

### 5.1 UC-01 — Use case diagram

Shows all 34 verified use cases, the four actors, and the include/extend relationships that the implementation actually exhibits. Note that UC-04 is drawn as a dashed, not-implemented extension of login rather than as a normal use case.

![Figure 5.1. UC-01 Use case diagram — actors, 34 use cases, and include/extend relationships](export/UC-01-use-case.png)

### 5.2 CL-01 — Class diagram

The domain class model. Twenty-two Eloquent models with their attributes, status constants, relationship methods and business helper methods. Framework base classes are omitted. Method signatures reflect the delivered code, including `hasVerifiedEnrollmentForCurrentTerm()`, `checkApprovalReadiness()` and `isBlocking()`.

![Figure 5.2. CL-01 Class diagram — 22 domain classes and their relationships](export/CL-01-class.png)

### 5.3 ERD-01 — Entity relationship diagram

The physical data model. Twenty-two business tables with primary keys, foreign keys, unique constraints and cardinalities taken directly from the migrations. The seven Laravel framework tables (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`) are excluded because they carry no business meaning; they are discussed in SRS §5.4.

![Figure 5.3. ERD-01 Entity relationship diagram — 22 business tables](export/ERD-01-erd.png)

<!-- pagebreak -->

## 6. Behavioural diagrams

### 6.1 Sequence diagrams

Sequence diagrams trace the actual object collaboration for the ten workflows that carry the most business risk. Participants are named after the real classes involved, so each diagram can be read against the source without translation.

#### SQ-01 — Registration and OTP verification

Covers account creation, one-time passcode issue, verification, and the resend cooldown.

![Figure 6.1. SQ-01 Registration and OTP verification](export/SQ-01-registration-otp.png)

#### SQ-02 — Login and role-based access control

Covers credential checking, inactive-account rejection, session regeneration, audit logging, and the role middleware decision.

![Figure 6.2. SQ-02 Login and role-based access control](export/SQ-02-login-rbac.png)

#### SQ-03 — Enrollment verification gate and staff decision

Covers the `enrollment.verified` middleware, including the legacy pass-through when a student has no records at all, and the staff decision transaction.

![Figure 6.3. SQ-03 Enrollment verification gate and staff decision](export/SQ-03-enrollment-verification.png)

#### SQ-04 — Application start, upload and submission

Covers application number generation, draft creation, validated uploads, draft-only deletion, and the submission transaction.

![Figure 6.4. SQ-04 Application start, upload and submission](export/SQ-04-application-submit.png)

#### SQ-05 — Review, document verification and approval

Covers administrative review, document verification, and the approval guard that refuses approval while any required document is unverified.

![Figure 6.5. SQ-05 Review, document verification and approval](export/SQ-05-review-approval.png)

#### SQ-06 — Eligibility evaluation and matching

Covers the five-criterion evaluation, the explanation contract, the five scoring factors, weight renormalisation, and the stable ranking order.

![Figure 6.6. SQ-06 Eligibility evaluation and matching](export/SQ-06-eligibility-recommendations.png)

#### SQ-07 — Slot generation and appointment booking

Covers availability, office hours, booking notice, the booking horizon, and the counselor and student conflict checks performed inside a transaction.

![Figure 6.7. SQ-07 Slot generation and appointment booking](export/SQ-07-guidance-booking.png)

#### SQ-08 — Service request submission and processing

Covers number generation under a row lock, attachment handling, assignment, and the service request transition table.

![Figure 6.8. SQ-08 Service request submission and processing](export/SQ-08-service-request.png)

#### SQ-09 — Notification dispatch and reminders

Covers duplicate suppression, queued email with an `emailed_at` stamp, and the reminder command's stamp-before-send idempotency.

![Figure 6.9. SQ-09 Notification dispatch and reminders](export/SQ-09-notification-dispatch.png)

#### SQ-10 — Analytics query and CSV export

Covers filter validation, the three analytical views, and the export that reuses the same filtered dataset.

![Figure 6.10. SQ-10 Analytics query and CSV export](export/SQ-10-analytics-export.png)

<!-- pagebreak -->

### 6.2 Activity diagrams

Activity diagrams show the decision structure of each workflow, including the branches that produce a refusal. Each decision node corresponds to a real validation or guard in the code.

#### AD-01 — Student onboarding

![Figure 6.11. AD-01 Student onboarding — registration, OTP, profile completion, enrollment gate](export/AD-01-student-onboarding.png)

#### AD-02 — Application lifecycle

![Figure 6.12. AD-02 Application lifecycle — student and administrator lanes](export/AD-02-application-lifecycle.png)

#### AD-03 — Document verification

![Figure 6.13. AD-03 Document verification sub-process](export/AD-03-document-verification.png)

#### AD-04 — Guidance appointment lifecycle

![Figure 6.14. AD-04 Guidance appointment lifecycle](export/AD-04-guidance-lifecycle.png)

#### AD-05 — Service request lifecycle

![Figure 6.15. AD-05 Service request lifecycle](export/AD-05-service-request-lifecycle.png)

#### AD-06 — Scholarship authoring and publication

![Figure 6.16. AD-06 Scholarship authoring and publication](export/AD-06-scholarship-authoring.png)

<!-- pagebreak -->

### 6.3 State machine diagrams

The five state machines below reproduce the transition tables and status vocabularies exactly as implemented. A transition not drawn in these diagrams is refused at runtime.

#### SM-01 — Scholarship application

Terminal states are `approved` and `rejected`. A `draft` record becomes `submitted` only through the student submission action.

![Figure 6.17. SM-01 Scholarship application state machine](export/SM-01-application.png)

#### SM-02 — Student service request

Terminal states are `completed` and `cancelled`. Remarks are mandatory when entering `requires_information` or `completed`.

![Figure 6.18. SM-02 Student service request state machine](export/SM-02-service-request.png)

#### SM-03 — Guidance appointment

Only `requested` and `confirmed` block time. Actor permission per transition is shown in brackets.

![Figure 6.19. SM-03 Guidance appointment state machine](export/SM-03-appointment.png)

#### SM-04 — Scholarship lifecycle

Student visibility requires `open` **and** a current date inside the application window.

![Figure 6.20. SM-04 Scholarship lifecycle](export/SM-04-scholarship.png)

#### SM-05 — Enrollment verification

`pending` is reached only by an existing record; no route creates one (GAP-01).

![Figure 6.21. SM-05 Enrollment verification state machine](export/SM-05-enrollment.png)

<!-- pagebreak -->

## 7. Architecture diagrams

### 7.1 ARCH-01 — Layered system architecture

The request lifecycle as implemented: front controller, middleware chain, route groups, request boundary (form requests, gates, policies), controllers, service layer, Eloquent models, and the data tier.

![Figure 7.1. ARCH-01 Layered system architecture](export/ARCH-01-layered.png)

### 7.2 ARCH-02 — Deployment architecture

The deployment topology: campus clients over HTTPS, a web server whose document root is `public`, PHP with the Laravel application, private document storage, MariaDB, a queue worker, the OS scheduler, and the SMTP relay.

![Figure 7.2. ARCH-02 Deployment architecture](export/ARCH-02-deployment.png)

---

## 8. Consistency with the specification

Each diagram is traceable to the SRS sections it supports. The requirement families listed below are the ones whose identifiers appear against the corresponding use case in the catalogue.

| Diagram | SRS sections | Requirement families |
| --- | --- | --- |
| UC-01 | §3, §4 | All FR groups |
| CL-01 | §5.3, §15.2 | FR-RBAC, FR-APP, FR-DOC, FR-GUID, FR-SVC |
| ERD-01 | §5, §10 | DR-01 … DR-20, BR-04, BR-05, BR-09 |
| SQ-01 | §4.1, §6.4 | FR-AUTH, FR-PROF |
| SQ-02 | §3.3, §4.1, §4.2 | FR-AUTH, FR-RBAC, FR-AUDIT |
| SQ-03 | §4.5, §10 | FR-ENR, BR-22, BR-23, BR-28 |
| SQ-04 | §4.9, §4.10, §16.5 | FR-APP, FR-DOC, BR-10, BR-24 |
| SQ-05 | §4.9, §4.10, §10 | FR-APP, FR-DOC, BR-11, BR-12 |
| SQ-06 | §4.7, §4.8, §16.1, §16.2, §16.3 | FR-ELIG, FR-MATCH, BR-21 |
| SQ-07 | §4.11, §16.4 | FR-GUID, BR-15 … BR-19 |
| SQ-08 | §4.12 | FR-SVC, BR-13, BR-14 |
| SQ-09 | §4.13, §16.6 | FR-NOTIF, FR-GUID-12, BR-29, BR-30 |
| SQ-10 | §4.14, §7.1 | FR-ANLYT |
| AD-01 | §4.1, §4.5 | FR-AUTH, FR-ENR, FR-PASS |
| AD-02 | §4.6, §4.9 | FR-SCH, FR-APP |
| AD-03 | §4.10 | FR-DOC, BR-11 |
| AD-04 | §4.11 | FR-GUID |
| AD-05 | §4.12 | FR-SVC |
| AD-06 | §4.6, §10 | FR-SCH, FR-MATCH-02 |
| SM-01 | §4.9, §10 | FR-APP, BR-09, BR-11, BR-12 |
| SM-02 | §4.12, §10 | FR-SVC, BR-13, BR-14 |
| SM-03 | §4.11, §10 | FR-GUID, BR-15 … BR-19 |
| SM-04 | §4.6, §10 | FR-SCH, BR-20 |
| SM-05 | §4.5, §10 | FR-ENR, BR-22, BR-23, BR-28 |
| ARCH-01 | §2.1, §9, §15.1, §15.2 | NFR-PERF, NFR-SEC, NFR-MAINT |
| ARCH-02 | §8, §9 | NFR-COMPAT, NFR-PORT, NFR-REL |

### 8.1 Editorial decisions recorded

1. **The use case diagram is a flowchart rendering.** Mermaid has no native use-case diagram type. Actors, use cases and include/extend semantics are modelled correctly, but the visual grammar is flowchart-based. This is stated rather than concealed.
2. **Framework tables are excluded from ERD-01.** They carry no business meaning and would triple the width of the diagram without adding explanatory value.
3. **Class diagram methods are selective.** Private helpers and framework overrides are omitted; methods that express business behaviour are retained.
4. **Gaps are annotated on the diagrams and not hidden.** SM-05 states that no route creates a `pending` record; UC-04 is drawn as not implemented; UC-09 and UC-28 are labelled Partially Implemented in the catalogue.
5. **The `withdrawn` status is absent from SM-01.** The badge map recognises the label, but no transition produces it (GAP-10). Drawing it would imply a capability that does not exist.

---

## 9. Reproduction

To regenerate every diagram from source:

1. Set `PUPPETEER_EXECUTABLE_PATH` to an installed Chrome executable.
2. Run `documentation/_tools/render_uml.ps1`.

The script renders each `.mmd` file to both SVG and a 2× PNG in `export/`, and reports any diagram whose source fails to parse. A parse failure leaves the previous export untouched, so a failed run is visible as a stale timestamp rather than a silent corruption.
