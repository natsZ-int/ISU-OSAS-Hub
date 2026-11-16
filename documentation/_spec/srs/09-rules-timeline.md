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
