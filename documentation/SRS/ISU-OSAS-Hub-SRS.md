# Software Requirements Specification

**ISU OSAS Hub — Integrated Student Affairs and Services Management System**

Isabela State University · Office of Student Affairs and Services

| Item | Details |
| --- | --- |
| Document title | Software Requirements Specification — ISU OSAS Hub |
| System name | ISU OSAS Hub |
| System type | Locally hosted, web-based student affairs and services information system |
| Primary users | Student, Counselor, Administrator |
| Institution | Isabela State University (ISU), Office of Student Affairs and Services (OSAS) |
| Core modules | Scholarship Management, Eligibility and Matching, Guidance and Counseling, Student Services, Analytics and Reporting, Audit |
| Development stack | PHP 8.2, Laravel 12, MariaDB, Blade, HTML5, CSS3, JavaScript |
| Development environment | XAMPP/Apache, Composer, Node.js (asset tooling), Visual Studio Code |
| Document status | Final — verified against source code |
| Version | 1.0 |
| Preparation date | 15 November 2026 |
| Basis of preparation | Read-only audit of the delivered Laravel application |

> This specification documents only behaviour that is present in the delivered
> source code. Where a capability is absent, incomplete, or requested but not
> implemented, it is labelled explicitly rather than described as existing.

<!-- pagebreak -->

## Contents

<!-- toc -->

<!-- pagebreak -->


## 1. Introduction

### 1.1 Purpose

This Software Requirements Specification defines the functional, data, interface, security, performance and quality requirements of the ISU OSAS Hub, a web-based information system that supports the scholarship, guidance and student-service operations of the Office of Student Affairs and Services of Isabela State University.

The document is written for four audiences. The project team uses it as the authoritative description of the delivered system. The technical adviser and panel use it to verify completeness and traceability. Future maintainers use it to understand why the system behaves as it does. Implementers of later phases use it to see which capabilities are still outstanding.

Every functional requirement in this document carries a unique identifier and a status of Implemented, Partially Implemented, or Proposed. Every requirement is traceable to a module, a UML artefact, a wireframe and a user-interface mockup through the Requirements Traceability Matrix that accompanies this specification.

### 1.2 Problem Context

Before the system, OSAS transactions were handled through paper forms, shared spreadsheets and office walk-ins. This produced four recurring problems. First, scholarship information reached students late and unevenly, because announcements depended on bulletin boards and informal channels. Second, eligibility screening was manual, inconsistent between staff members, and impossible to audit after the fact. Third, application documents were stored in physical folders, so verifying a single applicant required locating a folder and remembering which documents had already been checked. Fourth, guidance appointments and student-service requests were scheduled verbally, which caused double-booking and left students without a reliable record of what had been requested or promised.

The ISU OSAS Hub addresses each of these problems with a single authenticated portal. Scholarship publication, screening and document verification become explicit, recorded steps. Guidance appointments are scheduled against published counselor availability with conflict detection. Student-service requests acquire a traceable number, an assigned handler and a status history. Every privileged action leaves an audit record.

### 1.3 Objectives

| ID | Objective | Measure |
| --- | --- | --- |
| OBJ-01 | Centralise scholarship information in one authoritative catalogue | All open scholarships discoverable from a single student page |
| OBJ-02 | Make eligibility screening deterministic and explainable | Every criterion returns a documented outcome with a stated reason |
| OBJ-03 | Replace paper document handling with controlled digital submission | Required documents uploaded, verified, and downloadable only by authorised users |
| OBJ-04 | Remove double-booking from guidance scheduling | Slot generation and conflict checks reject overlapping appointments |
| OBJ-05 | Give students visibility of the status of every request | Status history visible to the student for applications, appointments and service requests |
| OBJ-06 | Provide management with operational evidence | Analytics dashboards and CSV export over scholarships, guidance and services |
| OBJ-07 | Preserve accountability for administrative decisions | Append-only audit log of authentication and business events |

### 1.4 Scope

**In scope.** Account registration and verification; role-based access control; student profiles; profile passcode protection; per-term enrollment verification; scholarship authoring and publication; eligibility evaluation; weighted matching and recommendations; scholarship application submission, review, document verification and approval; guidance availability, booking and appointment workflow; student-service catalogue, request submission and processing; in-application and email notification; analytics and CSV reporting; audit logging; role-scoped search.

**Out of scope.** Payroll or disbursement of scholarship funds; grade management or academic records of record; registrar or student information system integration; SMS messaging; mobile native applications; multi-campus tenancy; biometric identity verification; document optical character recognition.

### 1.5 Definitions and Acronyms

| Term | Definition |
| --- | --- |
| OSAS | Office of Student Affairs and Services |
| ISU | Isabela State University |
| SRS | Software Requirements Specification |
| RBAC | Role-Based Access Control |
| FSM | Finite State Machine — the permitted status transitions of a record |
| OTP | One-Time Passcode, a six-digit code sent by email to verify ownership of an address |
| GWA | General Weighted Average, expressed on the Philippine 1.00–5.00 scale where 1.00 is highest |
| Eligibility criterion | One rule (GWA, income, program, year level, enrollment status) evaluated against a scholarship |
| Match score | Weighted composite of five factors used to rank scholarships for a student |
| Document readiness | Proportion of a scholarship's required documents already uploaded by the student |
| Enrollment verification | A staff decision, scoped to one academic year and semester, that a student is enrolled |
| Artifact | A UML diagram, wireframe or mockup that implements part of this specification |

### 1.6 References

| Ref | Document |
| --- | --- |
| R1 | ISU OSAS Hub source code: `app/`, `routes/web.php`, `database/migrations/`, `resources/views/`, `public/css/app.css` |
| R2 | Project reference SRS: *SRS-MACARILAY_ALINDAYU_LUCAS.docx* (structural and formatting reference only) |
| R3 | Laravel 12 documentation — framework behaviour for routing, middleware, policies, queues and validation |
| R4 | WCAG 2.1 — Web Content Accessibility Guidelines, Level AA |
| R5 | ISO/IEC/IEEE 29148:2018 — Requirements engineering |
| R6 | Requirements Traceability Matrix — `documentation/Traceability/` |

### 1.7 Document Conventions

Requirement identifiers follow the pattern `FR-<GROUP>-nn` for functional requirements and `NFR-<GROUP>-nn` for non-functional requirements. Business rules use `BR-nn`, acceptance criteria `AC-nn`, external interface requirements `EIR-nn`, and data requirements `DR-nn`. Status values are used with fixed meanings throughout: **Implemented** means the behaviour exists and is reachable in the delivered code; **Partially Implemented** means some of the behaviour exists but a required entry point, interface or guard is missing; **Proposed** means the capability is absent and is recorded only so that the gap is explicit.

The word *shall* marks a mandatory requirement, *should* marks a desirable one, and *may* marks a permitted option. Text in callout blocks records an observation about the implementation rather than a requirement.


## 2. Scope and System Functions

### 2.1 System Perspective

The ISU OSAS Hub is a self-contained, server-rendered web application. It does not replace the university's student information system and does not consume data from it. Student academic attributes — year level, program, college, GWA, family income and enrollment status — are held in the hub's own student profile and are maintained either by the student, within a restricted set of fields, or by an administrator.

The application follows a layered request lifecycle. A browser request passes through session authentication, then through an active-account check, a role or gate check, and optionally an enrollment-verification check. It then reaches a controller, which validates input through a form request, delegates business rules to a service class, persists through an Eloquent model, and renders a Blade view. Document files are written to private storage and served only through authorisation-checked download routes. Notifications are written to the database for in-application display and additionally queued for email delivery.

