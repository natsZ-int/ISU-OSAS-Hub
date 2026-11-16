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
