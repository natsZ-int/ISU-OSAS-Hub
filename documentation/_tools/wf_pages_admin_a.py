"""Wireframe page specs: administrator screens, part A (WF-A-01 … WF-A-13)."""

PAGES = [
    dict(
        id="WF-A-01", title="Administrator dashboard", role="admin", folder="admin",
        nav_active="admin.dashboard", route="admin.dashboard",
        module="M03, M06, M09, M11, M12, M14",
        reqs="FR-ANLYT-01, NFR-PERF-01",
        purpose="Summarise the health of every OSAS function on one screen so the administrator "
                "can see backlogs and volumes before opening an individual queue.",
        annotations=[
            "Six summary tiles cover students, open scholarships, live applications, pending "
            "appointments, open service requests and enrollments awaiting verification.",
            "Two charts summarise application outcomes and service-request status for the "
            "current term; both link to the corresponding analytics tab.",
            "Each backlog row links directly to its queue with the relevant filter pre-applied.",
            "All figures are computed by the analytics service with the same filters used on the "
            "analytics screens, so numbers never disagree.",
            "The dashboard exposes no bulk actions; every change happens on the owning screen.",
            "Quick actions cover the three most frequent administrative starts: a new "
            "scholarship, a new user and the review queue.",
        ],
        responsive=[
            ("Desktop 1440", "Six tiles in two rows of three, then a 2:1 split of charts and "
                             "backlogs."),
            ("Tablet 1024", "Tiles in two columns; charts stack above backlog rows."),
            ("Mobile 390", "Single column; tiles become compact rows with the number right-aligned."),
        ],
        blocks=[
            {"type": "header", "title": "OSAS overview",
             "subtitle": "A.Y. 2025–2026, second semester · figures as of 15 Nov 2026, 08:00",
             "actions": [("Open analytics", "primary"), ("New scholarship", "ghost")]},
            {"type": "stats", "cols": 3, "items": [
                ("Registered students", "1,284", "42 awaiting enrollment verification"),
                ("Open scholarships", "4", "2 close within 14 days"),
                ("Live applications", "96", "31 awaiting document verification"),
                ("Pending appointments", "12", "Oldest waiting 1 day"),
                ("Open service requests", "27", "5 awaiting student information"),
                ("Enrollments to verify", "42", "Across 6 colleges"),
            ]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "chart", "kind": "bars", "title": "Application outcomes this term",
                 "sub": "Counts by current status across all open and closed scholarships",
                 "labels": ["Draft", "Submitted", "Under review", "Doc. verification",
                            "Approved", "Rejected"],
                 "values": [18, 24, 31, 23, 41, 9]},
                {"type": "grid", "cols": 2, "items": [
                    {"type": "chart", "kind": "donut", "title": "Service requests",
                     "sub": "By current status",
                     "labels": ["Submitted", "Processing", "Requires info", "Completed"],
                     "values": ["8", "11", "5", "34"]},
                    {"type": "chart", "kind": "donut", "title": "Appointments",
                     "sub": "By current status",
                     "labels": ["Requested", "Confirmed", "Completed", "Cancelled"],
                     "values": ["12", "27", "63", "8"]},
                ]},
            ], "right": [
                {"type": "card", "title": "Needs attention", "items": [
                    {"type": "html", "html": """
<div style="display:grid;gap:0">
  <div class="kv" style="justify-content:space-between;padding:8px 0;
       border-bottom:1px solid var(--line-3)">
    <span>Approval blocked · unverified documents</span><b>17</b></div>
  <div class="kv" style="justify-content:space-between;padding:8px 0;
       border-bottom:1px solid var(--line-3)">
    <span>Service requests awaiting student</span><b>5</b></div>
  <div class="kv" style="justify-content:space-between;padding:8px 0;
       border-bottom:1px solid var(--line-3)">
    <span>Appointments unconfirmed &gt; 24 h</span><b>3</b></div>
  <div class="kv" style="justify-content:space-between;padding:8px 0;
       border-bottom:1px solid var(--line-3)">
    <span>Scholarships closing this week</span><b>2</b></div>
  <div class="kv" style="justify-content:space-between;padding:8px 0">
    <span>Students with no enrollment record</span><b>9</b></div>
</div>
<div class="row" style="margin-top:12px">
  <button class="wf-btn sm primary">Open review queue</button>
  <button class="wf-btn sm">Enrollment queue</button></div>"""},
                ]},
                {"type": "card", "title": "Quick actions", "items": [
                    {"type": "html", "html": """
<div style="display:grid;gap:7px">
  <button class="wf-btn sm" style="justify-content:flex-start">Create a scholarship</button>
  <button class="wf-btn sm" style="justify-content:flex-start">Add a user account</button>
  <button class="wf-btn sm" style="justify-content:flex-start">Publish an announcement</button>
  <button class="wf-btn sm" style="justify-content:flex-start">Edit matching weights</button>
  <button class="wf-btn sm" style="justify-content:flex-start">Open audit log</button>
</div>"""},
                ]},
            ]},
            {"type": "card", "title": "Recent activity",
             "actions": [("View audit log", "ghost")],
             "items": [{"type": "timeline", "items": [
                 ("Today 07:52", "Application approved · SCH-2026-000177",
                  "DOST-SEI Undergraduate · approved by A. Bautista after all required documents "
                  "were verified."),
                 ("Today 07:40", "Scholarship published · CHED Merit Scholarship",
                  "Status changed from draft to open by A. Bautista."),
                 ("Yesterday 16:18", "Document rejected",
                  "Certificate of Grades for SCH-2026-000184 · remarks recorded."),
                 ("Yesterday 14:02", "Matching weights updated",
                  "Academic 35, financial 25, program 20, year level 10, documents 10."),
             ]}]},
        ],
    ),

    dict(
        id="WF-A-02", title="User accounts", role="admin", folder="admin",
        nav_active="admin.users.index", route="admin.users.index",
        module="M03",
        reqs="FR-PROF-07, NFR-PERF-03",
        purpose="List every account with its role and active state so the administrator can "
                "suspend, restore or edit access.",
        annotations=[
            "Role filter is the primary control, because role determines which screens a user "
            "can reach.",
            "Active state is a first-class column: an inactive account is refused at login even "
            "with correct credentials.",
            "Only three roles exist — student, counselor and administrator. There is no super "
            "administrator tier.",
            "Deactivation is a toggle, not a delete, so historical records keep a valid owner.",
            "A user's own account cannot be deactivated from this screen (self-lockout guard).",
            "Email addresses are shown in full here because this is an administrator-only screen.",
        ],
        responsive=[
            ("Desktop 1440", "Filter bar plus a six-column table with row actions."),
            ("Tablet 1024", "Created column folds into a secondary line under the email address."),
            ("Mobile 390", "Rows become cards; status and role badges share the header row."),
        ],
        blocks=[
            {"type": "header", "title": "User accounts",
             "subtitle": "Student, counselor and administrator accounts. Role determines the "
                         "screens an account can open.",
             "actions": [("Add user", "primary")]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("Role", "select", ["All roles", "Student", "Counselor",
                                            "Administrator"]),
                        ("Status", "select", ["All", "Active", "Inactive"])],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Name", "Email", "Role", "Status", "Created", ""],
             "rows": [
                 ["Maria L. Santos", "m.santos@isu.edu.ph", "Student", "Active", "12 Jun 2023", ""],
                 ["Jose P. Rizal", "j.rizal@isu.edu.ph", "Student", "Active", "03 Jul 2022", ""],
                 ["Prof. R. Dela Cruz", "r.delacruz@isu.edu.ph", "Counselor", "Active", "01 Jun 2021", ""],
                 ["Ms. L. Aquino", "l.aquino@isu.edu.ph", "Counselor", "Inactive", "14 Aug 2022", ""],
                 ["A. Bautista", "a.bautista@isu.edu.ph", "Administrator", "Active", "01 Jun 2021", ""],
             ],
             "status_col": 3, "actions": ["Edit", "Deactivate"]},
            {"type": "pager", "current": 1, "total": 3, "count": "5 of 1,291 accounts"},
            {"type": "notice", "kind": "info", "text":
             "There is exactly one administrator tier. No super administrator, adviser or "
             "OSAS-staff role exists in the delivered system."},
        ],
    ),

    dict(
        id="WF-A-03", title="User create and edit", role="admin", folder="admin",
        nav_active="admin.users.index", route="admin.users.create / admin.users.edit",
        module="M03",
        reqs="FR-RBAC-01, FR-PROF-06, BR-05",
        purpose="Create an account with an explicit role, or change an existing account's name, "
                "email, role and active state.",
        annotations=[
            "Role is a required, explicit choice — it is never inferred from the email address.",
            "Email must be unique across all accounts; the field reports a duplicate inline.",
            "On create, an initial password is required and must be confirmed.",
            "On edit, the password field is left blank and only replaced when filled, so editing "
            "a role does not silently reset a password.",
            "Deactivating an account keeps every historical foreign-key reference intact.",
            "Changing a student's role does not create or delete a student profile; that is done "
            "on the student profile screens.",
        ],
        responsive=[
            ("Desktop 1440", "Two-column field grid inside an 840 px card."),
            ("Tablet 1024", "Two columns retained."),
            ("Mobile 390", "Single column; the role and status controls stack last."),
        ],
        blocks=[
            {"type": "header", "title": "Edit user account",
             "subtitle": "Maria L. Santos · created 12 June 2023",
             "actions": [("Back to users", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "items": [
                    {"type": "form", "sections": [
                        {"heading": "Account", "fields": [
                            {"label": "Full name", "required": True, "placeholder": "Maria L. Santos"},
                            {"label": "Email address", "required": True,
                             "placeholder": "m.santos@isu.edu.ph"},
                            {"label": "Role", "type": "select", "required": True,
                             "options": ["Student", "Counselor", "Administrator"],
                             "help": "Determines which route group the account may open."},
                            {"label": "Account status", "type": "select",
                             "options": ["Active", "Inactive"]},
                            {"label": "New password", "placeholder": "Leave blank to keep current",
                             "span": 2,
                             "help": "Minimum 8 characters. Use the reset screen instead when "
                                     "the user must choose their own."},
                        ]},
                    ], "buttons": [("Save changes", "primary"), ("Cancel", "outline")]},
                ]},
            ], "right": [
                {"type": "card", "title": "Account summary", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Role", "Student"),
                        ("Status", "Active"),
                        ("Email verified", "12 Jun 2023"),
                        ("Last login", "15 Nov 2026, 07:31"),
                        ("Linked profile", "2023-00123"),
                    ]}]},
                ]},
                {"type": "notice", "kind": "warn", "title": "Self-lockout guard",
                 "text": "You cannot deactivate or demote your own account. Another "
                         "administrator must make that change."},
                {"type": "card", "title": "Danger zone", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="margin-bottom:9px">
  Deactivating blocks login immediately. Records owned by this account are retained.</div>
<button class="wf-btn danger sm">Deactivate account</button>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-A-04", title="Administrator-initiated password reset", role="admin", folder="admin",
        nav_active="admin.users.index", route="admin.users.reset-password",
        module="M01, M03",
        reqs="FR-AUTH-10, FR-PROF-08, NFR-SEC-05",
        purpose="Let an administrator set a temporary password for a user who cannot sign in, "
                "without exposing or recovering the existing password.",
        annotations=[
            "This is the only password-recovery path: no self-service reset route exists "
            "(GAP-05).",
            "The existing password is a one-way hash and can never be displayed.",
            "The new password must be entered twice and meet the same minimum length as "
            "registration.",
            "The action is recorded in the audit log with the acting administrator and the "
            "target account.",
            "The user is not emailed automatically; the administrator hands over the temporary "
            "password through an agreed channel.",
            "Session invalidation is explicit: the user must sign in again with the new password.",
        ],
        responsive=[
            ("Desktop 1440", "Narrow confirmation card, 560 px, centred."),
            ("Tablet 1024", "Same card, full content width."),
            ("Mobile 390", "Single column, buttons full width."),
        ],
        blocks=[
            {"type": "header", "title": "Reset password",
             "subtitle": "Maria L. Santos · m.santos@isu.edu.ph · Student",
             "actions": [("Back to users", "ghost")]},
            {"type": "notice", "kind": "warn", "title": "This affects a live account",
             "text": "The current password will stop working immediately. Confirm the person's "
                     "identity at the OSAS office before continuing."},
            {"type": "card", "items": [
                {"type": "form", "sections": [
                    {"heading": "New credentials", "fields": [
                        {"label": "New temporary password", "required": True, "span": 2,
                         "placeholder": "At least 8 characters",
                         "help": "Avoid names and student numbers."},
                        {"label": "Confirm temporary password", "required": True, "span": 2,
                         "placeholder": "Repeat the password",
                         "error": "The two passwords do not match."},
                    ]},
                ], "buttons": [("Reset password", "primary"), ("Cancel", "outline")]},
            ]},
            {"type": "card", "title": "Audit trail", "items": [
                {"type": "detail", "sections": [{"pairs": [
                    ("Recorded action", "user.password_reset"),
                    ("Acting administrator", "A. Bautista"),
                    ("Target account", "m.santos@isu.edu.ph"),
                    ("Existing sessions", "Invalidated on save"),
                ]}]},
            ]},
        ],
    ),

    dict(
        id="WF-A-05", title="Student profiles", role="admin", folder="admin",
        nav_active="admin.student-profiles.index", route="admin.student-profiles.index",
        module="M03, M05",
        reqs="FR-PROF-04, FR-ENR-05",
        purpose="List every student profile with academic identity, GWA, income and current "
                "enrollment status so records can be corrected and audited.",
        annotations=[
            "Students are filtered by college, program, year level and enrollment status — the "
            "same facets used by eligibility rules.",
            "Enrollment status is the profile-level mirror of the latest verification decision.",
            "GWA and family income are shown because they drive eligibility; both are "
            "administrator-editable.",
            "A student with no verification record is distinguishable from one with a pending "
            "record.",
            "The list is read-only except for the per-row Edit action.",
            "Bulk export is offered because colleges request roster extracts.",
        ],
        responsive=[
            ("Desktop 1440", "Filter bar with five controls, then a seven-column table."),
            ("Tablet 1024", "Program folds under college; income column is hidden."),
            ("Mobile 390", "Cards with name, program, GWA, income and status."),
        ],
        blocks=[
            {"type": "header", "title": "Student profiles",
             "subtitle": "1,284 profile records across six colleges.",
             "actions": [("Export CSV", "ghost")]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("College", "select", ["All colleges", "CCIS", "CENG", "CTE", "CBA",
                                               "CA", "CN"]),
                        ("Program", "select", ["All programs"]),
                        ("Year level", "select", ["All years", "1st", "2nd", "3rd", "4th"]),
                        ("Enrollment", "select", ["All", "Enrolled", "Not enrolled", "Unknown"])],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Student", "Student no.", "College / program", "Year", "GWA",
                         "Income", "Enrollment", ""],
             "rows": [
                 ["Maria L. Santos", "2023-00123", "CCIS · BS Information Technology", "3",
                  "1.42", "148,000.00", "enrolled", ""],
                 ["Jose P. Rizal", "2022-00456", "CENG · BS Civil Engineering", "4",
                  "1.75", "220,000.00", "enrolled", ""],
                 ["Ana C. Reyes", "2024-00991", "CTE · BSEd Mathematics", "2",
                  "1.60", "96,500.00", "unknown", ""],
                 ["Mark T. Lim", "2021-00311", "CBA · BS Accountancy", "5",
                  "2.10", "310,000.00", "not_enrolled", ""],
             ],
             "status_col": 6, "actions": ["Edit", "Open"]},
            {"type": "pager", "current": 1, "total": 5, "count": "4 of 1,284 profiles"},
        ],
    ),

    dict(
        id="WF-A-06", title="Student profile detail", role="admin", folder="admin",
        nav_active="admin.student-profiles.index", route="admin.student-profiles.show",
        module="M03, M04, M05, M07",
        reqs="FR-PASS-04, FR-PROF-05, FR-ENR-02, FR-ELIG-01",
        purpose="Show one student's complete record, including enrollment outcome, passcode "
                "state and the eligibility inputs the system actually uses.",
        annotations=[
            "Contact and household details are shown only to administrators; a counselor never "
            "reaches this screen.",
            "Passcode state is shown as set or not set; the hash is never displayed.",
            "Resetting a forgotten passcode clears the hash and requires the student to set a "
            "new one; the action is audited.",
            "The enrollment panel links to the verification record for the current term.",
            "Eligibility inputs are labelled so an administrator can see exactly which fields "
            "feed scholarship screening.",
            "Every change is made through the edit screen, not inline, so validation is shared.",
        ],
        responsive=[
            ("Desktop 1440", "Three-column summary strip, then a 2:1 split of details and status."),
            ("Tablet 1024", "Status panels stack under the detail sections."),
            ("Mobile 390", "Definition lists collapse to label above value."),
        ],
        blocks=[
            {"type": "header", "title": "Maria L. Santos · 2023-00123",
             "subtitle": "College of Computing and Information Sciences · BS Information "
                         "Technology · 3rd year",
             "actions": [("Edit profile", "primary"), ("Back to list", "ghost")]},
            {"type": "stats", "cols": 3, "items": [
                ("General weighted average", "1.42", "Eligible for merit awards"),
                ("Annual family income", "PHP 148,000", "Below the 400,000 ceiling"),
                ("Enrollment status", "Enrolled", "Verified 05 Nov 2026"),
            ]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Profile record", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Identity", "pairs": [
                            ("Full name", "Maria L. Santos"),
                            ("Student number", "2023-00123"),
                            ("Email address", "m.santos@isu.edu.ph"),
                            ("Contact number", "0917 555 0142"),
                            ("Home address", "Brgy. San Isidro, Echague, Isabela"),
                        ]},
                        {"heading": "Academic", "pairs": [
                            ("College", "College of Computing and Information Sciences"),
                            ("Program", "BS Information Technology"),
                            ("Year level", "3rd year"),
                            ("General weighted average", "1.42"),
                            ("Annual family income", "PHP 148,000.00"),
                        ]},
                    ]},
                ]},
                {"type": "card", "title": "Scholarship activity", "items": [
                    {"type": "table", "columns": ["Application", "Scholarship", "Status", ""],
                     "rows": [
                         ["SCH-2026-000184", "DOST-SEI Undergraduate", "under_review", ""],
                         ["SCH-2026-000121", "ISU Presidential Grant", "rejected", ""],
                     ], "status_col": 2, "actions": ["Open"]},
                ]},
            ], "right": [
                {"type": "card", "title": "Enrollment verification", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Term", "2025–2026 · Second semester"),
                        ("Record status", "Verified"),
                        ("Decided by", "Prof. R. Dela Cruz"),
                        ("Decided on", "05 Nov 2026"),
                    ]}]},
                    {"type": "html", "html": """
<div class="row" style="margin-top:10px">
  <button class="wf-btn sm">Open verification record</button></div>"""},
                ]},
                {"type": "card", "title": "Profile passcode", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("State", "Set"),
                        ("Last changed", "02 Nov 2026"),
                    ]}]},
                    {"type": "html", "html": """
<div class="small muted" style="margin:8px 0 9px">
  Resetting clears the stored hash. The student sets a new passcode on their next visit.</div>
<button class="wf-btn danger sm">Reset passcode</button>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-A-07", title="Student profile edit", role="admin", folder="admin",
        nav_active="admin.student-profiles.index", route="admin.student-profiles.edit",
        module="M03",
        reqs="FR-PROF-05, FR-ENR-02, BR-06",
        purpose="Correct any field of a student profile, including the academic identity and "
                "enrollment status that students cannot change themselves.",
        annotations=[
            "Every field on this form is editable, unlike the student-facing edit screen.",
            "Student number must remain unique across all profiles.",
            "GWA is constrained to 1.00–5.00 and income to a non-negative decimal; a change "
            "immediately affects eligibility previews.",
            "Enrollment status here is a manual override; the verification screen is the "
            "auditable path and is preferred.",
            "Changes are audited with before and after values.",
            "The student is not notified automatically of an administrative correction.",
        ],
        responsive=[
            ("Desktop 1440", "Three grouped sections in a two-column grid."),
            ("Tablet 1024", "Two columns retained; long text fields span both."),
            ("Mobile 390", "Single column; section headings become sticky separators."),
        ],
        blocks=[
            {"type": "header", "title": "Edit student profile",
             "subtitle": "Maria L. Santos · 2023-00123",
             "actions": [("Back to profile", "ghost")]},
            {"type": "notice", "kind": "info", "text":
             "Prefer the enrollment verification screen for status changes: it records an "
             "auditable decision, a decider and a timestamp. The override here is for "
             "corrections only."},
            {"type": "card", "items": [
                {"type": "form", "sections": [
                    {"heading": "Identity", "fields": [
                        {"label": "Student number", "required": True, "placeholder": "2023-00123"},
                        {"label": "Full name", "required": True, "placeholder": "Maria L. Santos"},
                        {"label": "Email address", "required": True,
                         "placeholder": "m.santos@isu.edu.ph"},
                        {"label": "Contact number", "required": True,
                         "placeholder": "0917 555 0142"},
                        {"label": "Home address", "type": "textarea", "span": 2,
                         "placeholder": "Brgy. San Isidro, Echague, Isabela"},
                    ]},
                    {"heading": "Academic", "fields": [
                        {"label": "College", "type": "select",
                         "options": ["College of Computing and Information Sciences"]},
                        {"label": "Program", "type": "select",
                         "options": ["BS Information Technology"]},
                        {"label": "Year level", "type": "select",
                         "options": ["1st year", "2nd year", "3rd year", "4th year"]},
                        {"label": "Enrollment status", "type": "select",
                         "options": ["Unknown", "Enrolled", "Not enrolled"]},
                        {"label": "General weighted average", "required": True,
                         "placeholder": "1.42", "help": "1.00 highest, 5.00 lowest."},
                        {"label": "Annual family income", "required": True,
                         "placeholder": "148000.00", "help": "Two decimals, non-negative."},
                    ]},
                ], "buttons": [("Save changes", "primary"), ("Cancel", "outline")]},
            ]},
        ],
    ),

    dict(
        id="WF-A-08", title="Scholarships", role="admin", folder="admin",
        nav_active="admin.scholarships.index", route="admin.scholarships.index",
        module="M06",
        reqs="FR-SCH-03, FR-SCH-04, FR-SCH-08",
        purpose="Manage the scholarship catalogue: create, publish, close, archive and announce "
                "a scholarship, and reach its document requirements.",
        annotations=[
            "Status is grouped into draft, open, closed and archived, matching the lifecycle "
            "constants.",
            "An open scholarship is visible to students only while today sits inside the "
            "application window; the window is shown next to the status.",
            "Publish, deactivate and archive are row actions, each guarded by the lifecycle "
            "rules in force.",
            "Announcement sends a notification only to students currently eligible for that "
            "scholarship.",
            "The requirements count links to the requirements manager for that scholarship.",
            "Archiving hides the scholarship from students but leaves historical applications "
            "intact.",
        ],
        responsive=[
            ("Desktop 1440", "Filter bar plus a seven-column table."),
            ("Tablet 1024", "Provider folds under the title; window becomes a secondary line."),
            ("Mobile 390", "Cards grouped by status with a status-coloured edge."),
        ],
        blocks=[
            {"type": "header", "title": "Scholarships",
             "subtitle": "4 open · 1 draft · 2 closed · 3 archived",
             "actions": [("New scholarship", "primary"), ("Matching weights", "ghost")]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("Status", "select", ["All statuses", "Draft", "Open", "Closed",
                                              "Archived"]),
                        ("Provider", "select", ["All providers", "DOST", "CHED", "ISU",
                                                "OSAS", "Private"]),
                        ("Closing before", "date")],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Scholarship", "Provider", "Type", "Window", "Requirements", "Status", ""],
             "rows": [
                 ["DOST-SEI Undergraduate Scholarship", "DOST", "Merit",
                  "01 Nov – 30 Nov 2026", "3", "open", ""],
                 ["CHED Merit Scholarship", "CHED", "Merit",
                  "15 Nov – 15 Dec 2026", "3", "open", ""],
                 ["ISU Presidential Grant", "ISU", "Grant",
                  "01 Nov – 30 Nov 2026", "2", "open", ""],
                 ["OSAS Emergency Assistance", "OSAS", "Assistance",
                  "20 Nov – 05 Dec 2026", "1", "open", ""],
                 ["Private Foundation Grant", "Private", "Grant",
                  "—", "2", "draft", ""],
                 ["University Book Allowance", "ISU", "Grant",
                  "01 Aug – 30 Sep 2026", "1", "closed", ""],
             ],
             "status_col": 5, "actions": ["Open", "Deactivate"]},
            {"type": "pager", "current": 1, "total": 2, "count": "6 of 10 scholarships"},
            {"type": "notice", "kind": "info", "text":
             "A row action is shown only when the lifecycle permits it. A closed scholarship "
             "can be reopened; an archived one cannot."},
        ],
    ),

    dict(
        id="WF-A-09", title="Scholarship create and edit", role="admin", folder="admin",
        nav_active="admin.scholarships.index", route="admin.scholarships.create / "
        "admin.scholarships.edit", module="M06",
        reqs="FR-SCH-01, FR-SCH-02, BR-20, BR-21",
        purpose="Define a scholarship's identity, its eligibility thresholds, its eligible "
                "programs and year levels, and its application window.",
        annotations=[
            "Four sections: identity, eligibility thresholds, eligible cohorts, and schedule.",
            "Every threshold is optional; a blank threshold means that criterion is not applied "
            "and is reported to students as not applicable.",
            "Deadline must be on or after the start date, validated on both fields.",
            "Programs and year levels are multi-selects; selecting none means all are eligible.",
            "Status is chosen explicitly — saving as draft keeps the scholarship invisible to "
            "students.",
            "Saving never publishes automatically; publication is a separate, audited action.",
        ],
        responsive=[
            ("Desktop 1440", "Two-column grid, sections separated by rules, sticky save bar."),
            ("Tablet 1024", "Two columns retained; multi-selects become full width."),
            ("Mobile 390", "Single column; save and cancel become a fixed bottom bar."),
        ],
        blocks=[
            {"type": "header", "title": "New scholarship",
             "subtitle": "Complete the identity, thresholds and window. Drafts stay invisible to "
                         "students until published.",
             "actions": [("Back to scholarships", "ghost")]},
            {"type": "card", "items": [
                {"type": "form", "sections": [
                    {"heading": "1 · Identity", "fields": [
                        {"label": "Title", "required": True, "span": 2,
                         "placeholder": "DOST-SEI Undergraduate Scholarship"},
                        {"label": "Provider", "required": True, "placeholder": "Department of "
                         "Science and Technology"},
                        {"label": "Scholarship type", "type": "select", "required": True,
                         "options": ["Merit", "Grant", "Assistance"]},
                        {"label": "Description", "type": "textarea", "span": 2,
                         "placeholder": "What the award covers and who it is for."},
                        {"label": "Status", "type": "select",
                         "options": ["Draft", "Open", "Closed"]},
                    ]},
                    {"heading": "2 · Eligibility thresholds", "fields": [
                        {"label": "Minimum GWA", "placeholder": "2.00",
                         "help": "Leave blank to skip this criterion."},
                        {"label": "Maximum family income", "placeholder": "400000.00",
                         "help": "Leave blank to skip this criterion."},
                        {"label": "Required enrollment status", "type": "select",
                         "options": ["Any", "Enrolled"]},
                        {"label": "Award summary", "placeholder": "Full tuition + stipend"},
                    ]},
                    {"heading": "3 · Eligible cohorts", "fields": [
                        {"label": "Eligible programs", "type": "select", "span": 2,
                         "options": ["All programs", "Selected programs…"],
                         "help": "Selecting none means every program is eligible."},
                        {"label": "Eligible year levels", "type": "select", "span": 2,
                         "options": ["All year levels", "2nd to 4th year", "Selected years…"]},
                    ]},
                    {"heading": "4 · Application window", "fields": [
                        {"label": "Application start date", "required": True,
                         "placeholder": "01 Nov 2026"},
                        {"label": "Application deadline", "required": True,
                         "placeholder": "30 Nov 2026",
                         "error": "The deadline must be on or after the start date."},
                        {"label": "Announcement", "type": "checkbox", "span": 2,
                         "placeholder": "Notify currently eligible students when this "
                                        "scholarship is published."},
                    ]},
                ], "buttons": [("Save as draft", "primary"), ("Publish", "outline"),
                               ("Cancel", "outline")]},
            ]},
            {"type": "notice", "kind": "info", "title": "Document requirements are added after saving",
             "text": "Save the scholarship first, then use its requirements manager to add the "
                     "documents students must upload. Approval is blocked until every required "
                     "document has a verified upload."},
        ],
    ),

    dict(
        id="WF-A-10", title="Scholarship requirements manager", role="admin", folder="admin",
        nav_active="admin.scholarships.index",
        route="admin.scholarships.requirements.index", module="M06, M10",
        reqs="FR-SCH-05, BR-11",
        purpose="Maintain the ordered list of documents a scholarship demands, and whether each "
                "one is required or optional.",
        annotations=[
            "Each requirement has a name, an optional description, a required flag and a "
            "display order.",
            "Display order controls the order students see on the application and the upload form.",
            "A requirement marked required participates in the approval guard; an optional one "
            "never blocks approval.",
            "Requirements can be edited while the scholarship is draft or open, but removing one "
            "that already has uploads warns first.",
            "The count of uploaded documents per requirement is shown so the effect of removal is "
            "visible.",
            "Deleting a requirement does not delete the scholarship; it only removes the demand.",
        ],
        responsive=[
            ("Desktop 1440", "Requirements table left, add form right."),
            ("Tablet 1024", "Add form moves above the table."),
            ("Mobile 390", "Each requirement becomes a card with reorder and delete actions."),
        ],
        blocks=[
            {"type": "header", "title": "Document requirements",
             "subtitle": "DOST-SEI Undergraduate Scholarship · 3 requirements",
             "actions": [("Back to scholarship", "ghost")]},
            {"type": "notice", "kind": "info", "text":
             "Every requirement marked required must have at least one verified upload before "
             "the application can be approved. This is enforced by the approval guard, not by "
             "this screen."},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Requirements", "items": [
                    {"type": "table",
                     "columns": ["Order", "Requirement", "Needed", "Uploads", ""],
                     "rows": [
                         ["1", "Certificate of Enrollment", "Required", "28", ""],
                         ["2", "Certificate of Grades", "Required", "26", ""],
                         ["3", "Certificate of Indigency", "Required", "19", ""],
                         ["4", "Barangay Clearance", "Optional", "6", ""],
                     ],
                     "status_col": 2, "actions": ["Edit", "Delete"]},
                ]},
            ], "right": [
                {"type": "card", "title": "Add a requirement", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Requirement name", "required": True, "span": 2,
                             "placeholder": "Certificate of Enrollment"},
                            {"label": "Description", "type": "textarea", "span": 2,
                             "placeholder": "What the document must show, and who issues it."},
                            {"label": "Needed", "type": "select",
                             "options": ["Required", "Optional"]},
                            {"label": "Display order", "placeholder": "5"},
                        ]},
                    ], "buttons": [("Add requirement", "primary"), ("Cancel", "outline")]},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-A-11", title="Matching weights", role="admin", folder="admin",
        nav_active="admin.scholarship-matching.weights.edit",
        route="admin.scholarship-matching.weights.edit", module="M08",
        reqs="FR-MATCH-02, FR-MATCH-03, BR-21",
        purpose="Set the five weights that drive recommendation ranking, with a hard requirement "
                "that they total 100.",
        annotations=[
            "Five weights: academic, financial, program, year level and document readiness.",
            "The live total is displayed and must equal 100, with a tolerance of 0.01; otherwise "
            "saving is refused.",
            "Only one weight set is active at a time; saving supersedes the previous set.",
            "The change is attributed to the signed-in administrator and time-stamped.",
            "Weights affect ranking order, never eligibility — an ineligible scholarship stays "
            "ineligible whatever the weights.",
            "A worked example shows how a 92% score is produced from the entered weights.",
        ],
        responsive=[
            ("Desktop 1440", "Weight form left with a live total badge, explanation right."),
            ("Tablet 1024", "Explanation stack moves below the form."),
            ("Mobile 390", "Weights become full-width rows with numeric steppers."),
        ],
        blocks=[
            {"type": "header", "title": "Matching weights",
             "subtitle": "Weights must total 100. They determine ranking order only; they never "
                         "change eligibility.",
             "actions": [("Back to scholarships", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Active weights", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Academic performance", "required": True, "placeholder": "35",
                             "help": "GWA relative to the scholarship threshold."},
                            {"label": "Financial need", "required": True, "placeholder": "25",
                             "help": "Household income relative to the ceiling."},
                            {"label": "Program match", "required": True, "placeholder": "20",
                             "help": "Binary: the program is eligible or it is not."},
                            {"label": "Year level match", "required": True, "placeholder": "10",
                             "help": "Binary: the year level is eligible or it is not."},
                            {"label": "Document readiness", "required": True, "placeholder": "10",
                             "help": "Verified or uploaded documents over required documents."},
                        ]},
                    ], "buttons": [("Save weights", "primary"), ("Reset to saved", "outline")]},
                ]},
            ], "right": [
                {"type": "card", "title": "Total", "items": [
                    {"type": "html", "html": """
<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
  <div style="font:700 34px/1 var(--mono)">100.00</div>
  <span class="badge solid">Valid</span></div>
<div class="small muted">A total outside 100 ± 0.01 is refused with the exact difference
shown, and nothing is saved.</div>"""},
                ]},
                {"type": "card", "title": "How a score is formed", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="display:grid;gap:7px">
  <span>Each factor is scored 0–1, multiplied by its weight, then summed.</span>
  <span>The sum is divided by the weights that applied, so a skipped criterion does not
  depress the score.</span>
  <span>The result is multiplied by 100 and rounded to two decimals.</span>
  <span class="mono" style="background:var(--fill);padding:8px;border-radius:4px">
    (0.90×35 + 0.95×25 + 1×20 + 1×10 + 0.67×10) / 100 = 92.20</span>
</div>"""},
                ]},
            ]},
            {"type": "notice", "kind": "warn", "title": "Weights are global",
             "text": "A single weight set applies to every scholarship and every student. "
                     "Per-scholarship weights are not supported by the delivered schema."},
        ],
    ),

    dict(
        id="WF-A-12", title="Application review queue", role="admin", folder="admin",
        nav_active="admin.scholarship-applications.index",
        route="admin.scholarship-applications.index", module="M09, M10",
        reqs="FR-APP-06, NFR-PERF-02, NFR-USA-03",
        purpose="List applications across all scholarships so the administrator can triage by "
                "status, age and document readiness.",
        annotations=[
            "Status tabs mirror the application state machine exactly, including draft.",
            "Document readiness is shown as verified over required, because that is the "
            "approval-blocking condition.",
            "Days waiting is derived from the timestamp of the last status change.",
            "Scholarship, status and date filters combine; the search box matches application "
            "number or student name.",
            "Rows are sorted oldest-first within a status so nothing starves.",
            "The queue performs no transitions; every decision happens on the detail screen.",
        ],
        responsive=[
            ("Desktop 1440", "Tabs, filters, then an eight-column table."),
            ("Tablet 1024", "Scholarship folds under the application number."),
            ("Mobile 390", "Cards with the application number, student, readiness and status."),
        ],
        blocks=[
            {"type": "header", "title": "Scholarship applications",
             "subtitle": "96 live applications · 31 awaiting document verification",
             "actions": [("Export CSV", "ghost")]},
            {"type": "tabs", "items": [("Submitted (24)", True), ("Under review (31)", False),
                                       ("Document verification (23)", False),
                                       ("Approved (41)", False), ("Rejected (9)", False)]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("Scholarship", "select", ["All scholarships"]),
                        ("Documents", "select", ["Any readiness", "Ready to approve",
                                                 "Missing required"]),
                        ("Submitted from", "date")],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Application no.", "Student", "Scholarship", "Submitted", "Documents",
                         "Waiting", "Status", ""],
             "rows": [
                 ["SCH-2026-000184", "Maria L. Santos", "DOST-SEI Undergraduate",
                  "12 Nov 2026", "1 / 3", "3 d", "under_review", ""],
                 ["SCH-2026-000181", "Jose P. Rizal", "CHED Merit Scholarship",
                  "11 Nov 2026", "3 / 3", "4 d", "document_verification", ""],
                 ["SCH-2026-000179", "Ana C. Reyes", "ISU Presidential Grant",
                  "10 Nov 2026", "2 / 2", "5 d", "document_verification", ""],
                 ["SCH-2026-000176", "Mark T. Lim", "DOST-SEI Undergraduate",
                  "09 Nov 2026", "3 / 3", "6 d", "submitted", ""],
             ],
             "status_col": 6, "actions": ["Review"]},
            {"type": "pager", "current": 1, "total": 7, "count": "4 of 96 applications"},
        ],
    ),

    dict(
        id="WF-A-13", title="Application review detail", role="admin", folder="admin",
        nav_active="admin.scholarship-applications.index",
        route="admin.scholarship-applications.show", module="M09, M10",
        reqs="FR-APP-07, FR-APP-08, FR-DOC-03, FR-DOC-04, FR-APP-09, NFR-REL-03",
        purpose="Review one application, verify or reject each document, and advance the "
                "workflow only along transitions the state machine permits.",
        annotations=[
            "The transition control offers only the targets allowed from the current state.",
            "Approval is refused while any required requirement lacks a verified document, and "
            "the refusal lists each blocking requirement.",
            "Document actions are verify and reject; rejection requires remarks that the student "
            "sees.",
            "Documents are opened through an authorised stream, never a direct storage path.",
            "Rejection of the application requires remarks; the requirement is enforced "
            "server-side as well.",
            "The history panel is append-only and is the audit record for the decision.",
        ],
        responsive=[
            ("Desktop 1440", "Stepper and header, then a 2:1 split of documents and decision "
                             "panel."),
            ("Tablet 1024", "Decision panel moves under the documents table."),
            ("Mobile 390", "Single column; each document becomes a card with verify and reject."),
        ],
        blocks=[
            {"type": "header", "title": "SCH-2026-000184",
             "subtitle": "Maria L. Santos · DOST-SEI Undergraduate · submitted 12 Nov 2026",
             "actions": [("Download all", "ghost")]},
            {"type": "steps", "items": [("Submitted", "done"), ("Under review", "now"),
                                        ("Document verification", ""), ("Decision", "")]},
            {"type": "notice", "kind": "warn", "title": "Approval is currently blocked",
             "text": "Two required requirements have no verified document: Certificate of "
                     "Indigency (not uploaded) and Certificate of Enrollment (pending "
                     "verification)."},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Documents", "items": [
                    {"type": "table",
                     "columns": ["Requirement", "File", "Uploaded", "State", ""],
                     "rows": [
                         ["Certificate of Enrollment", "coe_santos.pdf", "10 Nov 2026",
                          "pending", ""],
                         ["Certificate of Grades", "cog_santos.pdf", "10 Nov 2026",
                          "verified", ""],
                         ["Certificate of Indigency", "—", "—", "not uploaded", ""],
                     ],
                     "status_col": 3, "actions": ["Open", "Verify"]},
                    {"type": "html", "html": """
<div class="card" style="margin-top:12px;border-style:dashed">
  <div class="card-b">
    <b style="font-size:13px">Verify or reject a document</b>
    <div class="fgrid" style="margin-top:10px">
      <div class="field"><label>Document</label>
        <div class="ctl"><span>Certificate of Enrollment</span>
          <span class="car">&#9660;</span></div></div>
      <div class="field"><label>Decision</label>
        <div class="ctl"><span>Rejected</span><span class="car">&#9660;</span></div></div>
      <div class="field span2"><label>Remarks <span class="req">*</span></label>
        <div class="ctl area"><span class="ph">Required when rejecting. Shown to the student
          with the notification.</span></div>
        <div class="fhelp">The student may re-upload while the application is not yet
          decided.</div></div>
    </div>
    <div class="row" style="margin-top:10px">
      <button class="wf-btn sm primary">Save document decision</button></div>
  </div>
</div>"""},
                ]},
                {"type": "card", "title": "Status history", "items": [
                    {"type": "timeline", "items": [
                        ("13 Nov 2026 08:12", "Moved to under review",
                         "Reviewed by A. Bautista."),
                        ("12 Nov 2026 09:14", "Submitted",
                         "Submitted by Maria L. Santos."),
                        ("10 Nov 2026 16:02", "Draft created",
                         "Application SCH-2026-000184 assigned."),
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Workflow transition", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Next status", "type": "select", "span": 2,
                             "options": ["Document verification",
                                         "Rejected — application"],
                             "help": "Only transitions permitted from under_review are listed."},
                            {"label": "Remarks", "type": "textarea", "span": 2,
                             "placeholder": "Required when rejecting."},
                        ]},
                    ], "buttons": [("Apply transition", "primary")]},
                ]},
                {"type": "card", "title": "Student", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Name", "Maria L. Santos"),
                        ("Student no.", "2023-00123"),
                        ("College", "CCIS"),
                        ("GWA", "1.42"),
                        ("Enrollment", "Verified"),
                    ]}]},
                ]},
            ]},
        ],
    ),
]