### 2.2 Major System Functions

| ID | Module | Description | Status |
| --- | --- | --- | --- |
| M01 | Authentication and Account Lifecycle | Registration, login, logout, OTP email verification, throttling, inactive-account rejection | Implemented |
| M02 | Access Control | Role middleware, panel gates, policy checks on individual records | Implemented |
| M03 | Student Profiles and User Management | Profile creation, self-service field-restricted editing, administrative maintenance, activation | Implemented |
| M04 | Profile Security | Student-set passcode, session lock and unlock, administrative recovery | Implemented |
| M05 | Enrollment Verification | Per-term verification records, staff decisions, enrollment gating of student services | Partially Implemented |
| M06 | Scholarship Management | Scholarship authoring, lifecycle, requirements, publication and announcement | Implemented |
| M07 | Eligibility Evaluation | Five-criterion rule engine with per-criterion explanation | Implemented |
| M08 | Matching and Recommendations | Weighted scoring, configurable weights, recommendation categories | Implemented |
| M09 | Application Workflow | Draft creation, submission, review, state machine, approval guard, history | Implemented |
| M10 | Document Management | Validated upload, deletion while draft, verification, authorised download | Implemented |
| M11 | Guidance and Counseling | Availability, slot generation, booking, confirmation, completion, reminders | Implemented |
| M12 | Student Services | Service catalogue, request submission, assignment, processing, status history | Partially Implemented |
| M13 | Notifications | In-application notification centre, duplicate suppression, queued email | Implemented |
| M14 | Analytics and Reporting | Operational metrics, three analytical views, period filtering, CSV export | Implemented |
| M15 | Audit Logging | Authentication and business event capture, search, detail view, read-only policy | Implemented |
| M16 | Search and Navigation | Role-scoped typeahead, scholarship search service, shared application shell | Implemented |

### 2.3 Users of the System

The system recognises three human roles and one non-human actor. A student registers an account and uses the portal to find scholarships, apply, book guidance appointments and raise service requests. A counselor publishes availability, decides guidance appointments, and shares responsibility for enrollment verification with administrators. An administrator operates the scholarship catalogue, reviews applications, verifies documents, configures guidance and service settings, monitors analytics and inspects the audit log. A scheduled command and a queue worker deliver appointment reminders and notification email.

### 2.4 Operating Environment

The system runs as a Laravel application on a web server with PHP 8.2 or later, using a MariaDB or MySQL database. It is accessed through a standards-compliant browser over HTTP or HTTPS. Detailed environment requirements are given in Section 9.

### 2.5 Assumptions and Dependencies

| ID | Assumption or dependency |
| --- | --- |
| AS-01 | An SMTP account capable of delivering outbound mail exists; without it, OTP verification and notification email cannot reach users. |
| AS-02 | An OSAS administrator maintains the current academic year and semester in configuration at the start of every term. |
| AS-03 | Counselors publish availability; without published availability no bookable slots are generated. |
| AS-04 | Administrators create appointment types before students can book; a fresh installation without seed data has none. |
| AS-05 | Students self-report GWA and family income truthfully, since the system does not integrate with academic records. |
| AS-06 | The server clock is synchronised, because booking notice windows, deadlines and reminders all depend on it. |
| AS-07 | A queue worker is running, otherwise notification email accumulates in the jobs table. |
| AS-08 | The enrollment-verification population step is performed by an operator or a future interface, because no creation route exists. |

### 2.6 Out of Scope

Functionality that is deliberately excluded from this version is listed in Section 1.4. In addition, the following are absent from the delivered code and must not be assumed by any reader: an adviser actor, a distinct OSAS-staff role, a super-administrator role, self-service password reset, rescheduling of confirmed appointments, counselor-authored session notes, and counselor-side processing of student-service requests.


## 3. User Roles and Privileges

### 3.1 Role Catalogue

The system stores roles in a `roles` table and assigns exactly one role to each user account. Three roles are seeded: **student**, **counselor**, and **admin**. No other role exists in the schema, the seeders, the route definitions, the gates, or the interface.

| Role key | Display label | Purpose in the system | How accounts are created |
| --- | --- | --- | --- |
| `student` | Student | Discovers and applies for scholarships, books guidance appointments, raises service requests, maintains own profile | Self-registration, or administrator creation |
| `counselor` | Counselor | Publishes availability, decides guidance appointments, reviews enrollment verification records | Administrator creation only |
| `admin` | Administrator | Operates the entire institution-side workflow and configuration | Administrator creation, and the seeded first administrator |

### 3.2 Permission Matrix

A filled circle indicates that the role can perform the action subject to the stated condition. A dash indicates that no route, gate or interface exists for that role.

| Capability | Student | Counselor | Administrator |
| --- | --- | --- | --- |
| Register an account | Yes | — | — |
| Log in, log out, remember session | Yes | Yes | Yes |
| Verify email by OTP | Yes | Yes | Yes |
| Reset own password without signing in | — | — | — |
| View own profile | Yes | — | Yes (any student) |
| Edit own profile | Yes, four fields only | — | Yes (any student) |
| Set, change, remove profile passcode | Yes | — | Reset for any student |
| Unlock a locked profile | Yes | — | Yes |
| Browse and search open scholarships | Yes | — | Yes (management view) |
| View eligibility preview | Yes | — | — |
| View recommendations and match scores | Yes | — | Configure weights only |
| Create, edit, close, archive scholarships | — | — | Yes |
| Manage scholarship document requirements | — | — | Yes |
| Announce a scholarship to eligible students | — | — | Yes |
| Configure matching weights | — | — | Yes |
| Start an application and upload documents | Yes, enrollment-gated | — | — |
| Submit an application | Yes, enrollment-gated | — | — |
| Review applications and change status | — | — | Yes |
| Verify or reject uploaded documents | — | — | Yes |
| Review enrollment verification records | Own record only (view) | Yes | Yes |
| Record an enrollment decision | — | Yes | Yes |
| Publish guidance availability | — | Yes (own) | — |
| Book a guidance appointment | Yes, enrollment-gated | — | — |
| Cancel a guidance appointment | Yes (own) | — | — |
| Confirm, reject, complete an appointment | — | Yes (own appointments) | View only |
| Manage appointment types and scheduling settings | — | — | Yes |
| View all appointments | — | Own only | Yes |
| Browse service catalogue and submit request | Yes, enrollment-gated | — | — |
| Track and cancel own service requests | Yes | — | — |
| Assign and process service requests | — | — | Yes |
| Manage service types and their requirements | — | — | Yes |
| Receive notifications | Yes | Yes | Yes |
| View analytics and export CSV | — | — | Yes |
| View audit log | — | — | Yes |
| Create and deactivate user accounts | — | — | Yes |
| Reset another user's password | — | — | Yes |

### 3.3 Role-to-Implementation Mapping

| Role | Enforcement points |
| --- | --- |
| Student | `role:student` route group; `verified.email` middleware; `enrollment.verified` middleware on submission routes; `access-student-panel` gate; student-scoped search endpoints |
| Counselor | `role:counselor` route group; `access-counselor-panel` gate; `manage-enrollment-verifications` gate; ownership checks in `GuidanceAppointmentPolicy` |
| Administrator | `role:admin` route group; `manage-users` and `access-admin-panel` gates; `active` middleware on analytics and notifications; `NotificationPolicy` grant |

Authorization is enforced server-side in all cases. The interface hides actions a role cannot perform, but navigation visibility is never the control — every restricted route independently re-checks the role or gate.

### 3.4 Requested Roles That Are Not Implemented

