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
