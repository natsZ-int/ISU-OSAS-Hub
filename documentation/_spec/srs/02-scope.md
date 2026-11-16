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