The project brief for this documentation named four actors: Student, Adviser, OSAS Staff, and Super Admin. Only Student survives verification. The remaining three are recorded here so that the discrepancy is explicit and cannot be mistaken for a delivered feature.

| Requested actor | Present in code? | Closest implemented construct | Recommendation |
| --- | --- | --- | --- |
| Student | Yes | `student` role | Document as implemented |
| Adviser | No | None. No endorsement step exists in the application workflow | Either add an adviser role and an endorsement transition, or remove the actor from the design |
| OSAS Staff | No, as a role | "OSAS Staff" appears in route comments, a seeder description and interface copy as a label for the combined enrollment-verification duties of Administrator and Counselor | Document as a function performed by Administrator and Counselor, not as a role |
| Super Admin | No | `admin` is a single, flat administrative role with no hierarchy and no impersonation | Document as Administrator, or add a distinct super-administrator if separation of duties is required |

> The phrase "OSAS Staff" is used in the source only as descriptive prose. Treating it as an actor in a use case diagram would misrepresent the system, because no account can hold that role.

### 3.5 Authorization Mechanisms

Four mechanisms cooperate. Route middleware enforces the role for an entire route group and rejects mismatches with HTTP 403. Gates express named capabilities such as `manage-users` and `manage-enrollment-verifications`, and additionally require the account to be active. Policies guard individual records by comparing the record's owner with the signed-in user, which prevents one student from opening another student's application document or appointment. Middleware named `enrollment.verified` completes the chain by blocking enrollment-gated student actions until verification for the current term exists.

### 3.6 Account Lifecycle

A student may register, but the account is unusable for student functions until the email address is verified by one-time passcode. Administrators and counselors are created by an administrator and are treated as already verified. An administrator can deactivate an account at any time; a deactivated account cannot authenticate, cannot pass the `active` middleware, and receives no notifications. Accounts are never deleted through the interface, which preserves the referential integrity of historical applications, appointments and audit entries.


## 4. Functional Requirements

This section states every functional requirement of the delivered system. Requirements are grouped by module and identified by the prefix of their identifier. Each row records the responsible actor, the priority assigned during verification, and the implementation status.

Priority is interpreted as follows. **High** requirements are essential to the operational purpose of the module. **Medium** requirements improve efficiency or governance. **Low** requirements are desirable refinements.

**Functional requirement summary.** 113 requirements in total.

| Measure | Value |
|---|---|
| Total | 113 |
| By status | Implemented: 106, Partial: 1, Proposed: 6 |
| By priority | High: 78, Low: 4, Medium: 31 |

### 4.1 Authentication and Account Lifecycle (FR-AUTH)

Authentication is implemented without a starter kit. The application provides its own login, registration and verification flows, and issues a six-digit one-time passcode by email rather than relying on a signed-link verification. Login and registration are throttled, failed attempts are audited without recording the attempted password, and deactivated accounts are rejected at authentication and again by middleware on protected pages.

_No requirements recorded under AUTH._

### 4.2 Access Control (FR-RBAC)

Authorization is layered. Route groups carry the role middleware, named gates express capabilities, and policies decide on individual records. The result is that a student cannot reach an administrator page by typing its address, and cannot open another student's document by guessing an identifier.

_No requirements recorded under RBAC._

### 4.3 Student Profiles and User Management (FR-PROF)

A student profile is a separate record from the user account, joined one-to-one. Students may correct only the four fields over which they hold authoritative knowledge — contact number, address, GWA and family income. Academic identity fields and enrollment status are administrator-controlled, which prevents a student from inflating eligibility by rewriting their year level or program.

_No requirements recorded under PROF._

### 4.4 Profile Security (FR-PASS)

The profile passcode is a student-controlled privacy measure for shared or public computers. The passcode is hashed with the same one-way algorithm used for account passwords, is never returned to the browser, and can be reset by an administrator when a student forgets it.

_No requirements recorded under PASS._

### 4.5 Enrollment Verification (FR-ENR)

Enrollment verification exists so that a scholarship application or appointment booking is only possible for a student who is actually enrolled in the current term. Verification records are scoped to an academic year and semester, and the current term is read from configuration.

> Observed gap. The application can list, display and decide enrollment-verification records, but no route or interface creates them. Until a record exists for a student, the enrollment gate deliberately permits access, because the check treats a completely unverified student as a legacy account. The module is therefore classified as Partially Implemented, and the intake requirement is recorded as Proposed.

_No requirements recorded under ENR._

### 4.6 Scholarship Management (FR-SCH)

Scholarships carry eligibility thresholds, a set of document requirements and an application window. A scholarship is visible to students only when its status is open and the current date falls inside the window, so an administrator can prepare a scholarship in draft without exposing it.

_No requirements recorded under SCH._

### 4.7 Eligibility Evaluation (FR-ELIG)

Eligibility is evaluated deterministically. For each criterion the engine returns one of met, not met, or not applicable, together with the student's value, the scholarship threshold and a plain-language reason. The overall decision is derived from the criterion set rather than computed separately, which keeps the explanation and the verdict consistent.

_No requirements recorded under ELIG._

### 4.8 Matching and Recommendations (FR-MATCH)

Matching ranks scholarships for a particular student by combining five factors under administrator-controlled weights. Because the weights must total exactly one hundred, a ranking always expresses a genuine trade-off rather than an arbitrary scale.

_No requirements recorded under MATCH._

### 4.9 Application Workflow (FR-APP)

The application lifecycle is governed by a finite state machine. A student starts an application in draft, uploads documents, and submits. An administrator then moves the record through review and document verification to approval or rejection. Approval is guarded: the transition is refused unless every required requirement has a document that has itself been verified.

_No requirements recorded under APP._

### 4.10 Document Management (FR-DOC)

Documents are stored on a private disk. Authorised download is the only way to read a file, and the policy compares the requesting user with the owner of the application. Uploads accept PDF, JPEG and PNG only, up to five megabytes.

_No requirements recorded under DOC._

### 4.11 Guidance and Counseling (FR-GUID)

Guidance scheduling is computed rather than negotiated. Counselor availability, office hours, appointment duration, minimum booking notice and the booking horizon together determine which slots are offered. Conflicts are checked for both the counselor and the student before an appointment is created.

_No requirements recorded under GUID._

### 4.12 Student Services (FR-SVC)

Service requests are numbered, described, and optionally supported by attachments. An administrator assigns a handler and moves the request through processing, possibly requesting further information, to completion or cancellation.

> Observed gap. The workflow service and the policy layer permit a counselor to be the assigned handler, but no counselor route or screen exists for service requests. The module is classified as Partially Implemented on that basis.

_No requirements recorded under SVC._

### 4.13 Notifications (FR-NOTIF)

Notifications are written to the database and simultaneously queued for email. Duplicate suppression prevents the same unread notification from being created twice for the same event, and the email job stamps the record so that a retry cannot send a second message.

_No requirements recorded under NOTIF._

### 4.14 Analytics and Reporting (FR-ANLYT)

Analytics are descriptive. They report what happened in the scholarship, guidance and service workflows over a chosen period, and they are exportable so that the office can include the figures in its own reports.

_No requirements recorded under ANLYT._

### 4.15 Audit Logging (FR-AUDIT)

The audit log records who did what, to which record, when, and from which address. It is exposed read-only, and no route exists to modify or delete an entry.

_No requirements recorded under AUDIT._

### 4.16 Search and Navigation (FR-SEARCH)

Live search returns suggestions only for records the signed-in role is entitled to see. The same normalisation and length bounds are applied to every search surface, which keeps query cost predictable.

_No requirements recorded under SEARCH._


## 5. Data Requirements

### 5.1 Data Model Overview

