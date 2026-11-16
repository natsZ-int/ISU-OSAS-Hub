#!/usr/bin/env python3
"""portfolio_front.py - cover, front matter, Section 1 and Section 2."""

from __future__ import annotations

from collections import Counter, OrderedDict

MODULES = [
    ("M01", "Authentication and Account Lifecycle",
     "Registration, login, logout, one-time-passcode email verification, throttling and "
     "rejection of deactivated accounts", "Implemented"),
    ("M02", "Access Control",
     "Role middleware, named gates and record policies enforced on the server",
     "Implemented"),
    ("M03", "Student Profiles and User Management",
     "Profile maintenance, self-service editing of four fields, administrative maintenance "
     "and account activation", "Implemented"),
    ("M04", "Profile Security",
     "Student-set profile passcode, session lock, unlock and administrative recovery",
     "Implemented"),
    ("M05", "Enrollment Verification",
     "Per-term verification records, staff decisions and gating of enrollment-dependent "
     "student actions", "Partially implemented"),
    ("M06", "Scholarship Management",
     "Scholarship authoring, lifecycle, document requirements, publication and announcement",
     "Implemented"),
    ("M07", "Eligibility Evaluation",
     "Five-criterion rule engine that explains each criterion and derives the verdict",
     "Implemented"),
    ("M08", "Matching and Recommendations",
     "Weighted scoring under administrator-controlled weights, recommendations and full "
     "match list", "Implemented"),
    ("M09", "Application Workflow",
     "Draft creation, submission, review, state machine, approval guard and status history",
     "Implemented"),
    ("M10", "Document Management",
     "Validated upload, deletion while in draft, per-document verification and authorised "
     "download", "Implemented"),
    ("M11", "Guidance and Counseling",
     "Availability, slot generation, booking, confirmation, completion and reminders",
     "Implemented"),
    ("M12", "Student Services",
     "Service catalogue, request submission, assignment, processing and status history",
     "Partially implemented"),
    ("M13", "Notifications",
     "In-application notification centre with duplicate suppression and queued email",
     "Implemented"),
    ("M14", "Analytics and Reporting",
     "Operational metrics, three analytical views, period filtering and CSV export",
     "Implemented"),
    ("M15", "Audit Logging",
     "Capture of authentication and business events, search, detail view and read-only policy",
     "Implemented"),
    ("M16", "Search and Navigation",
     "Role-scoped typeahead, scholarship search and the shared application shell",
     "Implemented"),
]

MODULE_TEXT = {
    "M01": "Authentication is implemented without a starter kit. The application provides its "
           "own login, registration and verification screens, and verifies an address with a "
           "six-digit one-time passcode rather than a signed link. Login and registration are "
           "throttled, failed attempts are audited without recording the attempted password, "
           "and deactivated accounts are refused at authentication and again by middleware.",
    "M02": "Authorization is layered: route groups carry role middleware, named gates express "
           "shared capabilities, and record policies compare the owner of a record with the "
           "signed-in user. A student cannot reach an administrator page by typing its address, "
           "and cannot open another student's document by guessing an identifier.",
    "M03": "A student profile is a separate record joined one-to-one to the user account. "
           "Students may correct only the four fields over which they hold authoritative "
           "knowledge — contact number, address, GWA and family income. Academic identity fields "
           "and enrollment status remain administrator-controlled.",
    "M04": "The profile passcode is a student-controlled privacy measure for shared computers. "
           "It is hashed with the same one-way algorithm as an account password, is never returned "
           "to the browser, and can be reset by an administrator when it is forgotten.",
    "M05": "Enrollment verification exists so that an application or a booking can be limited to "
           "a student who is enrolled in the current term. Records are scoped to an academic year "
           "and semester and the current term is read from configuration.",
    "M06": "A scholarship carries eligibility thresholds, a set of document requirements and an "
           "application window. It is visible to students only when its status is open and the "
           "current date falls inside the window, so an administrator can prepare a scholarship in "
           "draft without exposing it.",
    "M07": "Eligibility is evaluated deterministically. For each criterion the engine returns met, "
           "not met or not applicable together with the student's value, the threshold and a "
           "plain-language reason. The verdict is derived from the criterion results, which keeps "
           "the explanation and the decision consistent.",
    "M08": "Matching ranks scholarships for one student by combining five factors under weights "
           "that an administrator controls. Because the weights must total one hundred, a ranking "
           "expresses a trade-off rather than an arbitrary scale.",
    "M09": "The application lifecycle is governed by a finite state machine. A student starts a "
           "draft, uploads documents and submits. An administrator moves the record through review "
           "and document verification to approval or rejection. Approval is refused unless every "
           "required requirement has a document that has itself been verified.",
    "M10": "Documents are stored on a private disk. Authorised download is the only way to read a "
           "file, and the policy compares the requesting user with the owner of the application. "
           "Uploads accept PDF, JPEG and PNG only, up to five megabytes.",
    "M11": "Guidance scheduling is computed rather than negotiated. Counselor availability, office "
           "hours, appointment duration, minimum notice and the booking horizon together determine "
           "which slots are offered, and conflicts are checked for both the counselor and the "
           "student before an appointment is created.",
    "M12": "Service requests are numbered, described and optionally supported by attachments. An "
           "administrator assigns a handler and moves the request through processing, possibly "
           "asking for further information, to completion or cancellation.",
    "M13": "Notifications are written to the database for in-application display and simultaneously "
           "queued for email. Duplicate suppression prevents the same unread notification from being "
           "created twice, and the mail job stamps the record so that a retry cannot send twice.",
    "M14": "Analytics are descriptive. They report what happened in the scholarship, guidance and "
           "service workflows over a chosen period and can be exported so that the office can reuse "
           "the figures in its own reports.",
    "M15": "The audit log records who acted, on which record, when and from which address. It is "
           "exposed read-only and no route exists to modify or delete an entry.",
    "M16": "Live search returns suggestions only for records that the signed-in role may see. The "
           "same normalisation and length bounds apply to every search surface, which keeps query "
           "cost predictable.",
}


