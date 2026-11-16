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