The persistent model contains twenty-two business tables and seven framework tables. The business model is organised around four aggregates: the account aggregate (role, user, student profile, enrollment verification), the scholarship aggregate (scholarship, requirement, matching weight, application, document, status history), the guidance aggregate (availability, appointment type, scheduling settings, appointment), and the service aggregate (service type, service type requirement, service request, request document, request history). Notifications and audit entries are cross-cutting records attached to a user.

### 5.2 Data Requirements

| ID | Requirement |
| --- | --- |
| DR-01 | Every user account shall hold exactly one foreign key to a role, and roles shall be restricted from deletion while referenced. |
| DR-02 | The user email address shall be unique across all accounts, and the password shall be stored only as a hash. |
| DR-03 | A student profile shall be unique per user account and shall carry a unique student number of at most twenty alphanumeric characters or dashes. |
| DR-04 | A student profile shall store first, middle and last names separately, and shall store year level as an unsigned small integer. |
| DR-05 | GWA shall be stored with two decimal places and constrained at input to the range 1.00 to 5.00. |
| DR-06 | Family income shall be stored with two decimal places and constrained at input to a non-negative value. |
| DR-07 | The profile passcode shall be stored only as a hash and shall be excluded from serialisation. |
| DR-08 | Scholarship eligible programs and eligible year levels shall be stored as structured arrays so that a scholarship can target several of each. |
| DR-09 | Each scholarship requirement shall record whether it is mandatory and its display order. |
| DR-10 | A scholarship application shall be unique per student profile and scholarship for the lifetime of the record. |
| DR-11 | Every application and service request shall carry a unique, human-readable business number in addition to its surrogate key. |
| DR-12 | Each uploaded document shall record original filename, MIME type, byte size, storage path, and verification state, and shall reference the requirement it satisfies. |
| DR-13 | Verification of a document shall record the verifying user and the timestamp, and rejection shall require remarks. |
| DR-14 | Status history records shall be append-only and shall store previous status, new status, actor, remarks and timestamp. |
| DR-15 | An enrollment verification record shall be scoped to one student profile, academic year and semester, and shall record the deciding user and time. |
| DR-16 | A guidance appointment shall reference one student profile, one counselor account, and optionally one appointment type. |
| DR-17 | Counselor availability shall be expressed as day of week plus start and end time, and shall not overlap itself for the same counselor. |
| DR-18 | Notification records shall carry type, title, message, optional related type and identifier, optional metadata, read timestamp and emailed timestamp. |
| DR-19 | Audit entries shall record actor, action, module, record type, record identifier, description, address, user agent and timestamp, and shall reference the actor with a null-on-delete rule so history survives account removal. |
| DR-20 | Every timestamp shall be recorded in server time and rendered in a single consistent human-readable format. |

### 5.3 Business Entities

| Table | Purpose | Key relationships |
| --- | --- | --- |
| `roles` | Role dictionary | One role has many users |
| `users` | Accounts for all roles | Belongs to role; has one student profile; has many notifications, audit entries |
| `student_profiles` | Academic and personal attributes of a student | Belongs to user; has many applications, appointments, service requests, verifications |
| `student_enrollment_verifications` | Per-term enrollment decisions | Belongs to student profile; belongs to verifying user |
| `scholarships` | Scholarship catalogue | Belongs to creating user; has many requirements and applications |
| `scholarship_requirements` | Documents a scholarship demands | Belongs to scholarship; has many application documents |
| `scholarship_matching_weights` | Active scoring weights | Belongs to updating user |
| `scholarship_applications` | A student's application to a scholarship | Belongs to student profile and scholarship; has many documents and status histories |
| `application_documents` | Uploaded proof for a requirement | Belongs to application and requirement; belongs to verifying user |
| `application_status_histories` | Immutable application transition log | Belongs to application; belongs to acting user |
| `counselor_availabilities` | Weekly availability blocks | Belongs to counselor account |
| `guidance_appointment_types` | Bookable appointment categories | Has many appointments |
| `guidance_scheduling_settings` | Single active scheduling policy | Belongs to updating user |
| `guidance_appointments` | Booked sessions | Belongs to student profile, counselor, appointment type, cancelling user |
| `service_types` | Service catalogue entries | Has many requirements and requests |
| `service_type_requirements` | Documents a service requires | Belongs to service type; has many request documents |
| `student_service_requests` | A student's service request | Belongs to student profile, service type, handling user, cancelling user |
| `service_request_documents` | Attachments on a service request | Belongs to request, requirement, uploading user |
| `student_service_request_histories` | Immutable request transition log | Belongs to request; belongs to acting user |
| `notifications` | Per-user notification records | Belongs to user |
| `audit_logs` | Security and business event log | Belongs to user |
| `otp_verifications` | Issued one-time passcodes | Belongs to user |

### 5.4 Framework Tables

The Laravel framework tables `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches` and `failed_jobs` are present. Of these, `sessions` and `jobs` are operationally significant: sessions store authenticated state server-side, and jobs hold queued notification email. The `password_reset_tokens` table is created by the framework but is not used, because no self-service reset flow exists.

### 5.5 File Storage

Uploaded documents are written to a private disk, outside the public web root, under paths that include the owning record. They are never linked directly. A download request passes through a policy that confirms the requester owns the record or is an administrator, and is then streamed by the server.

### 5.6 Data Integrity Rules

| ID | Rule |
| --- | --- |
| DI-01 | Deleting a user cascades to that user's student profile, and therefore to the profile's dependent records, so account deletion is not offered in the interface. |
| DI-02 | Scholarships and student profiles are protected against deletion while applications reference them. |
| DI-03 | Appointment and service-request histories cascade with their parent record. |
| DI-04 | Verification of a document requires an existing requirement reference; documents that satisfy no requirement cannot be counted toward approval. |
| DI-05 | The unique constraint on student profile and scholarship is permanent, so a rejected applicant cannot reapply for the same scholarship without manual intervention. |

### 5.7 Data Retention and Privacy

Personal data is limited to what the workflow needs: identity, contact details, academic attributes and household income, which are the inputs to eligibility. The passcode hash and account password are never displayed. Audit entries record the actors' address and browser identification, which supports investigation but is itself personal data and should be covered by the institution's privacy notice. The system provides no automated purge; retention is an institutional policy decision.


## 6. External Interface Requirements

### 6.1 Interface Requirements

| ID | Interface | Requirement |
| --- | --- | --- |
| EIR-01 | Web browser interface | The system shall be operable through a standards-compliant browser with JavaScript enabled, using server-rendered HTML and a single stylesheet, and shall degrade gracefully where optional scripting is unavailable. |
| EIR-02 | Email transport | The system shall deliver one-time passcodes and notification email through a configured SMTP server, using a queued job for notification mail. |
| EIR-03 | Database | The system shall persist all records in a MariaDB or MySQL database accessed through the framework's database layer, using parameterised queries throughout. |
| EIR-04 | File system | The system shall store uploaded documents on a private local disk and shall require an application route for every read. |
| EIR-05 | CSV export | The system shall stream analytics data as a comma-separated file with a header row, suitable for opening in a spreadsheet application. |
| EIR-06 | Scheduler | The system shall expose a console command that sends appointment reminders and is intended to be invoked by the operating system scheduler. |
| EIR-07 | Queue worker | The system shall process notification email through the database queue, requiring an operator-run worker. |

### 6.2 User Interface Conventions

