## 4. Functional Requirements

This section states every functional requirement of the delivered system. Requirements are grouped by module and identified by the prefix of their identifier. Each row records the responsible actor, the priority assigned during verification, and the implementation status.

Priority is interpreted as follows. **High** requirements are essential to the operational purpose of the module. **Medium** requirements improve efficiency or governance. **Low** requirements are desirable refinements.

{{REQ_STATS}}

### 4.1 Authentication and Account Lifecycle (FR-AUTH)

Authentication is implemented without a starter kit. The application provides its own login, registration and verification flows, and issues a six-digit one-time passcode by email rather than relying on a signed-link verification. Login and registration are throttled, failed attempts are audited without recording the attempted password, and deactivated accounts are rejected at authentication and again by middleware on protected pages.

{{REQ_TABLE:AUTH}}

### 4.2 Access Control (FR-RBAC)

Authorization is layered. Route groups carry the role middleware, named gates express capabilities, and policies decide on individual records. The result is that a student cannot reach an administrator page by typing its address, and cannot open another student's document by guessing an identifier.

{{REQ_TABLE:RBAC}}

### 4.3 Student Profiles and User Management (FR-PROF)

A student profile is a separate record from the user account, joined one-to-one. Students may correct only the four fields over which they hold authoritative knowledge — contact number, address, GWA and family income. Academic identity fields and enrollment status are administrator-controlled, which prevents a student from inflating eligibility by rewriting their year level or program.

{{REQ_TABLE:PROF}}

### 4.4 Profile Security (FR-PASS)

The profile passcode is a student-controlled privacy measure for shared or public computers. The passcode is hashed with the same one-way algorithm used for account passwords, is never returned to the browser, and can be reset by an administrator when a student forgets it.

{{REQ_TABLE:PASS}}

### 4.5 Enrollment Verification (FR-ENR)

Enrollment verification exists so that a scholarship application or appointment booking is only possible for a student who is actually enrolled in the current term. Verification records are scoped to an academic year and semester, and the current term is read from configuration.

> Observed gap. The application can list, display and decide enrollment-verification records, but no route or interface creates them. Until a record exists for a student, the enrollment gate deliberately permits access, because the check treats a completely unverified student as a legacy account. The module is therefore classified as Partially Implemented, and the intake requirement is recorded as Proposed.

{{REQ_TABLE:ENR}}

### 4.6 Scholarship Management (FR-SCH)

Scholarships carry eligibility thresholds, a set of document requirements and an application window. A scholarship is visible to students only when its status is open and the current date falls inside the window, so an administrator can prepare a scholarship in draft without exposing it.

{{REQ_TABLE:SCH}}

### 4.7 Eligibility Evaluation (FR-ELIG)

Eligibility is evaluated deterministically. For each criterion the engine returns one of met, not met, or not applicable, together with the student's value, the scholarship threshold and a plain-language reason. The overall decision is derived from the criterion set rather than computed separately, which keeps the explanation and the verdict consistent.

{{REQ_TABLE:ELIG}}

### 4.8 Matching and Recommendations (FR-MATCH)

Matching ranks scholarships for a particular student by combining five factors under administrator-controlled weights. Because the weights must total exactly one hundred, a ranking always expresses a genuine trade-off rather than an arbitrary scale.

{{REQ_TABLE:MATCH}}

### 4.9 Application Workflow (FR-APP)

The application lifecycle is governed by a finite state machine. A student starts an application in draft, uploads documents, and submits. An administrator then moves the record through review and document verification to approval or rejection. Approval is guarded: the transition is refused unless every required requirement has a document that has itself been verified.

{{REQ_TABLE:APP}}

### 4.10 Document Management (FR-DOC)

Documents are stored on a private disk. Authorised download is the only way to read a file, and the policy compares the requesting user with the owner of the application. Uploads accept PDF, JPEG and PNG only, up to five megabytes.

{{REQ_TABLE:DOC}}

### 4.11 Guidance and Counseling (FR-GUID)

Guidance scheduling is computed rather than negotiated. Counselor availability, office hours, appointment duration, minimum booking notice and the booking horizon together determine which slots are offered. Conflicts are checked for both the counselor and the student before an appointment is created.

{{REQ_TABLE:GUID}}

### 4.12 Student Services (FR-SVC)

Service requests are numbered, described, and optionally supported by attachments. An administrator assigns a handler and moves the request through processing, possibly requesting further information, to completion or cancellation.

> Observed gap. The workflow service and the policy layer permit a counselor to be the assigned handler, but no counselor route or screen exists for service requests. The module is classified as Partially Implemented on that basis.

{{REQ_TABLE:SVC}}

### 4.13 Notifications (FR-NOTIF)

Notifications are written to the database and simultaneously queued for email. Duplicate suppression prevents the same unread notification from being created twice for the same event, and the email job stamps the record so that a retry cannot send a second message.

{{REQ_TABLE:NOTIF}}

### 4.14 Analytics and Reporting (FR-ANLYT)

Analytics are descriptive. They report what happened in the scholarship, guidance and service workflows over a chosen period, and they are exportable so that the office can include the figures in its own reports.

{{REQ_TABLE:ANLYT}}

### 4.15 Audit Logging (FR-AUDIT)

The audit log records who did what, to which record, when, and from which address. It is exposed read-only, and no route exists to modify or delete an entry.

{{REQ_TABLE:AUDIT}}

### 4.16 Search and Navigation (FR-SEARCH)

Live search returns suggestions only for records the signed-in role is entitled to see. The same normalisation and length bounds are applied to every search surface, which keeps query cost predictable.

{{REQ_TABLE:SEARCH}}