def build(doc, requirements, toc_entries=None) -> None:
    # ----------------------------------------------------------------------
    # Cover
    # ----------------------------------------------------------------------
    doc.cover("""
institution: ISABELA STATE UNIVERSITY
office: Office of Student Affairs and Services
address: Echague, Isabela
doc_type: System Development Portfolio
title: ISU OSAS Hub
subtitle: Integrated Student Affairs and Services Management System
program: Software Engineering I
author: Macarilay, Jake Ace | Address: [to be supplied] | Email: [to be supplied]
author: Alindayu, Fortunato Jr. | Address: [to be supplied] | Email: [to be supplied]
author: Lucas, John Lawrence | Address: [to be supplied] | Email: [to be supplied]
date: November 2026
""")

    # ----------------------------------------------------------------------
    # Front matter
    # ----------------------------------------------------------------------
    doc.p("**Document control**")
    doc.table(
        ["Item", "Details"],
        [
            ["Document", "System Development Portfolio"],
            ["Project", "ISU OSAS Hub — Integrated Student Affairs and Services Management System"],
            ["Course", "Software Engineering I"],
            ["Institution", "Isabela State University — Office of Student Affairs and Services"],
            ["Developers", "Macarilay, Jake Ace; Alindayu, Fortunato Jr.; Lucas, John Lawrence"],
            ["Document version", "1.0"],
            ["Date", "November 2026"],
            ["System version", "Git commit 8270c54, 8 November 2026"],
            ["Prepared from", "The running application, its source code, and the accompanying SRS, "
                             "UML, wireframe, mockup and traceability deliverables"],
            ["Status", "For submission"],
        ],
        widths=[1.35, 5.15])

    doc.p("**How to read this document**")
    doc.bullets([
        "The five sections follow the numbering required by the portfolio instructions. Every "
        "figure and every table carries a number that is scoped to its chapter and is referred to "
        "in the text.",
        "Requirements keep the identifiers used by the Software Requirements Specification "
        "(`FR-<group>-nn`, `NFR-<group>-nn`), so a row in Section 2 can be traced to a UML "
        "artefact, a wireframe and a mockup through the accompanying traceability matrix.",
        "Implementation status is used with a fixed meaning. *Implemented* means the behaviour "
        "exists and is reachable in the delivered code, *Partially implemented* means part of the "
        "behaviour is present but a required entry point or guard is missing, and *Proposed* means "
        "the capability is absent and is recorded only so that the gap stays visible.",
        "Text in square brackets, such as `[to be supplied]`, marks information that the group must "
        "complete before submission. No personal detail and no external link has been invented to "
        "fill a gap.",
        "Every screenshot in Section 4 was captured from the running application against its own "
        "database during the preparation of this document; none is a wireframe, a mockup or a "
        "reconstruction.",
    ])

    doc.page_break()
    doc.p("**Table of contents**")
    if toc_entries is None:
        doc.toc()
    else:
        doc.toc_static(toc_entries)
    doc.page_break()

    # ----------------------------------------------------------------------
    # SECTION 1
    # ----------------------------------------------------------------------
    doc.start_chapter(1)
    doc.h1("1. System Proposal")

    doc.h2("1.1 Introduction")
    doc.h3("1.1.1 Background and context")
    doc.p(
        "The Office of Student Affairs and Services (OSAS) of Isabela State University is "
        "responsible for the non-academic side of student life. Its work includes administering "
        "scholarship programmes and their application cycles, providing guidance and counseling "
        "services, and processing the routine student services that students request during a "
        "term, from certificates and clearances to inquiries about financial assistance.")
    doc.p(
        "Each of these functions produces records that two parties depend on: students, who need "
        "to know what is available and what has happened to a request, and OSAS personnel, who "
        "need to decide, verify and report. ISU OSAS Hub is a web-based information system that "
        "keeps those records in a single authenticated portal. It was developed as the Software "
        "Engineering I project of Macarilay, Jake Ace; Alindayu, Fortunato Jr.; and Lucas, John "
        "Lawrence, and the project repository records development between 6 October and 8 "
        "November 2026.")
    doc.p(
        "The delivered system is a server-rendered Laravel 12 application backed by MariaDB. It "
        "recognises three roles — student, counselor and administrator — and sixteen functional "
        "modules covering authentication, access control, profiles, enrollment verification, "
        "scholarships, eligibility, matching, applications, documents, guidance, student services, "
        "notifications, analytics, audit logging and search.")

    doc.h3("1.1.2 Existing situation and processes")
    doc.p(
        "Before the system, OSAS transactions were handled through paper forms, shared "
        "spreadsheets and office walk-ins. Scholarship announcements depended on bulletin boards "
        "and informal channels, so information reached students late and unevenly. Eligibility was "
        "screened by hand, which meant that two staff members could reach different conclusions "
        "from the same profile and that no decision could be reconstructed afterwards. Application "
        "documents were kept in physical folders, so verifying one applicant required locating a "
        "folder and remembering which pages had already been checked.")
    doc.p(
        "Guidance appointments and student-service requests were arranged verbally. A student "
        "asked for a slot, a counselor wrote it down or did not, and nothing in the process "
        "prevented two students from being promised the same hour. Service requests were recorded "
        "in notebooks or in conversation, so a student had no way to prove what had been requested "
        "and the office had no way to show who had handled it.")
    doc.p(
        "The consequence was not only delay. It was the absence of a record: the office could not "
        "answer a simple question — how many applications were pending, how many appointments were "
        "confirmed, which requests were still open — without assembling the answer by hand from "
        "several files.")

    doc.h3("1.1.3 Importance and benefits")
    doc.p(
        "The system replaces that arrangement with a single portal in which every privileged action "
        "is recorded. Its benefits follow directly from the modules that implement them.")
    doc.bullets([
        "**One authoritative catalogue.** Scholarships live in one table with an explicit status "
        "and an application window, so a student sees the same information as the office.",
        "**Screening that can be explained.** Eligibility is computed from five criteria and each "
        "criterion reports its own result and reason, so a student learns why a scholarship is out "
        "of reach instead of being told that it is.",
        "**Controlled document handling.** Uploads are validated, stored privately, verified one by "
        "one and served only to authorised users.",
        "**Scheduling that cannot double-book.** Bookable slots are generated from published "
        "availability and checked for conflicts on both sides before an appointment exists.",
        "**Status that students can see.** Applications, appointments and service requests each "
        "carry a status, a history and a numbered reference.",
        "**Accountability by default.** Authentication events and business decisions produce audit "
        "entries that cannot be edited through the interface.",
        "**Evidence for management.** Three analytical views and a CSV export answer the questions "
        "that previously required manual assembly.",
    ])

    doc.h2("1.2 Problem Statement")
    doc.p(
        "Six problems motivated the system. Each is stated below with its effect on students, its "
        "effect on OSAS personnel, and the module that responds to it. The table is a summary; the "
        "requirement statements that make each response testable are given in Section 2.")
    doc.table(
        ["Problem", "Effect on students", "Effect on OSAS personnel", "Response in the system"],
        [
            ["Scholarship information is announced late and unevenly",
             "Deadlines are missed because the announcement never reached the student",
             "The same questions are answered repeatedly and inconsistently",
             "M06 — one catalogue with status, window and announcement notifications"],
            ["Eligibility is screened by hand",
             "Two students with comparable profiles can receive different answers",
             "Rules are re-checked per applicant and decisions cannot be audited",
             "M07 and M08 — five-criterion engine with per-criterion reasons and weighted ranking"],
            ["Documents are held in physical folders",
             "Documents are resubmitted because the office cannot confirm receipt",
             "Verification state is remembered rather than recorded",
             "M10 and M09 — validated upload, per-document verification, authorised download"],
            ["Appointments are arranged verbally",
             "A student can be given a slot that was already promised to someone else",
             "Counselor schedules live outside any system and are easily contradicted",
             "M11 — published availability, generated slots, conflict checks, status workflow"],
            ["Service requests leave no reliable trace",
             "A student cannot prove what was requested, when, or by whom it was handled",
             "No record of ownership, progress or completion",
             "M12 — numbered request, assigned handler, status history"],
            ["Management reporting is manual",
             "Indirect: slow answers to legitimate questions about the office",
             "Figures are assembled by hand from several files and cannot be reconciled",
             "M14 — three analytical views, period filters and CSV export"],
        ],
        widths=[1.5, 1.6, 1.6, 1.8],
        caption="Problems addressed by the system and the module that responds to each")

    doc.p(
        "Two limits are stated deliberately. First, the system supports a decision; it does not "
        "replace it. An application is approved by an administrator, not by the software, and the "
        "matching score ranks scholarships without granting them. Second, the hub does not "
        "integrate with the university student information system, so GWA, family income and "
        "enrollment status are supplied by the student or by an administrator and are checked "
        "against uploaded documents rather than against academic records of record.")

    doc.h2("1.3 Project Description")
    doc.h3("1.3.1 Purpose and scope")
    doc.p(
        "The purpose of ISU OSAS Hub is to give the office and its students one system of record "
        "for scholarships, guidance appointments and student-service requests, and to make the "
        "state of every record visible to the people entitled to see it.")
    doc.p("**In scope.** The delivered system covers the following.")
    doc.bullets([
        "Account registration, one-time-passcode verification, login, logout and session handling.",
        "Role-based access control through route middleware, gates and record policies.",
        "Student profiles, self-service editing of four fields, and administrative maintenance.",
        "Profile passcode protection with lock, unlock and administrative reset.",
        "Per-term enrollment verification records and gating of enrollment-dependent actions.",
        "Scholarship authoring, lifecycle, document requirements, publication and announcement.",
        "Eligibility evaluation with a per-criterion explanation.",
        "Weighted matching, recommendation categories and a full ranked match list.",
        "Application submission, document upload, review, verification, approval and history.",
        "Guidance availability, slot generation, booking, confirmation, completion and reminders.",
        "Student-service catalogue, request submission, assignment, processing and history.",
        "In-application notifications with queued email delivery.",
        "Analytics over the three workflows, with period filters and CSV export.",
        "Audit logging of authentication and business events, exposed read-only.",
        "Role-scoped search and a shared application shell.",
    ])
    doc.p("**Out of scope.** The following are deliberately excluded from this version.")
    doc.bullets([
        "Disbursement or payroll of scholarship funds.",
        "Grade management or any academic record of record.",
        "Integration with the registrar or the student information system.",
        "SMS messaging and native mobile applications.",
        "Multi-campus tenancy and biometric identity verification.",
        "Optical character recognition of uploaded documents.",
        "Self-service password reset and rescheduling of confirmed appointments.",
        "Counselor-authored session notes and counselor-side processing of service requests.",
    ])

    doc.h3("1.3.2 Major system modules")
    doc.p(
        "The sixteen modules below are the unit of delivery and the unit of verification. Each "
        "module owns a set of requirements with its own identifier prefix, and each is reachable "
        "from the interface of at least one role.")
    doc.table(
        ["ID", "Module", "What it provides", "Status"],
        [[code, name, text, status] for code, name, text, status in MODULES],
        widths=[0.5, 1.65, 3.35, 1.0],
        caption="Functional modules of the delivered system")

    doc.h3("1.3.3 Key capabilities and scale")
    doc.p(
        "Three workflows account for most of the system and are the ones a reader should follow "
        "first. In the **scholarship workflow** an administrator authors a scholarship with its "
        "thresholds, required documents and window, announces it, and later reviews applications "
        "whose documents have been uploaded and verified. In the **guidance workflow** a counselor "
        "publishes weekly availability, a student books one of the generated slots, and the "
        "counselor confirms, rejects or completes the appointment. In the **student-service "
        "workflow** a student submits a numbered request from a catalogue that an administrator "
        "maintains, and an assigned handler moves it through processing to completion.")
    doc.p(
        "The size of the delivered system is summarised below. The figures are counts taken from "
        "the repository and the database of the verified build, not estimates.")
    doc.table(
        ["Measure", "Value"],
        [
            ["Functional requirements", "113, of which 106 implemented, 1 partially implemented "
                                        "and 6 proposed"],
            ["Non-functional requirements", "40, all implemented in the delivered build"],
            ["Functional modules", "16, of which 14 implemented and 2 partially implemented"],
            ["User roles", "3 — student, counselor, administrator"],
            ["Application routes", "129, excluding vendor packages"],
            ["Database tables", "30"],
            ["Eloquent models", "22"],
            ["Service classes", "19"],
            ["Controllers", "31"],
            ["Blade view templates", "91"],
            ["Automated test suite", "PHPUnit 11 test suite in tests/"],
        ],
        widths=[2.0, 4.5],
        caption="Scale of the delivered system")

    doc.h2("1.4 Target Users")
    doc.p(
        "The system recognises exactly three human roles, stored in a `roles` table with one role "
        "per account; every restricted route re-checks the role or a named gate on the server. A "
        "fourth, non-human actor exists: scheduled commands and a queue worker deliver appointment "
        "reminders and notification email.")
    doc.table(
        ["Role", "Who holds it", "Responsibilities in the system", "Expected benefit"],
        [
            ["Student",
             "Any enrolled ISU student with a verified account",
             "Maintains a profile, browses and searches scholarships, checks eligibility, applies "
             "and uploads documents, books and cancels guidance appointments, submits and tracks "
             "service requests",
             "One place to find opportunities and to see the state of every request"],
            ["Counselor",
             "Guidance personnel whose account is created by an administrator",
             "Publishes weekly availability, confirms, rejects or completes appointments assigned "
             "to them, and records enrollment-verification decisions",
             "A schedule that the system maintains and a queue that cannot be double-booked"],
            ["Administrator",
             "OSAS staff authorised to operate the office workflow",
             "Manages accounts and profiles, authors scholarships and requirements, reviews "
             "applications and verifies documents, configures guidance and service settings, "
             "monitors analytics and reads the audit log",
             "One console for the whole office, with evidence of who did what"],
        ],
        widths=[0.95, 1.55, 2.5, 1.5],
        caption="Verified user roles, responsibilities and benefits")

    doc.h3("1.4.1 Roles that are not implemented")
    doc.p(
        "The earlier documentation brief named four actors: Student, Adviser, OSAS Staff and Super "
        "Admin. Verification of the schema, seeders, routes and interface found only the three "
        "roles above; the remaining names are recorded here so the discrepancy cannot be mistaken "
        "for a delivered feature.")
    doc.table(
        ["Requested actor", "Present in the system", "Closest implemented construct"],
        [
            ["Student", "Yes", "`student` role"],
            ["Adviser", "No",
             "No endorsement step exists anywhere in the application workflow"],
            ["OSAS Staff", "No, not as a role",
             "The label appears in interface copy only, so treating it as an actor in a use case "
             "diagram would misrepresent the system; the duties are performed by the administrator "
             "and counselor roles"],
            ["Super Admin", "No",
             "`admin` is a single flat role with no hierarchy and no impersonation"],
        ],
        widths=[1.3, 1.4, 3.8],
        caption="Actors named in the brief that the delivered system does not implement")

    doc.page_break()

    # ----------------------------------------------------------------------
    # SECTION 2
    # ----------------------------------------------------------------------
    doc.start_chapter(2)
    doc.h1("2. Software Requirements Specification")
    doc.p(
        "This section restates the requirement baseline against which the system was built and "
        "verified: 113 functional requirements and 40 non-functional requirements. Each "
        "requirement has a unique identifier, a priority, an implementation status and a traceable "
        "artefact in the accompanying UML, wireframe, mockup and traceability deliverables. The "
        "full statements, acceptance criteria and data requirements are in the Software "
        "Requirements Specification; the tables here are the same requirements, grouped so that "
        "they can be read next to the modules they belong to.")
    doc.p(
        "Priority is used consistently. **High** requirements are essential to the operational "
        "purpose of the module, **Medium** requirements improve efficiency or governance, and "
        "**Low** requirements are desirable refinements. Status uses the three values defined in "
        "the front matter.")

    doc.h2("2.1 Functional Requirements")
    doc.p(
        "Functional requirements are organised by module and, within a module, by the user task "
        "they support. Each statement is written so that it can be tested: it names the actor, the "
        "action and the observable result.")
    doc.table(
        ["Module", "Prefix", "Requirements", "Implemented", "Partial", "Proposed"],
        _module_summary(requirements),
        widths=[2.3, 0.9, 1.1, 1.05, 0.6, 0.75],
        caption="Functional requirements by module")

    for number, (code, name, _text, _status) in enumerate(MODULES, start=1):
        rows = [row for row in requirements
                if row["req_id"].startswith("FR-") and row["module"] == code]
        rows.sort(key=lambda row: (row["group"], row["req_id"]))
        doc.h3(f"2.1.{number} {code} — {name}")
        doc.p(MODULE_TEXT[code])
        if code == "M05":
            doc.note(
                "Observed gap. The application can list, display and decide enrollment-verification "
                "records, but no route or interface creates them, and the enrollment gate permits "
                "access while no record exists for a student. The module is therefore classified "
                "as partially implemented and the intake requirement is recorded as proposed.")
        if code == "M12":
            doc.note(
                "Observed gap. The workflow service and the policy layer allow a counselor to be the "
                "assigned handler of a service request, but no counselor route or screen exists for "
                "that purpose. The module is classified as partially implemented on that basis.")
        doc.table(
            ["ID", "Requirement", "Priority", "Status"],
            [[row["req_id"], f"**{row['title']}.** {row['description']}",
              row["priority"], row["status"]] for row in rows],
            widths=[0.62, 4.15, 0.7, 1.03],
            caption=f"{name} requirements")

    doc.h2("2.2 Non-Functional Requirements")
    doc.p(
        "The forty non-functional requirements are grouped into nine categories. Each statement "
        "describes a property of the delivered system that can be checked, and each is marked "
        "implemented because the mechanism that satisfies it is present in the code.")
    doc.table(
        ["Category", "Requirements", "Implemented", "Subject"],
        _nfr_summary(requirements),
        widths=[1.2, 1.0, 1.0, 3.3],
        caption="Non-functional requirements by category")

    nfr_sections = [
        ("2.2.1", "Security", "Security",
         "Security is enforced in the framework rather than in the interface. Passwords and profile "
         "passcodes are hashed one way, sessions are server-side and CSRF-protected, every "
         "non-public route requires authentication and every restricted route re-checks its role or "
         "gate. Uploaded documents sit on a private disk and are released only through a "
         "policy-checked download route, and sensitive actions are rate limited per client."),
        ("2.2.2", "Performance", "Performance",
         "Performance requirements are expressed against the seeded dataset on the reference "
         "hardware. The implementation supports them with indexes on the columns that filters and "
         "sorts use, pagination on every list view, and a queue for email so that no request waits "
         "on the mail transport."),
        ("2.2.3", "Usability", "Usability",
         "Every authenticated page shares one sidebar and one top bar, forms report errors beside "
         "the offending field and preserve what was typed, lists and charts describe their empty "
         "state, and every workflow status is rendered as a labelled badge rather than a raw "
         "database value."),
        ("2.2.4", "Reliability", "Reliability",
         "Multi-record operations run inside transactions, invalid status transitions are rejected "
         "without modifying the record, and notification jobs carry idempotency stamps so that a "
         "retry cannot duplicate an outbound message. Foreign keys keep ownership relationships "
         "intact."),
        ("2.2.5", "Maintainability", "Maintainability",
         "Presentation, validation, business rules and persistence are separated into Blade views, "
         "form requests, service classes and Eloquent models. Status values are defined once, "
         "recurring interface elements are Blade components, and the academic term is read from "
         "configuration rather than hard-coded."),
        ("2.2.6", "Compatibility", "Compatibility",
         "The application runs on PHP 8.2 or later with MariaDB or MySQL behind Apache or Nginx, "
         "and on current versions of the four major browsers. The delivered pages are served from "
         "one stylesheet and one script, so no JavaScript build step is required to use the "
         "system."),
        ("2.2.7", "Portability", "Portability",
         "The layout is usable from 360 to 1920 pixels and collapses the sidebar into a drawer "
         "below 1024 pixels. The system needs no commercial runtime, and database, mail and "
         "academic-term settings are supplied through environment configuration."),
        ("2.2.8", "Accessibility", "Accessibility",
         "Pages expose a main landmark, a skip link and labelled navigation; every control is "
         "keyboard operable with a visible focus indicator; status and alert regions carry roles "
         "that assistive technology can announce; colour pairs meet WCAG 2.1 AA contrast; and "
         "animation is suppressed when the user asks for reduced motion."),
        ("2.2.9", "Data", "Data",
         "Status history, audit entries and verification records are retained rather than "
         "overwritten, timestamps are stored in server time and rendered consistently, and the "
         "application and request numbers that identify a record are unique and stable for its "
         "lifetime."),
    ]
    for number, title, group, prose in nfr_sections:
        rows = [row for row in requirements
                if row["req_id"].startswith("NFR-") and row["group"] == group]
        doc.h3(f"{number} {title}")
        doc.p(prose)
        doc.table(
            ["ID", "Requirement", "Priority", "Status"],
            [[row["req_id"], f"**{row['title']}.** {row['description']}",
              row["priority"], row["status"]] for row in rows],
            widths=[1.0, 3.77, 0.7, 1.03],
            caption=f"{title} requirements")

    doc.h2("2.3 User Roles and Access Permissions")
    doc.p(
        "The permission matrix below is the contract between the roles and the interface. A filled "
        "entry states what the role may do and under which condition; a dash states that no route, "
        "gate or screen exists for that role, which is a stronger statement than a disabled button.")
    doc.table(
        ["Capability", "Student", "Counselor", "Administrator"],
        [
            ["Register an account", "Yes", "—", "—"],
            ["Log in, log out, remember session", "Yes", "Yes", "Yes"],
            ["Verify email by one-time passcode", "Yes", "Yes", "Yes"],
            ["Reset own password without signing in", "—", "—", "—"],
            ["View own profile", "Yes", "—", "Yes, any student"],
            ["Edit own profile", "Yes, four fields", "—", "Yes, any student"],
            ["Set, change or remove profile passcode", "Yes", "—", "Reset for any student"],
            ["Unlock a locked profile", "Yes", "—", "Yes"],
            ["Browse and search open scholarships", "Yes", "—", "Yes, management view"],
            ["View eligibility preview", "Yes", "—", "—"],
            ["View recommendations and match scores", "Yes", "—", "Configure weights only"],
            ["Create, edit, close or archive scholarships", "—", "—", "Yes"],
            ["Manage scholarship document requirements", "—", "—", "Yes"],
            ["Announce a scholarship to eligible students", "—", "—", "Yes"],
            ["Configure matching weights", "—", "—", "Yes"],
            ["Start an application and upload documents", "Yes, enrollment-gated", "—", "—"],
            ["Submit an application", "Yes, enrollment-gated", "—", "—"],
            ["Review applications and change status", "—", "—", "Yes"],
            ["Verify or reject uploaded documents", "—", "—", "Yes"],
            ["View enrollment verification records", "Own record only", "Yes", "Yes"],
            ["Record an enrollment decision", "—", "Yes", "Yes"],
            ["Publish guidance availability", "—", "Yes, own", "—"],
            ["Book a guidance appointment", "Yes, enrollment-gated", "—", "—"],
            ["Cancel a guidance appointment", "Yes, own", "—", "—"],
            ["Confirm, reject or complete an appointment", "—", "Yes, own appointments", "View only"],
            ["Manage appointment types and scheduling settings", "—", "—", "Yes"],
            ["View all appointments", "—", "Own only", "Yes"],
            ["Browse the service catalogue and submit a request", "Yes, enrollment-gated", "—", "—"],
            ["Track and cancel own service requests", "Yes", "—", "—"],
            ["Assign and process service requests", "—", "—", "Yes"],
            ["Manage service types and their requirements", "—", "—", "Yes"],
            ["Receive notifications", "Yes", "Yes", "Yes"],
            ["View analytics and export CSV", "—", "—", "Yes"],
            ["View the audit log", "—", "—", "Yes"],
            ["Create and deactivate user accounts", "—", "—", "Yes"],
            ["Reset another user's password", "—", "—", "Yes"],
        ],
        widths=[3.3, 1.25, 1.05, 1.1],
        caption="Role and permission matrix")

    doc.p(
        "Four mechanisms cooperate to enforce the matrix. Route middleware rejects a role mismatch "
        "with HTTP 403 for a whole route group. Named gates express shared capabilities such as "
        "`manage-users` and additionally require the account to be active. Policies compare the "
        "owner of a record with the signed-in user, which is what prevents one student from opening "
        "another student's document or appointment. The `enrollment.verified` middleware completes "
        "the chain by blocking enrollment-dependent student actions until a verification record "
        "exists for the current term.")
    doc.table(
        ["Role", "Enforcement points in the delivered code"],
        [
            ["Student",
             "`role:student` route group; `verified.email` middleware; `enrollment.verified` "
             "middleware on submission routes; `access-student-panel` gate; student-scoped search "
             "endpoints"],
            ["Counselor",
             "`role:counselor` route group; `access-counselor-panel` gate; "
             "`manage-enrollment-verifications` gate; `GuidanceAppointmentPolicy` ownership checks"],
            ["Administrator",
             "`role:admin` route group; `manage-users` and `access-admin-panel` gates; `active` "
             "middleware on analytics and notifications; `NotificationPolicy` grant"],
        ],
        widths=[1.15, 5.35],
        caption="Where each role is enforced")
    doc.note(
        "Navigation visibility is never the control. Every restricted route independently re-checks "
        "the role or gate on the server, so hiding a menu entry changes the interface and not the "
        "authorisation.")