The interface follows one shell for all authenticated users: a persistent left sidebar of role-appropriate links, a top bar carrying the current page context, search, notification count and account menu, and a main content region. Every page begins with a page header stating the purpose of the screen and offering its primary action. List pages present filters above results, results in a bordered table with a header row, and pagination below. Forms place labels above inputs, show validation messages directly beneath the offending field, and place the primary submission action at the end of the form with a secondary cancel action beside it. Destructive actions use a distinct danger treatment and require confirmation.

### 6.3 Screen Inventory

The system comprises fifty-five distinct application pages, of which fifty-two are role-facing screens and three are authentication screens, together with a set of reusable components. The verification of each screen against its route and its requirement identifiers is recorded in the wireframe index and in the traceability matrix.

### 6.4 Error and Feedback Interface

Invalid input returns the form with field-level messages and the previously entered values. An unauthorised request returns HTTP 403 with an explanatory page. A request for a record that does not exist, or a scholarship that is not open, returns HTTP 404. Workflow violations return the record page with an explanatory message rather than a generic failure. Successful operations return to the relevant page with a confirmation banner.

### 6.5 Accessibility Interface

Pages expose a skip link to the main region, semantic landmarks, labelled navigation, visible keyboard focus, and status regions announced to assistive technology. Animation is suppressed when the operating system reports a reduced-motion preference. Colour is never the sole carrier of meaning: every status is also labelled.


## 7. Non-Functional Requirements

Non-functional requirements constrain how the functional requirements are delivered. They are grouped by quality attribute and each is stated in a form that can be verified by inspection, measurement or test.

**Non-functional requirement summary.** 40 requirements in total.

| Measure | Value |
|---|---|
| Total | 40 |
| By status | Implemented: 40 |
| By priority | High: 20, Low: 2, Medium: 18 |

### 7.1 Performance (NFR-PERF)

Performance targets assume the reference hardware in Section 8, a seeded dataset representative of one academic year, and a single application server. They are expressed as targets rather than measurements; verification is described in Section 14.

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-PERF-01 | Ninety-five percent of authenticated page requests shall complete within two seconds on the reference hardware with the seeded dataset. | System | High | Implemented |
| NFR-PERF-02 | Frequent filters and sorts shall be supported by database indexes on status, dates, owner identifiers, and composite application and appointment keys. | System | Medium | Implemented |
| NFR-PERF-03 | Every list view shall paginate results rather than loading complete tables. | System | High | Implemented |
| NFR-PERF-04 | Email delivery shall be queued so that no user-facing request waits on the mail transport. | System | High | Implemented |

*4 requirement(s) in this group.*

### 7.2 Security (NFR-SEC)

Security is implemented through server-side authorization, hashed credentials, private file storage, request throttling and an audit trail. The system does not implement multi-factor authentication for staff accounts; the one-time passcode is an email-ownership check performed once per account, not a second factor at every login.

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-SEC-01 | All passwords and profile passcodes shall be stored using a one-way hash and never in plaintext. | System | High | Implemented |
| NFR-SEC-02 | Sessions shall use server-side storage, CSRF tokens on state-changing requests, and session regeneration on authentication. | System | High | Implemented |
| NFR-SEC-03 | Every non-public route shall require authentication, and every role-restricted route shall enforce its role or gate server-side. | System | High | Implemented |
| NFR-SEC-04 | Uploaded documents shall not be web-accessible by direct path and shall be served only through policy-checked download routes. | System | High | Implemented |
| NFR-SEC-05 | Passcode hashes and password fields shall be excluded from serialization and from every rendered view. | System | High | Implemented |
| NFR-SEC-06 | File uploads shall be validated by MIME type and size on the server before storage. | System | High | Implemented |
| NFR-SEC-07 | Login, registration, OTP submission, OTP resend, and profile unlock shall be rate limited per client. | System | High | Implemented |
| NFR-SEC-08 | Every privileged state change shall produce an audit entry identifying the actor and the affected record. | System | High | Implemented |

*8 requirement(s) in this group.*

### 7.3 Usability (NFR-USA)

Usability requirements reflect the operational reality of an office counter: staff process records in volume, so consistency and predictability matter more than novelty. Every list behaves the same way, every status looks the same way, and every irreversible action asks first.

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-USA-01 | Every authenticated page shall present the same persistent sidebar and top bar, with role-appropriate entries only. | All users | High | Implemented |
| NFR-USA-02 | Forms shall display field-level validation messages adjacent to the offending input and preserve previously entered values. | All users | High | Implemented |
| NFR-USA-03 | Every list and chart shall display a descriptive empty state instead of a blank region when no data exists. | All users | Medium | Implemented |
| NFR-USA-04 | Any destructive or irreversible action shall require an explicit confirmation step. | All users | Medium | Implemented |
| NFR-USA-05 | Every workflow status shall be rendered with a consistent labeled badge and colour rather than a raw database value. | All users | Medium | Implemented |
| NFR-USA-06 | Report and detail pages shall remain legible when printed, suppressing navigation chrome. | Administrator | Low | Implemented |

*6 requirement(s) in this group.*

### 7.4 Reliability and Integrity (NFR-REL)

The dominant reliability risk in this system is a partially applied workflow change: an application moved to approved while a document remains unverified, or a reminder dispatched twice by an overlapping scheduler. The requirements below address that class of failure.

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-REL-01 | Multi-record operations shall execute inside a database transaction so that a partial workflow change cannot persist. | System | High | Implemented |
| NFR-REL-02 | Reminder and email jobs shall use idempotency stamps so that retries cannot duplicate outbound messages. | System | Medium | Implemented |
| NFR-REL-03 | An invalid state transition shall be rejected without modifying the record and shall surface a user-readable error. | System | High | Implemented |
| NFR-REL-04 | Foreign keys shall enforce ownership between users, profiles, applications, appointments, and requests, with restrictive or cascading behaviour matching business intent. | System | High | Implemented |

*4 requirement(s) in this group.*

### 7.5 Maintainability (NFR-MAINT)

Maintainability requirements protect the layered structure of the application so that a change to a business rule does not require editing views, and a change to a status value does not require searching the codebase for string literals.

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-MAINT-01 | Presentation, request validation, business rules, and persistence shall remain separated into Blade, FormRequest, Service, and Eloquent layers. | System | Medium | Implemented |
| NFR-MAINT-02 | Status values shall be defined once as class constants or enums and reused by services, badges, and filters. | System | Medium | Implemented |
| NFR-MAINT-03 | Recurring interface elements shall be implemented as Blade components rather than duplicated markup. | System | Medium | Implemented |
| NFR-MAINT-04 | The current academic year and semester shall be supplied by configuration, not hard-coded values. | System | Medium | Implemented |

*4 requirement(s) in this group.*

### 7.6 Compatibility (NFR-COMPAT)

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-COMPAT-01 | The system shall operate on current versions of Chrome, Edge, Firefox, and Safari. | System | Medium | Implemented |
| NFR-COMPAT-02 | The system shall run on PHP 8.2 or later with a MariaDB or MySQL database and a standard Apache or Nginx web server. | System | High | Implemented |
| NFR-COMPAT-03 | The delivered interface shall be usable without a JavaScript bundler, relying on a single stylesheet and a single script. | System | Medium | Implemented |

*3 requirement(s) in this group.*

### 7.7 Portability and Responsiveness (NFR-PORT)

The interface is deliberately built without a client-side framework or build step, which keeps deployment to a single upload and keeps the application usable on the low-bandwidth connections common in provincial campuses.

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-PORT-01 | The interface shall remain usable from 360 px through 1920 px, collapsing the sidebar into a drawer below 1024 px. | All users | High | Implemented |
| NFR-PORT-02 | The system shall not require a commercial runtime or licence to operate. | System | Medium | Implemented |
| NFR-PORT-03 | Database, mail, and academic-term settings shall be supplied through environment configuration rather than source edits. | System | Medium | Implemented |

