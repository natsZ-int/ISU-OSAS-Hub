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
