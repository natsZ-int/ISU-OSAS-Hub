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