*3 requirement(s) in this group.*

### 7.8 Accessibility (NFR-ACC)

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-ACC-01 | Every page shall expose a main landmark, a skip link, and labelled navigation regions. | All users | Medium | Implemented |
| NFR-ACC-02 | All interactive controls shall be reachable and operable by keyboard with a visible focus indicator. | All users | High | Implemented |
| NFR-ACC-03 | Status and alert regions shall be announced to assistive technology through appropriate roles. | All users | Medium | Implemented |
| NFR-ACC-04 | Text and interactive elements shall meet WCAG 2.1 AA contrast against their backgrounds. | All users | Medium | Implemented |
| NFR-ACC-05 | Animation shall be suppressed when the user requests reduced motion. | All users | Low | Implemented |

*5 requirement(s) in this group.*

### 7.9 Data Quality and Retention (NFR-DATA)

| ID | Requirement | Actor | Priority | Status |
|---|---|---|---|---|
| NFR-DATA-01 | Status history, audit entries, and verification records shall be retained and never overwritten by later transitions. | System | Medium | Implemented |
| NFR-DATA-02 | All records shall store created and updated timestamps in server time and render them in a consistent human-readable format. | System | Medium | Implemented |
| NFR-DATA-03 | Application numbers and request numbers shall be unique and stable for the life of the record. | System | High | Implemented |

*3 requirement(s) in this group.*


## 8. Sample Hardware Requirements

The following figures describe a deployment adequate for an office-scale installation serving one campus. They are samples for planning, not measured minimums.

| ID | Component | Sample specification | Rationale |
| --- | --- | --- | --- |
| HW-01 | Application server | Quad-core x86-64 processor, 8 GB RAM, 100 GB SSD | Serves PHP requests, queue worker and scheduler on one host |
| HW-02 | Database server | Same host or separate host with 4 GB RAM and SSD storage | MariaDB performance is bound by disk latency for write-heavy audit and notification tables |
| HW-03 | Document storage | 50 GB allocated to the private storage volume, expandable | Documents are small but numerous; five megabytes is the per-file ceiling |
| HW-04 | Client device | Dual-core processor, 4 GB RAM, 1366×768 display or larger | Sufficient for a modern browser rendering a server-side interface |
| HW-05 | Client device, mobile | Any current smartphone with a 360 px viewport or wider | The interface collapses to a single column at this width |
| HW-06 | Network | 5 Mbps sustained per concurrent office user; internet access for email relay | Pages are server-rendered and text-heavy, so bandwidth demand is modest |
| HW-07 | Backup target | Storage equal to twice the database size plus the document volume | Supports a rolling backup of records and files |
| HW-08 | Uninterruptible power supply | Sized for the server and network equipment | Prevents corrupt writes during unexpected power loss |

> Capacity note. The dominant storage growth is not student records but uploaded documents and audit entries. A scholarship cycle with one thousand applicants, each uploading four documents at an average of one megabyte, consumes roughly four gigabytes per cycle.

<!-- pagebreak -->

## 9. Software and Operating Environment

### 9.1 Server Environment

| Component | Requirement | Notes |
| --- | --- | --- |
| Operating system | Windows or Linux | Development is performed on Windows with XAMPP; production may use either |
| Web server | Apache 2.4 or Nginx | Document root must be the `public` directory |
| PHP | 8.2 or later | Required by Laravel 12 |
| PHP extensions | OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, BCMath | The standard Laravel extension set |
| Database | MariaDB 10.4 or later, or MySQL 8 | Schema name `isu_osas_hub` in the delivered configuration |
| Composer | 2.x | Installs server-side dependencies |
| Queue worker | Framework worker process | Required for notification email |
| Scheduler | Operating system scheduler invoking the framework scheduler | Required for appointment reminders |
| SMTP relay | Any standards-compliant SMTP service | Required for OTP and notification email |

### 9.2 Client Environment

| Component | Requirement |
| --- | --- |
| Browser | Current Chrome, Edge, Firefox or Safari |
| JavaScript | Enabled; used only for progressive enhancement |
| Cookies | Enabled; required for session continuity |
| Screen | 360 px minimum width; 1366 px or wider recommended for administrative work |

### 9.3 Development Tooling

| Tool | Role |
| --- | --- |
| Composer | Server-side dependency management |
| Node.js and npm | Present for asset tooling; the delivered interface does not require a build step |
| Visual Studio Code | Source editing |
| PHPUnit | Automated test execution |
| XAMPP | Local Apache, PHP and MariaDB bundle |

### 9.4 Configuration

Runtime configuration is supplied through environment variables rather than source edits. The delivered environment file targets MariaDB with an SMTP mail transport; the shipped example file targets a file-backed SQLite database with log-based mail, which is convenient for fresh development but is not the deployed configuration. Two academic variables, the current academic year and the current semester, govern enrollment verification and must be updated at the start of every term.

> Observation. The asset dependencies include a utility-first CSS framework that the delivered layout does not use: the interface loads a single hand-written stylesheet directly. Documentation, wireframes and mockups therefore follow the hand-written stylesheet as the design system of record.

### 9.5 Deployment Prerequisites

A working installation requires the database schema to be migrated, the three roles and the initial administrator to be seeded, the storage link or private disk to be writable by the web server, an SMTP credential to be configured, and both the queue worker and the scheduler to be running. Guidance appointment types must be created by an administrator before students can book, and scholarship records must be authored before students see anything in the catalogue.


## 10. Business Rules

Business rules are statements that hold regardless of which screen invokes them. They are enforced in service classes, model methods, request validation or database constraints — never in the interface alone.

| ID | Rule | Enforcement |
| --- | --- | --- |
| BR-01 | A user holds exactly one role, drawn from student, counselor or administrator. | Role foreign key; role middleware |
| BR-02 | An inactive account cannot authenticate, cannot pass protected middleware, and receives no notifications. | Login controller; `EnsureActive`; notification service |
| BR-03 | A student account must complete OTP email verification before the student panel becomes reachable. | `verified.email` middleware |
| BR-04 | A student number is unique, at most twenty characters, and may contain only letters, digits and dashes. | Registration and user-creation validation; unique index |
| BR-05 | One user account has at most one student profile, and one student profile belongs to exactly one account. | Unique foreign key |
| BR-06 | A student may edit only contact number, address, GWA and family income. | `UpdateOwnStudentProfileRequest` |
| BR-07 | Administrator-only fields — student number, names, college, program, year level, enrollment status — cannot be changed by a student even by crafting a request. | Request validation whitelist |
| BR-08 | The profile passcode is stored only as a hash and is never rendered or serialised. | Passcode service; hidden attribute |
| BR-09 | A student may hold at most one application per scholarship, permanently. | Unique composite index |
| BR-10 | Documents may be uploaded or deleted only while the application is in draft. | Model predicate; controller guard |
| BR-11 | Approval of an application requires that every required requirement has a document whose verification status is verified. | Approval readiness check in the workflow service |
| BR-12 | Only the transitions defined by the application state machine are permitted; approved and rejected are terminal. | Workflow service transition table |
| BR-13 | A service request description must contain at least ten characters. | Request validation |
| BR-14 | Only the transitions defined by the service request state machine are permitted; completed and cancelled are terminal. | Workflow service transition table |
| BR-15 | A guidance appointment may be created only within a published availability block, inside office hours, and at or beyond the minimum booking notice. | Scheduling service |
| BR-16 | A guidance appointment may not be created beyond the maximum future booking horizon. | Scheduling service |
| BR-17 | A new appointment may not overlap any appointment in requested or confirmed status for the same counselor, nor any such appointment for the same student. | Conflict checks in the scheduling service |
| BR-18 | Only requested and confirmed appointments block time; completed, cancelled and rejected appointments do not. | Blocking status set |
| BR-19 | A student cancellation is refused inside the cancellation notice window. | Scheduling service |
| BR-20 | A scholarship is visible to students only when its status is open and the current date lies within its application window. | Scholarship scope and visibility guard |
| BR-21 | The five matching weights must total exactly one hundred percent, within a tolerance of one hundredth. | Weight validation |
| BR-22 | Enrollment verification is valid only for the academic year and semester it was recorded for. | Term comparison against configuration |
| BR-23 | A student with no enrollment verification records at all retains access, so that accounts created before the verification feature are not locked out. | Enrollment gate fallback |
| BR-24 | Uploaded files must be PDF, JPEG or PNG and must not exceed five megabytes. | Upload validation |
| BR-25 | The audit log is append-only and is exposed read-only; no update or delete route exists. | Route inventory; controller |
| BR-26 | Accounts are deactivated rather than deleted, preserving historical records. | User controller |
| BR-27 | Scholarship requirements are presented in administrator-defined display order. | Relationship ordering |
| BR-28 | An enrollment decision of verified, rejected or unable-to-verify maps to the student's enrollment status of enrolled, not enrolled or unknown respectively. | Enrollment update transaction |
| BR-29 | A notification is not created when an identical unread notification already exists for the same user, type and related record. | Notification service |
| BR-30 | An appointment reminder is dispatched at most once per appointment. | Reminder idempotency stamp |

