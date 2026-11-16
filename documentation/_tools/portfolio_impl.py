#!/usr/bin/env python3
"""portfolio_impl.py - Section 4 (implementation evidence) and Section 5 (resources).

Every figure in Section 4 is a browser capture of the running application. The
captions name the screen, the explanations state what the user can do and which
rule the screen enforces, and Table 4.2 maps the figures back to the modules of
Section 1 so that coverage can be checked at a glance.
"""

from __future__ import annotations


def build(doc, requirements) -> None:
    # ======================================================================
    # SECTION 4
    # ======================================================================
    doc.start_chapter(4)
    doc.h1("4. System Implementation")

    doc.p(
        "This section presents the delivered interface. Every figure is a screenshot taken from the "
        "running application, not from the wireframes or mockups in Section 3. Each screen is a route "
        "that exists in the application, reached with an account that holds the role which owns it, and "
        "each is explained in terms of what it displays and what the user can do on it. Where the "
        "delivered behaviour is incomplete, the gap is stated instead of being hidden behind a "
        "screenshot.")
    doc.p(
        "The capture session used an unmodified copy of the repository at commit 8270c54 and a clone of "
        "the application database, so the working environment of the project was not altered. Table 4.1 "
        "records the conditions. The copy existed only to keep the screenshot data separate from the "
        "database used for development.")

    doc.table(
        ["Item", "Record of the capture session"],
        [
            ["Application", "ISU OSAS Hub, repository commit 8270c54 (8 November 2026), copied without "
                            "modification"],
            ["Server", "PHP 8.2.12 built-in web server on 127.0.0.1:8123"],
            ["Database", "Clone of the application database, named `isu_osas_hub_shots`, holding the "
                         "seeded demonstration records"],
            ["Browser and viewport", "Headless Chrome 153, viewport 1440 by 980 pixels, device scale 1.5, "
                                     "desktop layout"],
            ["Accounts used", "One seeded account per role: student, counselor and administrator. "
                              "Passwords are not reproduced in this document."],
            ["Date of capture", "15 November 2026"],
            ["Output", "57 PNG files in `documentation/Portfolio/figures/screens/`, of which 53 are "
                       "distinct screens and 4 are full-page variants of long screens"],
        ],
        widths=[1.5, 5.0],
        caption="Environment and method used for the implementation screenshots")

    doc.p(
        "Two conventions apply throughout. First, each capture is the viewport, not the full page, so "
        "content below the fold is described in the explanation rather than shown. Second, the records "
        "are the seeded demonstration data: accounts named System Administrator, Dev Counselor and "
        "ALINDAYU FORTUNATO JR. H., student number 22-0634, and scholarship titles such as `ched-tdp` and "
        "`schooarships for all`. The spelling of the second title is the stored value and is deliberately "
        "left as it is; it is not a transcription error in this document.")

    # ----------------------------------------------------------------------
    doc.h2("4.1 System Core Feature Interfaces")
    doc.p(
        "The interfaces are presented in the order a reader would meet them: the entry point, the three "
        "dashboards, then the workflows grouped by the problem they solve. Each figure carries a number "
        "and a title, and the paragraph after it explains the purpose of the screen and how the user "
        "operates it. Table 4.2 at the end of the section maps every figure to the module it evidences.")

    # -- 4.1.1 -------------------------------------------------------------
    doc.h3("4.1.1 Login and Authentication Interface")
    doc.p(
        "The application has a single entry point for all three roles. The role is a property of the "
        "account, never a choice made on this page, so the same form serves a student, a counselor and an "
        "administrator and the destination after sign-in is decided on the server.")

    labels = {}

    def fig(name: str, caption: str, prose: str) -> str:
        label = doc.figure(f"figures/screens/{name}.png", caption)
        labels[name] = label
        doc.p(prose)
        return label

    fig("login", "Login screen, the single entry point for all three roles",
        "The form takes an email address and a password, offers a reveal control for the password and a "
        "remember-me option, and submits to one authentication route. Invalid credentials produce a "
        "single non-revealing message, the attempt is throttled per client, and the failed attempt is "
        "written to the audit log without the attempted password. A successful sign-in regenerates the "
        "session identifier, and an account that has been deactivated is refused here and again by "
        "middleware on every protected page. The only secondary action is self-registration.")

    fig("register", "Student self-registration form",
        "Registration is open to students only. The form collects the student number, full legal name, "
        "email address, college, program, contact number, GWA, annual family income, address, password "
        "and privacy consent. The email address is verified with a six-digit one-time passcode rather "
        "than a signed link, and the academic fields collected here are subsequently maintained by an "
        "administrator, not by the student. Counselor and administrator accounts cannot be created from "
        "this page; they are created by an administrator, which is why no role selector appears.")

    doc.p(
        "Two parts of the authentication flow are not reproduced as figures. The six-digit verification "
        "screen follows registration and is a single code field with a resend action, and the shared "
        "password-reset screens do not exist because the delivered system has no self-service reset. The "
        "login page also cannot show throttling, CSRF protection or session regeneration, because those "
        "mechanisms operate on the server; they are described in Sections 2.2.1 and 3.3.1 and are "
        "verifiable in the code rather than in a picture.")

    # -- 4.1.2 -------------------------------------------------------------
    doc.h3("4.1.2 Dashboard Interface")
    doc.p(
        "Each role lands on a dashboard that answers one question: what needs attention now. The three "
        "dashboards share the same shell and differ in the records they summarise, because the role "
        "determines which workflows the account can see.")

    fig("student-dashboard", "Student dashboard: counters, recommendations, next appointment and application track",
        "The page opens with the signed-in student's name, student number and program, then four counters "
        "for active applications, draft applications, service requests and unread alerts. Below them are "
        "the highest-ranked recommendation with its match score, the next guidance appointment with its "
        "status, the active application track and three shortcuts into the student workflows. Every block "
        "links to the record it summarises, so the dashboard is a starting point rather than a place to "
        "act. Two counter captions render the HTML entity literally, as `Submitted &amp; under review` and "
        "`Requires upload &amp; submission`; this is a display defect in the view layer, recorded with the "
        "submission notes and left uncorrected because the application source was outside the scope of "
        "this documentation task.")

    fig("counselor-dashboard", "Counselor dashboard: today's schedule, pending requests and published availability",
        "The counselor sees four counters, an empty state for today's appointments, the queue of requests "
        "awaiting a decision with Confirm and Review actions, and the weekly availability that generates "
        "bookable slots. The empty state is shown as the system renders it: a named condition and a "
        "sentence explaining it, not a blank panel. The quick actions repeat the three tasks a counselor "
        "performs most often.")

    fig("admin-dashboard", "Administrator dashboard: management actions, four operational panels, audit feed and appointment queue",
        "The administrator's dashboard is an operational summary of the whole office. Seven quick actions "
        "lead to the management screens; four panels report account counts, scholarship outcomes, the "
        "guidance queue and the student-service queue; and the two lower panels show the most recent audit "
        "entries and the nearest upcoming appointments. The figures are counts of live records, and each "
        "panel links to the list behind it, which is what makes the dashboard usable as a daily check "
        "rather than a static report.")

    # -- 4.1.3 -------------------------------------------------------------
    doc.h3("4.1.3 Core Functional Modules")
    doc.p(
        "The workflows below are grouped by the problem they solve rather than by role, because each one "
        "crosses roles: a scholarship passes from administrator to student to administrator again, and an "
        "appointment passes from counselor to student to counselor. The order follows the sequence of "
        "Section 1.3.2.")

    doc.h4("4.1.3.1 Scholarship Discovery, Eligibility and Matching")
    doc.p(
        "A student begins with the catalogue, narrows it with filters, opens one scholarship and asks the "
        "system whether the profile meets its criteria. Matching adds a ranked second opinion without "
        "changing the eligibility verdict.")

    fig("student-scholarships", "Scholarship catalogue with filters, eligibility badges and match scores",
        "Two open scholarships are shown as cards. Each card states the provider, the type, the "
        "eligibility verdict, the match score, the GWA and income thresholds, the deadline with a closing "
        "warning, and, when an application already exists, its status. The filter row supports keyword, "
        "type, program, year level, deadline, eligibility and sort order, and the result count sits above "
        "the list. The student acts through View Details, Check Eligibility and, where the window is open, "
        "Apply Now.")

    fig("student-scholarship-search", "Catalogue empty state when a search returns no scholarship",
        "When a query matches nothing, the page keeps the search and filter controls in place and shows a "
        "named empty state with a Clear All Filters action. The state is deliberate: it tells the student "
        "that the query ran and the result set is empty, rather than leaving a blank region that could be "
        "mistaken for a loading failure.")

    fig("student-scholarship-show", "Scholarship detail with period, criteria and required document checklist",
        "The detail page states the provider, type, application period, availability status and "
        "description, then lists the general eligibility criteria and the required document checklist. "
        "The criteria are the same values the eligibility engine reads, so the student can compare them "
        "with the profile before applying. The two actions lead to the eligibility preview and to the "
        "student's own application.")

    fig("student-eligibility", "Eligibility preview with a criterion-by-criterion explanation",
        "The preview returns a single verdict and then explains it criterion by criterion. Each criterion "
        "shows met, not met or not applicable, the student's value, the required value and a sentence that "
        "connects them. The disclaimer states that the result reflects the configured criteria and the "
        "profile data, and that final approval depends on document verification and administrative review; "
        "the screen supports a decision rather than making it.")

    fig("student-recommendations", "Recommended scholarships ranked by weighted match score",
        "Recommendations are grouped by whether the student is eligible. Each entry shows its rank, the "
        "match score, the five factor chips and the deadline, and View Reasons expands the explanation of "
        "the score. The disclaimer repeats that a high score is not an approval and that the final decision "
        "rests on the complete application. The student can open the scholarship or start an application "
        "from the card.")

    fig("student-matches", "Full match list separating eligible from not-currently-eligible scholarships",
        "The full list is the unfiltered view behind the recommendation cards. It separates eligible "
        "scholarships from those the student cannot currently obtain, repeats the score and rank, and "
        "lists the criteria the profile already satisfies so that the reason for the ranking is visible. "
        "When every available scholarship is eligible, the second group shows a positive empty state "
        "instead of disappearing.")

    doc.h4("4.1.3.2 Application and Document Workflow")
    doc.p(
        "The application is the record that carries a student from an open scholarship to a decision. It "
        "has two states that matter to the interface: while it is a draft the student can upload and "
        "replace documents, and once it is submitted the documents become read-only while an administrator "
        "moves the record through review.")

    fig("student-applications", "Student's application list with status and submitted date",
        "The list shows one row per application with the application number, the scholarship, the current "
        "status and the submission date, and a search field and status filter above it. The action opens "
        "the application. The number is the identifier the student can quote to the office, and it is "
        "stable for the life of the record.")

    fig("student-application-show", "Submitted application with progress stepper, document verification state and status history",
        "The page shows the application number, a stepper for Submitted, Under Review, Document "
        "Verification, Approved and Rejected, the scholarship and provider, and the submission timestamp. "
        "The documents table lists each requirement with its file, its verification state and a locked "
        "action, because a submitted application no longer accepts replacement files. The status history "
        "below records every transition with its timestamp and the sentence that explains it, which is the "
        "part of the screen that makes the workflow auditable.")

    fig("admin-applications", "Administrator review queue for scholarship applications",
        "The queue lists applications with their number, the student and student number, the program, the "
        "scholarship, the submission timestamp and the status, and it can be filtered by free text and "
        "status. Review opens the record. The queue is the administrator's working list: it is ordered so "
        "that pending work is reachable without opening each application in turn.")

    fig("admin-application-show", "Application review with per-document verification and workflow transition controls",
        "The review screen repeats the student's identity and academic facts, the scholarship and its "
        "status, and then the documents table with a verification control, a remarks field and an update "
        "action for each row. The workflow control offers the transitions the state machine allows. "
        "Approval is refused on the server unless every required document has been verified, so the "
        "interface can present the transition without becoming the guard; the rule is enforced in the "
        "service layer and recorded in the audit log.")

    doc.h4("4.1.3.3 Guidance and Counseling")
    doc.p(
        "Guidance scheduling is computed rather than negotiated. The counselor publishes the hours that "
        "can be booked, the student books one of the slots that result, and the counselor decides the "
        "request. Every step is a record with a status, so a promise made in conversation cannot replace "
        "the schedule.")

    fig("student-counselors", "Counselor directory with active days and entry to the slot list",
        "The directory lists the counselors a student may consult, with the days on which each has active "
        "availability and a View Available Slots action. The search field narrows the list by name or email "
        "address. The page is the entry point to booking; it does not offer a free-form appointment.",
        )

    fig("student-slots", "Generated appointment slots for a chosen date and type, each bookable with a reason",
        "The student chooses a date and an appointment type, and the page returns the slots that the "
        "counselor's published availability, the office hours, the minimum notice and the booking horizon "
        "allow. Each slot carries an optional reason field and a Book This Slot action. Because the time "
        "is chosen from generated slots rather than typed, a student cannot create an appointment outside "
        "the published window; a slot that is taken disappears from the list instead of failing on submit.")

    fig("student-appointments", "Student's appointment list with status and a link to each record",
        "The list shows the date, time, counselor, type and status of every appointment, with a search "
        "field and a Book New Appointment action. The status badge is the same vocabulary the counselor "
        "and administrator see, so a student and the office cannot describe the same appointment "
        "differently.")

    fig("student-appointment-show", "Appointment detail with the student's reason and the cancellation action",
        "The detail page repeats the counselor, type, date, time and status and shows the reason the "
        "student wrote, which is the information the counselor reads before deciding. The Cancel "
        "Appointment action is offered only while the appointment can still be cancelled under the "
        "configured notice period; after that point the server refuses the transition, so the control is "
        "withheld rather than shown and rejected.")

    fig("counselor-appointments", "Counselor appointment queue with a status filter",
        "The counselor sees the appointments assigned to them with the date, time, student, type, status "
        "and a Manage action, filtered by free text or status. The queue is scoped to the signed-in "
        "counselor by the record policy, so another counselor's appointments are neither listed nor "
        "reachable by changing the identifier in the address.")

    fig("counselor-appointment-show", "Counselor decision screen with student information, reason and confirm or reject actions",
        "The decision screen places the student's program, the schedule, the type and the student's reason "
        "on one page, with a Confirm Appointment and a Reject Appointment action. A rejection requires a "
        "reason that the student will read, and only the transitions allowed by the appointment state "
        "machine are offered. The decision is written to the audit log and produces a notification for the "
        "student.")

    fig("counselor-availability", "Weekly availability editor with office-hour and overlap validation",
        "The counselor adds a window by choosing a day, a start time and an end time. The form states the "
        "two rules the server enforces: the window must fall inside office hours and must not overlap an "
        "existing window. The table lists the active windows and offers Deactivate for each, so "
        "availability is withdrawn without deleting the record that explains past bookings.")

    doc.h4("4.1.3.4 Student Services")
    doc.p(
        "The service catalogue is maintained by the office and consumed by students. A request is numbered "
        "on submission, assigned to a handler, and moved through a workflow whose history the student can "
        "read.")

    fig("student-services", "Student services catalogue with per-type document checklists",
        "The catalogue presents each service as a card with its description, the documents it requires and "
        "a Request This Service action. The checklist is part of the card because the requirement is part "
        "of the service definition, not a surprise revealed after submission. The search field filters the "
        "catalogue by name, description or instructions.")

    fig("student-service-create", "Request form generated from the selected service type",
        "The form is generated from the service type. The description is always required, and a file input "
        "appears for every document the type declares; the certificate type shown here declares none, so "
        "only the description is requested. Submitting assigns the request number, records the student and "
        "the type, and creates the first entry in the status history.")

    fig("student-service-index", "Student's request list with request numbers and current status",
        "The list shows the request number, the service type, the submission date, the status and a Track "
        "Details action, with a search field above it. The request number is the reference the student "
        "quotes to the office, and the status is the same value the administrator sees.")

    fig("student-service-show", "Request detail with status timeline, official remarks, description and attached documents",
        "The page states the request number, category, status and timestamps, the remarks the office "
        "intended the student to read, the student's own description and an append-only status timeline. "
        "Attached documents are listed with a download action that is authorised by policy. Cancel Request "
        "is offered while the workflow still permits cancellation; the button is not a substitute for the "
        "server-side check.")

    fig("admin-service-types", "Service type administration with document requirements and request counts",
        "The administrator maintains the catalogue here: order, category name, status and total requests, "
        "with Edit and Deactivate actions and a separate Add Service Type form. The request count shows "
        "which types are actually used, and deactivation removes a type from the student catalogue without "
        "deleting the requests already filed under it.")

    fig("admin-service-requests", "Administrator service request queue with handler, status and type filters",
        "The queue shows the request number, student and student number, service type, assigned handler, "
        "status, submission date and a Review action, with filters for text, type, status and handler. It "
        "is the office's working list: an unassigned request is visible as unassigned rather than as a "
        "request without an owner.")

    fig("admin-service-request-show", "Request processing screen with handler assignment, document checklist and status update",
        "The processing screen repeats the student's details and description, lists the supporting "
        "documents with an authorised download, and shows the request timeline with the actor and remarks "
        "for each entry. The administrator assigns a handler, which is recorded, and updates the status "
        "through a target-state control whose remarks are explicitly labelled as student-visible. The "
        "workflow service rejects a transition that the state machine does not allow.")

    doc.h4("4.1.3.5 Profile and Profile Security")
    doc.p(
        "A student profile is a separate record joined one to one with the account. The profile carries "
        "the academic, contact and financial facts that eligibility and matching read, which is why the "
        "student can correct some of them and not others, and why the sensitive ones can be hidden behind "
        "a passcode.")

    fig("student-profile", "Student profile with academic identity, protected contact and financial fields, and enrollment status",
        "The left card holds the academic identity and the enrollment status; the right card holds the "
        "email address and the four protected values, shown masked because the profile is locked. The "
        "explanatory text states which fields the student maintains and which the office controls. The "
        "Edit Profile action changes only the four student-editable fields, and the security card below "
        "unlocks the masked values.")

    fig("student-profile-security", "Profile security page for unlocking the profile and changing the passcode",
        "The page separates two operations that are easy to confuse: unlocking a locked profile for the "
        "current session, and changing the passcode itself. It states which details the passcode protects, "
        "warns that the passcode must not repeat the account password, and offers the removal of protection "
        "as a distinct action. The passcode is hashed with a one-way algorithm and is never returned to the "
        "browser, so the page can confirm a match but cannot reveal the stored value.")

    doc.h4("4.1.3.6 User, Profile and Enrollment Administration")
    doc.p(
        "The administrator maintains the accounts and profiles that the rest of the system depends on, and "
        "decides enrollment verification for the current term. These screens are the only place where a "
        "counselor account or an academic identity field can be changed.")

    fig("admin-users", "User management list with role, enrollment, account status and account actions",
        "The list shows each account's name, email, role, student number where relevant, enrollment state, "
        "account status and creation date, with Edit, Deactivate and Reset PW actions. The role badge is "
        "the stored role, and the enrollment badge is a derived state, not a second role. Filters cover "
        "name, email, role and status.")

    fig("admin-user-create", "Administrator account creation form with role and active-account control",
        "The form collects the full name, email address, role, initial password and the active flag. It is "
        "the only route that can create a counselor or administrator account, which is why the role "
        "selector exists here and not on the public registration page. The password is hashed on save and "
        "the account is subject to the same authentication rules as any other.")

    fig("admin-profiles", "Student profile list with academic, enrollment and account state",
        "The administrator's profile list shows the student number, name, college and program, year level, "
        "enrollment state and account state, with View and Edit actions. It is a directory of the student "
        "records that scholarships, appointments and service requests refer to, and it is filterable by "
        "college, year level and enrollment so that a term's cohort can be reviewed.")

    fig("admin-profile-show", "Administrator view of a locked profile with the unlock and passcode-reset controls",
        "The administrator sees the academic identity in full and the protected contact and financial "
        "values masked, because the student set a passcode. The page can unlock the values with the "
        "student's passcode, or reset the passcode when it has been forgotten; both actions are recorded. "
        "This is the mechanism that keeps an administrator from reading private details without a trace, "
        "while still allowing recovery.")

    fig("admin-enrollments", "Enrollment verification queue with academic year, semester and status filters",
        "The queue shows the student number, name, email, academic year, semester, status, submission date "
        "and a View or Review action, filtered by text, status, year and semester. The term is part of the "
        "record, so a decision applies to a specific enrollment period rather than to the student in "
        "general.")

    fig("admin-enrollment-show", "Enrollment verification decision with current status, decision control and history",
        "The detail page shows the student's identity and current enrollment status, the academic year and "
        "semester being verified, a decision control and a notes field. A reason is required when the "
        "decision is Rejected or Unable to Verify, and the history table records who decided, when and why. "
        "The queue and detail page therefore support the whole decision, including the explanation a "
        "student may later ask for.")

    doc.note(
        "Recorded gap. The queue and the decision screen exist, but the delivered build has no route or "
        "interface that creates a new enrollment-verification record; the entries shown were created by "
        "the seeded data. The module is therefore classified as partially implemented in Sections 1.3.2 and "
        "2.1.5, and no screenshot is presented as evidence of an intake screen that is absent.")

    doc.h4("4.1.3.7 Scholarship Administration and Matching Configuration")
    doc.p(
        "The scholarship screens are where the office defines the rules that the student-facing engine "
        "later applies. The thresholds entered here are the values the eligibility preview and the match "
        "score read, so the two sides of the system cannot drift.")

    fig("admin-scholarships", "Scholarship management list with lifecycle actions and application counts",
        "The list shows each scholarship's title, provider, type, application count, deadline and status, "
        "with Edit, Requirements, Notify Eligible and Activate or Deactivate actions. The application "
        "count is a live count, and the status badge distinguishes a draft from an open scholarship, which "
        "is what keeps an unfinished record out of the student catalogue.")

    fig("admin-scholarship-create", "Scholarship authoring form with basic information, eligibility rules and application settings",
        "The form is divided into basic information, eligibility criteria and application settings. The "
        "criteria section names the engine rules it writes: minimum GWA, maximum family income, eligible "
        "programs, eligible year levels and required enrollment status. Leaving programs or year levels "
        "blank opens the scholarship to all of them, and the helper text says so, which prevents an empty "
        "field from being read as an exclusion.")

    fig("admin-scholarship-requirements", "Document requirement editor with order, required flag and document counts",
        "The administrator adds a requirement by name, order and required flag, with an optional "
        "description, and the current list shows how many documents have been filed against each "
        "requirement. A required requirement becomes a file input on the student's application and a row "
        "in the verification table, so this screen is the source of both.")

    fig("admin-weights", "Matching weight configuration with a live total",
        "The five factors that produce a match score are exposed as percentages: academic qualification, "
        "financial eligibility, program match, year-level match and document readiness. The current total "
        "is recomputed from the fields, and the weights must total exactly 100 before they can be saved. "
        "Exposing the weights makes the ranking a stated trade-off rather than an unexplained number.")

    doc.h4("4.1.3.8 Guidance Administration")
    doc.p(
        "Guidance settings and appointment types determine which slots can exist. The administrator "
        "configures the frame; the counselor publishes availability inside it; the student books inside "
        "that. The administrator's appointment list is an oversight view and carries no decision controls.")

    fig("admin-guidance-types", "Appointment type administration with duration override, order and status",
        "The administrator adds an appointment type with a name, optional description, an optional duration "
        "override and a display order, and the list shows each type with its effective duration, status and "
        "a Deactivate action. The duration override is what allows a type such as Personal Concern to run "
        "for 45 minutes while the global default remains 30.")

    fig("admin-appointments", "Administrator guidance appointment oversight list",
        "The list shows the date, time, counselor, student, type, status and a View action, with filters "
        "for text, counselor and status. It is read-only oversight: the administrator can see the schedule "
        "and open a record, but the decision belongs to the counselor, which is why no confirm or reject "
        "control appears here.")

    fig("admin-appointment-show", "Read-only administrator view of a scheduled guidance appointment",
        "The detail page repeats the student, counselor, status, appointment type, schedule and the "
        "student's reason, with no action controls. It exists so that the office can answer a question "
        "about a booking without borrowing the counselor's account, and the absence of controls is "
        "consistent with the permission matrix in Section 2.3.")

    fig("admin-guidance-settings", "Guidance scheduling settings for office hours, duration, notice and booking horizon",
        "The form sets the office start and end time, the default duration, the minimum booking notice, the "
        "student cancellation notice and the maximum future booking period. These five values are the "
        "inputs that the slot generator uses, together with the counselor's availability, so changing one "
        "of them changes the slots offered on the student side immediately.")

    # -- 4.1.4 -------------------------------------------------------------
    doc.h3("4.1.4 Reports and Other Essential Features")
    doc.p(
        "The remaining interfaces are the ones that turn the records into management information and "
        "accountability: the analytics views and their CSV export, the audit log, and the notification "
        "centre. They are grouped here because they read the other modules rather than owning a workflow "
        "of their own.")

    fig("admin-analytics", "Analytics dashboard with period filter, four tabs and live summary tiles",
        "The dashboard reports the three workflows in one view. The filter bar sets a date range and can "
        "narrow by scholarship, appointment type and service type; the tabs separate the overview from the "
        "scholarship, guidance and student-service detail. The tiles and charts are counts and rates over "
        "the filtered period, each chart has a View data table control that exposes the numbers behind it, "
        "and the note at the foot of the page states that the metrics are descriptive rather than "
        "predictive.")

    fig("admin-analytics-scholarships", "Scholarship analytics: approval and rejection rates, applications by scholarship and monthly volume",
        "The scholarship tab reports the approval rate, rejection rate, pending count and total decided, "
        "then breaks applications down by scholarship and by month. Rates are computed only from decided "
        "applications, so pending records do not dilute them. Export CSV writes the figures on the tab, "
        "which lets the office reuse them in its own reports without retyping.")

    fig("admin-analytics-guidance", "Guidance analytics: appointment outcomes, volume by type and monthly trend",
        "The guidance tab reports completed, cancelled and pending appointments, the volume by appointment "
        "type split by outcome, and the monthly trend over the selected period. The type breakdown answers "
        "a question the appointment queue cannot answer directly: which kind of concern the office is "
        "actually seeing.")

    fig("admin-analytics-services", "Student services analytics: processing time, request volume by type and monthly trend",
        "The service tab reports completed requests, the pending queue and the average processing time in "
        "hours and days, then breaks requests down by service type and by month. The processing time is "
        "derived from the timestamps on completed records, not from a manually entered figure, and the "
        "generation timestamp on the header shows when the view was computed.")

    fig("admin-audit", "System audit log with filters, actor, module, record and client address",
        "The audit log lists 171 records in this dataset with the timestamp, user, action, module, record, "
        "description, IP address and a View action, filterable by text, user, module, action, record type "
        "and date range. The heading states that the log is append-only and that records cannot be edited "
        "or deleted, and no route exists to do either.")

    fig("admin-audit-show", "Audit entry detail with acting user, record identifier, description, address and user agent",
        "The detail page expands one entry into the timestamp, the acting user and email, the action and "
        "module, the record type and identifier, the written description, the IP address and the user "
        "agent. It is the evidence a reviewer needs to reconstruct who did what, and it is read-only by "
        "design.")

    fig("student-notifications", "Notification centre with unread count, categories and deep links to the record",
        "Notifications are written to the database for in-application display and queued for email. The "
        "centre shows the unread count, a Mark All as Read action, a search field and one card per "
        "notification with its category, the sentence that explains the event, the related record "
        "reference and a link to it. The same unread count appears in the top bar on every page, so the "
        "notification is not confined to this screen.")

    fig("admin-notifications", "Administrator notification centre with registration alerts",
        "The administrator receives the same notification interface with administrator events, such as a "
        "new student registration that is awaiting email and enrollment verification. The category and the "
        "wording identify the event, and the link leads to the record the administrator needs to act on. "
        "Unread and read states persist per account, which is why the same centre can serve all three "
        "roles.")

    doc.p(
        "The interface has limits that the figures make visible. The audit list in Figure 4.50 is long and "
        "paginated, so the screenshot shows the first page rather than the full history. The analytics "
        "figures describe the seeded dataset, which is small; the layout and the calculations are the "
        "delivered behaviour, but the values would change with production data. The notification centre "
        "shown here is the student and administrator variant; a counselor sees the same interface with "
        "guidance events.")

    doc.note(
        "Evidence that is described but not reproduced: the six-digit email verification screen, the "
        "document upload control (available only while an application is still a draft), and the "
        "administrator's user-edit form. The sampled application in Section 4.1.3.2 had already been "
        "approved, so its documents were read-only and no upload control was present to capture. These are "
        "not claimed as visual evidence anywhere in this portfolio.")

    # -- coverage table ----------------------------------------------------
    def refs(*names: str) -> str:
        return ", ".join(labels[name].replace("Figure ", "") for name in names)

    doc.table(
        ["Module", "Module name", "Figures", "What the figures establish"],
        [
            ["M01", "Authentication and account lifecycle",
             refs("login", "register"),
             "One role-neutral entry point, student-only self-registration and the fields that seed a "
             "student profile"],
            ["M02", "Access control",
             refs("student-dashboard", "counselor-dashboard", "admin-dashboard"),
             "Three different shells and navigation sets for the three roles, proving that authorisation "
             "is reflected in the interface as well as enforced on the route"],
            ["M03", "Student profiles and user management",
             refs("student-profile", "student-profile-security", "admin-users", "admin-user-create",
                  "admin-profiles", "admin-profile-show"),
             "Student-maintained and administrator-maintained fields, account creation and the role badge"],
            ["M04", "Profile security",
             refs("student-profile", "student-profile-security", "admin-profile-show"),
             "Masked protected values, unlock and change-passcode controls, and administrative reset"],
            ["M05", "Enrollment verification",
             refs("admin-enrollments", "admin-enrollment-show"),
             "Per-term queue and decision screen with history; the missing intake route is stated in the "
             "note above"],
            ["M06", "Scholarship management",
             refs("student-scholarships", "student-scholarship-show", "admin-scholarships",
                  "admin-scholarship-create", "admin-scholarship-requirements"),
             "Authoring, lifecycle, window, criteria and document requirements, and the resulting student "
             "catalogue"],
            ["M07", "Eligibility evaluation",
             refs("student-eligibility"),
             "A verdict plus a criterion-by-criterion explanation with the student's value and the "
             "threshold"],
            ["M08", "Matching and recommendations",
             refs("student-recommendations", "student-matches", "admin-weights"),
             "Ranked recommendations, the full eligible and ineligible list, and the weights that produce "
             "the score"],
            ["M09", "Application workflow",
             refs("student-applications", "student-application-show", "admin-applications",
                  "admin-application-show"),
             "Draft-to-decision states, the submitted record, the review queue and the transition controls"],
            ["M10", "Document management",
             refs("student-application-show", "admin-application-show"),
             "Per-requirement document rows, verification state, locked actions after submission and "
             "authorised download"],
            ["M11", "Guidance and counseling",
             refs("student-counselors", "student-slots", "student-appointments", "student-appointment-show",
                  "counselor-appointments", "counselor-appointment-show", "counselor-availability",
                  "admin-guidance-types", "admin-appointments", "admin-appointment-show",
                  "admin-guidance-settings"),
             "Generated slots, booking, counselor decisions, availability and the settings that constrain "
             "them"],
            ["M12", "Student services",
             refs("student-services", "student-service-create", "student-service-index", "student-service-show",
                  "admin-service-types", "admin-service-requests", "admin-service-request-show"),
             "Catalogue, generated request form, numbered request, timeline, handler assignment and status "
             "updates"],
            ["M13", "Notifications",
             refs("student-notifications", "admin-notifications"),
             "Unread state, categories, event wording and deep links for two roles"],
            ["M14", "Analytics and reporting",
             refs("admin-analytics", "admin-analytics-scholarships", "admin-analytics-guidance",
                  "admin-analytics-services"),
             "Descriptive metrics over the three workflows, period filtering, data tables and CSV export"],
            ["M15", "Audit logging",
             refs("admin-audit", "admin-audit-show"),
             "Append-only log, filters, actor and record identifiers, address and user agent"],
            ["M16", "Search and navigation",
             refs("student-scholarships", "student-scholarship-search", "student-counselors",
                  "admin-service-requests"),
             "Search fields on the list surfaces, the empty state, and the shared shell that carries the "
             "role-scoped search and notification count"],
        ],
        widths=[0.55, 1.45, 1.65, 2.85],
        caption="Evidence coverage by functional module")

    doc.p(
        "The coverage table is the bridge between this section and the rest of the portfolio. A module "
        "that appears with several figures is evidenced at more than one step of its workflow, while a "
        "module with a single figure is evidenced at the point where its behaviour is observable. The "
        "partial classification of M05 and M12 is repeated here rather than hidden, because a portfolio "
        "that overstates coverage is less useful to a reviewer than one that names the gap.")

    # ======================================================================
    # SECTION 5
    # ======================================================================
    doc.start_chapter(5)
    doc.h1("5. Project Resources and Repository Links")

    doc.p(
        "This section gives the reviewer the means to inspect the design, the source code and the running "
        "system. Each item that the portfolio instructions request is listed with its actual status. Where "
        "a resource does not exist, the absence is stated and the nearest available substitute is named; "
        "no address has been invented to fill a required field.")

    doc.table(
        ["Requested resource", "Status on 15 November 2026", "What the group supplies"],
        [
            ["Figma design link", "Not available",
             "No Figma project exists. The high-fidelity mockups and the design system are supplied as "
             "static HTML with PNG exports (Section 5.1)."],
            ["GitHub repository link", "Available",
             "Public web address of the repository and the verified commit (Section 5.2)."],
            ["Deployed system link", "Not applicable",
             "No public deployment exists. The verified environment is the development host in Section "
             "3.3.2 (Section 5.3)."],
            ["Demo credentials", "Not applicable",
             "No credentials are published, because no hosted environment exists for them to open."],
            ["Documentation set", "Available with this portfolio",
             "SRS, UML, wireframes, mockups and requirements traceability, submitted alongside this "
             "document."],
        ],
        widths=[1.5, 1.45, 3.55],
        caption="Project resources and their submission status")

    # ----------------------------------------------------------------------
    doc.h2("5.1 Figma Design Link")
    doc.p(
        "The portfolio instructions ask for a Figma URL. No Figma file was produced for this project, and "
        "none can be supplied. The interface was designed as static HTML and CSS generated from "
        "declarative page specifications and rendered to PNG with headless Chrome; the design system page "
        "in the mockup deliverable documents the colour, type, spacing and component tokens in the same "
        "way a Figma library would. Presenting the HTML mockups as a Figma project, or supplying a "
        "plausible-looking Figma address, would misrepresent both the design process and the evidence.")

    doc.p("The substitutes are complete and can be opened without a server or an account.")
    doc.table(
        ["Substitute", "Location", "What the reviewer can inspect"],
        [
            ["Mockup gallery", "`documentation/UI-UX-Mockups/index.html`",
             "All 46 high-fidelity screens, grouped by role, with the page specifications they were "
             "generated from"],
            ["Design system", "`documentation/UI-UX-Mockups/design-system.html`",
             "The 14-section specification of colour, typography, spacing, components and states, with "
             "every token traced to `public/css/app.css`"],
            ["Static exports", "`documentation/UI-UX-Mockups/export/`",
             "One PNG per screen, at desktop, tablet and phone widths"],
            ["Wireframe set", "`documentation/Wireframes/index.html`",
             "The 58 low-fidelity screens that establish the information architecture and control "
             "placement before visual design"],
            ["Design basis", "`documentation/UI-UX-Mockups/index.md`",
             "The scope note that states which parts of the interface are delivered and which are proposed"],
        ],
        widths=[1.35, 2.1, 3.05],
        caption="Design resources supplied in place of a Figma link")

    doc.p(
        "The reviewer should read the mockups as a restatement of the delivered interface in a consistent "
        "visual language, not as a redesign proposal. They use the colour, type and component tokens that "
        "the application stylesheet already defines, and Section 4 is the evidence that the running system "
        "matches them in structure. If the group creates a Figma file before submission, the URL belongs "
        "in this subsection and Table 5.2 should be replaced; until then, this is the accurate record.")

    # ----------------------------------------------------------------------
    doc.h2("5.2 GitHub Repository Link")
    doc.p(
        "The application source is held in a Git repository whose remote is verified on the development "
        "machine. The web address below is the browser address of that remote; it is not a guess and it "
        "does not depend on a local path.")

    doc.table(
        ["Repository fact", "Verified value"],
        [
            ["Web address", "https://github.com/superjynx/ISU-OSAS-Hub"],
            ["Remote configured as", "`origin` — https://github.com/superjynx/ISU-OSAS-Hub.git (fetch and "
                                     "push)"],
            ["Default branch", "`main`"],
            ["Commit reported in this portfolio", "`8270c54` — the full identifier is "
                                                 "`8270c54ffd4278de49338cb109b6b1da0cffb7ec`"],
            ["Commit date", "8 November 2026, 22:22:20 +0800"],
            ["Commit subject", "Improve user management UI and functionality"],
            ["Application source in the commit", "`app/`, `routes/`, `resources/views/`, `database/`, "
                                                 "`public/`, `tests/`, `composer.json`"],
            ["Documentation set", "Not part of that commit. The portfolio and the accompanying documents "
                                  "are submitted separately and are untracked in the working tree."],
        ],
        widths=[2.05, 4.45],
        caption="Repository facts verified on the development machine")

    doc.p(
        "Two access conditions must be confirmed by the group before submission, because this document "
        "cannot verify them from the development machine. If the repository is private, the instructor "
        "must be added as a collaborator or the repository must be made public; and if the address above "
        "differs from the address the instructor is expected to use, the correct one must replace it here. "
        "The commit named in Table 5.2 is the state that every statement in Sections 1 to 4 describes, so a "
        "reviewer who reads a later commit may find changes that this portfolio does not cover.")

    # ----------------------------------------------------------------------
    doc.h2("5.3 Deployed System Link")
    doc.p(
        "The system is not deployed on a public address. The only environment that has been verified is "
        "the single development host described in Section 3.3.2: the PHP built-in server running Laravel "
        "12 on port 8000, MariaDB on the same machine, private document storage on the local disk and the "
        "log transport standing in for SMTP. That host is not reachable from outside the machine, so a URL "
        "would either be invalid or would disclose an address that does not serve the system.")

    doc.table(
        ["Deployment item", "Status", "Explanation"],
        [
            ["Public URL", "None", "No virtual host, domain name, certificate or hosting account has been "
                                   "provisioned."],
            ["Verified environment", "One Windows workstation",
             "Application server, database, file store and log on a single host, as drawn in Figure 3.4."],
            ["HTTPS", "Not configured", "Traffic is loopback HTTP; the target design in Figure 3.5 places "
                                        "the application behind HTTPS on a campus network."],
            ["Background worker", "Not running as a service",
             "Queued mail and the hourly reminder command require a supervised worker and scheduler, which "
             "the development host does not provide."],
            ["SMTP account", "Not configured", "Mail is written to the log, which is why verification codes "
                                               "and notifications are visible in `storage/logs/laravel.log` "
                                               "rather than in an inbox."],
            ["Demo credentials", "Not published",
             "The seeded accounts exist only on the development host; publishing their passwords would "
             "create a false expectation that a reviewer can sign in."],
        ],
        widths=[1.35, 1.55, 3.6],
        caption="Deployment status and the reason for each entry")

    doc.p(
        "Evaluation is therefore a demonstration on the development host rather than a visit to a URL. If a "
        "campus deployment is provisioned later, four things must be added to this section: the public "
        "address, the deployment commit, a demonstration account that is not a personal account, and a "
        "statement of which services — database, queue worker, scheduler and SMTP — are running. The "
        "credentials should be issued for the evaluation and rotated afterwards, and they should never be "
        "the personal passwords of the developers.")

    doc.note(
        "Nothing in this section is a placeholder for a link that the group has. The Figma and deployment "
        "entries are accurate statements of absence, not incomplete fields. Before submission the group "
        "should confirm repository visibility, add the author addresses and email addresses requested on "
        "the cover, and decide whether to create a Figma file or to submit the HTML mockups as the design "
        "record.")