# --------------------------------------------------------------------------
# Table builders
# --------------------------------------------------------------------------

def _module_summary(requirements):
    rows = []
    for code, name, _text, _status in MODULES:
        subset = [row for row in requirements
                  if row["req_id"].startswith("FR-") and row["module"] == code]
        counts = Counter(row["status"] for row in subset)
        prefixes = sorted({row["req_id"].split("-")[1] for row in subset})
        rows.append([f"{code} — {name}", " / ".join(prefixes), str(len(subset)),
                     str(counts.get("Implemented", 0)),
                     str(counts.get("Partial", 0)),
                     str(counts.get("Proposed", 0))])
    return rows


def _nfr_summary(requirements):
    subjects = {
        "Security": "Hashing, sessions, authorisation, private storage, upload validation, "
                    "throttling, auditability",
        "Performance": "Response time, indexes, pagination, queued email",
        "Usability": "Navigation, validation feedback, empty states, confirmations, badges, printing",
        "Reliability": "Transactions, idempotency, transition safety, referential integrity",
        "Maintainability": "Layering, single source of status values, components, configured term",
        "Compatibility": "Browsers, server platform, no client build step",
        "Portability": "Responsive layout, no proprietary runtime, environment configuration",
        "Accessibility": "Landmarks, keyboard operation, announcements, contrast, reduced motion",
        "Data": "Retained history, consistent timestamps, unique business identifiers",
    }
    order = ["Security", "Performance", "Usability", "Reliability", "Maintainability",
             "Compatibility", "Portability", "Accessibility", "Data"]
    rows = []
    for group in order:
        subset = [row for row in requirements
                  if row["req_id"].startswith("NFR-") and row["group"] == group]
        implemented = sum(1 for row in subset if row["status"] == "Implemented")
        rows.append([group, str(len(subset)), str(implemented), subjects[group]])
    return rows