<!-- pagebreak -->

## 11. Development Timeline

The reference document left the development period to be finalised by the project team. The schedule below describes the work breakdown that the delivered system reflects, expressed in relative phases rather than fixed calendar dates, because the actual dates must be supplied by the project team and the adviser.

| Phase | Work package | Principal outputs | Relative duration |
| --- | --- | --- | --- |
| P1 | Requirements and role analysis | Problem statement, actor list, scope boundary, entity identification | 1 unit |
| P2 | Data design | Migration set, relationships, constraints, seed data | 1 unit |
| P3 | Authentication and access control | Registration, OTP verification, login, role middleware, gates, policies | 2 units |
| P4 | Profile and enrollment foundation | Student profiles, passcode protection, enrollment verification review | 2 units |
| P5 | Scholarship core | Scholarship authoring, requirements, eligibility engine, matching and recommendations | 3 units |
| P6 | Application workflow | Draft and submission, document upload, review, verification, approval guard, history | 3 units |
| P7 | Guidance module | Availability, slot generation, booking, appointment workflow, reminders | 3 units |
| P8 | Student services module | Catalogue, request submission, assignment, processing workflow | 2 units |
| P9 | Notifications and audit | Notification service, queued email, audit logging, notification centre | 2 units |
| P10 | Analytics and search | Dashboards, three analytical views, CSV export, live search | 2 units |
| P11 | Hardening and verification | Automated tests, responsive behaviour, accessibility pass, performance indexes | 2 units |
| P12 | Documentation | SRS, UML, wireframes, mockups, traceability matrix | 1 unit |

> Duration values are relative and must be converted to calendar weeks by the project team. The sequence, however, is a dependency chain: the application workflow cannot be built before scholarships and requirements exist, and analytics cannot be built before the workflows that generate its data.

### 11.1 Milestones

| Milestone | Completion criterion |
| --- | --- |
| M1 — Authentication complete | A student can register, verify by OTP, log in, and be redirected to the student dashboard |
| M2 — Scholarship catalogue live | An administrator can author a scholarship with requirements and a student can see it when open |
| M3 — Application cycle closed loop | A student can apply, upload, submit; an administrator can verify documents and approve |
| M4 — Guidance operational | A student can book a slot that a counselor has published and the counselor can confirm it |
| M5 — Services operational | A student can raise a request that an administrator can assign and complete |
| M6 — Governance complete | Analytics, CSV export and the audit log are available and correct |
| M7 — Documentation accepted | SRS, UML, wireframes, mockups and traceability are approved by the adviser |


## 12. Verification and Acceptance Criteria

Each criterion below is written so that it can be executed without interpretation. Criteria are grouped by the requirement family they verify and are phrased as a precondition, an action and an observable outcome.

| ID | Verifies | Criterion |
| --- | --- | --- |
| AC-01 | FR-AUTH-01 | Given a registered account with a verified address, when the correct email and password are submitted, then the user is redirected to the dashboard of their role. |
| AC-02 | FR-AUTH-01 | Given a registered account, when an incorrect password is submitted, then the page redisplays with a generic invalid-credentials message and no indication of which field was wrong. |
| AC-03 | FR-AUTH-04 | Given a guest, when registration is submitted with a duplicate student number, then the form redisplays with a field-level error and the entered values are preserved. |
| AC-04 | FR-AUTH-05 | Given an unverified student, when any student page is requested, then the verification notice is shown and the page is not rendered. |
| AC-05 | FR-AUTH-05 | Given an unverified student, when a correct, unexpired passcode is submitted, then the student dashboard becomes reachable. |
| AC-06 | FR-AUTH-05 | Given an unverified student, when an incorrect passcode is submitted beyond the permitted attempts, then the code is invalidated and a new code must be requested. |
| AC-07 | FR-AUTH-07 | Given repeated failed logins, when the sixth attempt is made within one minute, then the request is throttled. |
| AC-08 | FR-AUTH-08 | Given a deactivated account, when valid credentials are submitted, then authentication is refused. |
| AC-09 | FR-RBAC-02 | Given a signed-in student, when an administrator URL is requested directly, then HTTP 403 is returned and no administrative data is disclosed. |
| AC-10 | FR-RBAC-05 | Given a signed-in student, when another student's document download URL is requested, then the request is refused. |
| AC-11 | FR-PROF-03 | Given a signed-in student, when a request is crafted to change year level or student number, then those changes are rejected while permitted fields are saved. |
| AC-12 | FR-PASS-01 | Given a student who has set a passcode, when the profile is locked, then the profile fields are hidden until the correct passcode is entered. |
| AC-13 | FR-PASS-02 | Given a stored profile passcode, when the database value is inspected, then it is a one-way hash and not the plaintext. |
| AC-14 | FR-ENR-03 | Given a pending verification record, when staff record a rejection without a reason, then validation fails; when a reason is supplied, the decision is stored and the student is notified. |
| AC-15 | FR-ENR-05 | Given a student without a verified record for the current term but with at least one historical record, when an application is submitted, then submission is blocked. |
| AC-16 | FR-ENR-06 | Given a student with no verification records at all, when an application is submitted, then submission is permitted. |
| AC-17 | FR-SCH-06 | Given a scholarship whose status is draft, or whose deadline has passed, when a student opens the catalogue, then that scholarship is absent and its detail page returns not-found. |
| AC-18 | FR-SCH-05 | Given a scholarship with three requirements, when an administrator reorders them, then the student-facing order matches the configured display order. |
| AC-19 | FR-ELIG-01 | Given a student whose GWA exceeds the scholarship minimum-GWA threshold, when eligibility is previewed, then the GWA criterion reports not met and states both values. |
| AC-20 | FR-ELIG-03 | Given every criterion met and none not applicable in a failing way, when eligibility is evaluated, then the overall decision is eligible. |
| AC-21 | FR-MATCH-03 | Given five weights totalling ninety-five, when the configuration is saved, then validation fails and the stored weights are unchanged. |
| AC-22 | FR-MATCH-05 | Given a student with a complete profile, when recommendations are opened, then scholarships are ordered by match score and each shows its category. |
| AC-23 | FR-APP-03 | Given an existing application for a scholarship, when the student attempts a second application, then the attempt is refused without creating a record. |
| AC-24 | FR-APP-04 | Given a draft application with all required documents uploaded, when the student submits, then the status becomes submitted and the submission time is recorded. |
| AC-25 | FR-APP-08 | Given an application in document verification with one required document still pending, when an administrator attempts to approve, then the transition is refused with an explanation. |
| AC-26 | FR-APP-08 | Given every required document verified, when an administrator approves, then the status becomes approved and the student is notified. |
| AC-27 | FR-APP-07 | Given an approved application, when any further transition is attempted, then the request is refused because the state is terminal. |
| AC-28 | FR-DOC-01 | Given a student uploading a ten-megabyte executable, when the upload is submitted, then validation fails and nothing is stored. |
| AC-29 | FR-DOC-04 | Given a pending document, when an administrator records rejection with remarks, then the status becomes rejected and the remarks are visible to the student. |
| AC-30 | FR-GUID-06 | Given a counselor with no published availability, when a student opens the slot page, then no bookable slots are offered. |
| AC-31 | FR-GUID-08 | Given a confirmed appointment for a counselor, when another student requests an overlapping slot, then that slot is not offered and the booking is refused if forced. |
| AC-32 | FR-GUID-10 | Given a confirmed appointment inside the cancellation notice window, when the student cancels, then the cancellation is refused. |
| AC-33 | FR-GUID-12 | Given a confirmed appointment inside the reminder window, when the reminder command runs twice, then exactly one reminder exists. |
| AC-34 | FR-SVC-03 | Given a service description of nine characters, when the request is submitted, then validation fails. |
| AC-35 | FR-SVC-07 | Given a completed service request, when a further transition is attempted, then it is refused. |
| AC-36 | FR-NOTIF-02 | Given an unread notification for an event, when the same event recurs before the notification is read, then no duplicate unread notification is created. |
| AC-37 | FR-NOTIF-05 | Given several unread notifications, when mark-all-read is used, then the unread count becomes zero. |
| AC-38 | FR-ANLYT-05 | Given a start date later than the end date, when analytics is requested, then the filter is rejected or normalised and no error page is shown. |
| AC-39 | FR-ANLYT-06 | Given a filtered analytics view, when export is requested, then a CSV download is produced whose rows match the on-screen figures. |
| AC-40 | FR-AUDIT-03 | Given audit entries from several modules, when filtered by module and date range, then only matching entries are listed. |
| AC-41 | FR-AUDIT-05 | Given a signed-in administrator, when an audit entry is opened, then no control to edit or delete it is present. |
| AC-42 | FR-SEARCH-01 | Given a signed-in student, when a live search is performed on the scholarship module, then only scholarships visible to students are suggested. |
| AC-43 | NFR-SEC-04 | Given a document stored on the private disk, when its path is requested directly from the web root, then it is not served. |
| AC-44 | NFR-PORT-01 | Given a 390 px viewport, when any authenticated page is opened, then the sidebar is collapsed into a drawer and the content occupies a single column. |
| AC-45 | NFR-ACC-02 | Given keyboard-only navigation, when tabbing through a form, then every control receives visible focus and the primary action is reachable. |
| AC-46 | NFR-REL-01 | Given a workflow transition that fails mid-operation, when the transaction rolls back, then no partial status change or history entry remains. |

### 12.1 Verification Methods

| Method | Applied to |
| --- | --- |
| Automated feature test | Authentication, authorization, workflow transitions, duplicate prevention, notifications, search, analytics |
| Automated unit test | Eligibility evaluation, matching score, recommendation categorisation, workflow transition table |
| Manual inspection | Layout, responsive behaviour, print output, accessibility landmarks |
| Data inspection | Hash storage, absence of plaintext secrets, audit completeness |
| Route inventory | Absence of forbidden routes such as audit deletion or counselor service-request handling |


## 13. Requirements Requiring Final Confirmation

This section records every point on which the delivered code and the project brief disagree, or on which a decision is legitimately the project team's rather than the documenter's.

### 13.1 Actor Decisions

| ID | Question | Current state | Consequence if unresolved |
| --- | --- | --- | --- |
| CF-01 | Should Adviser exist as a fourth role with an endorsement step? | No adviser role, route, gate or workflow transition exists | Use case diagrams and the permission matrix omit an actor the panel may expect |
| CF-02 | Should OSAS Staff become a distinct role? | Implemented as the union of Administrator and Counselor duties for enrollment verification | A reader may believe staff accounts exist when they do not |
| CF-03 | Should a Super Admin exist above Administrator? | Administrator is a single flat role with full authority and no hierarchy | Separation-of-duties questions cannot be answered from the code |
| CF-04 | Are the three implemented roles final for defence purposes? | Documented as Student, Counselor, Administrator | Any renaming must be propagated to all four deliverables |

### 13.2 Functional Gaps Requiring a Decision

| ID | Gap | Evidence in source | Options |
| --- | --- | --- | --- |
| CF-05 | No interface creates enrollment-verification records | Reviewer controller supports list, show and update only; no creation call exists anywhere | Add an intake route, or document insertion as an administrative data-entry procedure |
| CF-06 | Enrollment gate is inert until the first record exists | The gate returns permitted when a student has no records | Accept as a migration safeguard, or require at least one record for new registrations |
| CF-07 | Counselor cannot process assigned service requests | Workflow and policy accept a counselor handler; no counselor routes exist | Add counselor routes, or restrict assignment to administrators and document it |
| CF-08 | Appointment notes cannot be written | Columns and display exist; no form, request or route | Add authoring, or remove the fields from the interface |
| CF-09 | No self-service password reset | Reset-token table and auth configuration exist; no routes | Add the flow, or document administrator reset as the supported procedure |
| CF-10 | Appointment types are absent on a fresh installation | The guidance seeder is not registered in the database seeder | Register the seeder, or require manual creation as a deployment step |
| CF-11 | Seeded scholarships are dated 2025 | Seed data predates the current term | Refresh seed data before any demonstration or screenshot capture |
| CF-12 | Administrative guidance oversight is view-only | Administrator can list and open appointments but cannot decide them | Accept as deliberate separation, or grant administrative override |
| CF-13 | Withdrawn application status has no transition | Status appears in the badge map but not in the transition table | Remove the label, or implement withdrawal |
| CF-14 | Duplicate application block is permanent | Unique index covers all statuses including rejected | Accept, or relax to allow reapplication after rejection |

### 13.3 Documentation Decisions

| ID | Question | Proposed resolution |
| --- | --- | --- |
| CF-15 | Should proposed capabilities appear in the body of the specification? | No. Main sections describe only verified behaviour; proposed items appear in this section and in the traceability matrix with status Proposed |
| CF-16 | Which design system governs the mockups? | The hand-written stylesheet actually loaded by the application, not the unused utility framework in the asset dependencies |
| CF-17 | What is the official development period? | To be supplied by the project team; Section 11 uses relative phases |
| CF-18 | Who are the document authors of record? | The names carried in the reference document are retained only if the project team confirms them |

### 13.4 Limitations of This Specification

Three limits apply. First, behaviour was established by reading source, migrations, routes and views; no live instance was exercised, so runtime timing, email deliverability and visual rendering were not observed. Second, the specification describes the delivered code, not an intended future design; where the code is internally inconsistent, the inconsistency is reported rather than resolved. Third, quantitative non-functional targets are stated as defensible engineering targets, not as measurements taken on the delivered system.


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
