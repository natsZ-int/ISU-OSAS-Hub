"""Wireframe page specs: counselor screens (WF-C-01 … WF-C-06)."""

PAGES = [
    dict(
        id="WF-C-01", title="Counselor dashboard", role="counselor", folder="counselor",
        nav_active="counselor.dashboard", route="counselor.dashboard",
        module="M11, M05, M12",
        reqs="FR-SVC-10, FR-GUID-09, FR-ENR-04",
        purpose="Give the counselor a triage view of what needs a decision today: pending "
                "appointment requests, today's confirmed sessions, and the enrollment and "
                "service-request items they are entitled to act on.",
        annotations=[
            "Four tiles: pending requests, today's sessions, enrollment items awaiting a "
            "decision, and unread notifications.",
            "The pending-request list is the only place a request can be confirmed or rejected, "
            "so it is placed above the fold.",
            "Today's schedule is ordered by start time and shows student, type and status only.",
            "Enrollment verification appears because counselors may verify enrollment alongside "
            "administrators (FR-RBAC-04); service-request counters are read-only here.",
            "No student-identifying data is exposed beyond name, student number and the request "
            "the student chose to share.",
            "Every panel links to the full queue; the dashboard itself performs no transitions.",
        ],
        responsive=[
            ("Desktop 1440", "Four tiles in one row; a 2:1 split of pending requests and today's "
                             "schedule."),
            ("Tablet 1024", "Tiles fall to two columns; the split becomes stacked."),
            ("Mobile 390", "Single column; the pending list appears first, tiles last."),
        ],
        blocks=[
            {"type": "header", "title": "Good morning, Prof. Dela Cruz",
             "subtitle": "2 requests need a decision · 3 sessions today · "
                         "Guidance Office, Room 204",
             "actions": [("My appointments", "primary"), ("Update availability", "ghost")]},
            {"type": "stats", "cols": 4, "items": [
                ("Pending requests", "2", "Oldest waiting 6 hours"),
                ("Today's sessions", "3", "First at 09:00"),
                ("Enrollment to review", "7", "Across 2 colleges"),
                ("Unread notifications", "4", "Since yesterday"),
            ]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Requests awaiting your decision",
                 "actions": [("Open queue", "ghost")],
                 "items": [{"type": "table",
                            "columns": ["Student", "Date and time", "Type", "Waiting", ""],
                            "rows": [
                                ["Maria L. Santos", "18 Nov 2026, 10:00", "Academic counseling", "6 h", ""],
                                ["Jose P. Rizal", "19 Nov 2026, 14:30", "Course selection", "1 d", ""],
                            ],
                            "actions": ["Review"]}]},
            ], "right": [
                {"type": "card", "title": "Today, 15 Nov", "items": [
                    {"type": "timeline", "items": [
                        ("09:00 – 09:30", "Ana C. Reyes · Personal counseling", "Confirmed"),
                        ("11:00 – 11:30", "Mark T. Lim · Academic counseling", "Confirmed"),
                        ("15:30 – 16:15", "Grace U. Tan · Scholarship guidance", "Confirmed"),
                    ]}]},
            ]},
            {"type": "grid", "cols": 2, "items": [
                {"type": "card", "title": "Enrollment verification", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="margin-bottom:10px">
  Pending decisions for the current term, 2025–2026 second semester.</div>
<div class="kv" style="justify-content:space-between;border-bottom:1px solid var(--line-3);
     padding:6px 0"><span>Pending</span><b>7</b></div>
<div class="kv" style="justify-content:space-between;border-bottom:1px solid var(--line-3);
     padding:6px 0"><span>Verified this week</span><b>23</b></div>
<div class="kv" style="justify-content:space-between;padding:6px 0">
  <span>Unable to verify</span><b>2</b></div>
<div class="row" style="margin-top:10px">
  <button class="wf-btn sm primary">Open verification queue</button></div>"""},
                ]},
                {"type": "card", "title": "Service request involvement", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="margin-bottom:10px">
  Read-only summary. Processing service requests is an administrator function
  (GAP-03: no counselor route exists).</div>
<div class="kv" style="justify-content:space-between;border-bottom:1px solid var(--line-3);
     padding:6px 0"><span>Assigned to you</span><b>0</b></div>
<div class="kv" style="justify-content:space-between;border-bottom:1px solid var(--line-3);
     padding:6px 0"><span>Awaiting information</span><b>1</b></div>
<div class="kv" style="justify-content:space-between;padding:6px 0">
  <span>Completed this month</span><b>0</b></div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-C-02", title="Weekly availability", role="counselor", folder="counselor",
        nav_active="counselor.guidance.availability", route="counselor.guidance.availability",
        module="M11",
        reqs="FR-GUID-01, FR-GUID-02, BR-15, BR-16",
        purpose="Publish the recurring weekly blocks from which bookable appointment slots are "
                "generated, and mark a day unavailable without deleting its history.",
        annotations=[
            "One row per day of week (1 = Monday … 7 = Sunday), with a start time, an end time "
            "and an available flag.",
            "Saving a block that overlaps another block for the same counselor is rejected with "
            "a field-level error.",
            "End time must be later than start time; the pair is validated server-side too.",
            "The preview column shows the resulting bookable slots so the effect of a change is "
            "visible before saving.",
            "Office hours and minimum booking notice are administrator settings and cannot be "
            "changed here; they are shown as context.",
            "Marking a day unavailable stops new bookings without deleting past appointments.",
        ],
        responsive=[
            ("Desktop 1440", "Seven-column grid with the editor form in a right-hand panel."),
            ("Tablet 1024", "Grid scrolls horizontally; editor moves above the grid."),
            ("Mobile 390", "Each day becomes a stacked row with its own start and end controls."),
        ],
        blocks=[
            {"type": "header", "title": "My weekly availability",
             "subtitle": "Students can only book inside these blocks, inside office hours and "
                         "beyond the minimum booking notice.",
             "actions": [("My appointments", "ghost")]},
            {"type": "notice", "kind": "info", "title": "Current office settings",
             "text": "Office hours 08:00 – 17:00, Monday to Friday · minimum booking notice 4 "
                     "hours · booking horizon 30 days. These are administrator settings."},
            {"type": "card", "title": "Published blocks", "items": [
                {"type": "availability",
                 "days": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
                 "rows": [
                     ["08:00", [1, 1, 0, 1, 1, 0, 0]],
                     ["09:00", [1, 1, 0, 1, 1, 0, 0]],
                     ["10:00", [1, 1, 1, 1, 1, 0, 0]],
                     ["11:00", [1, 1, 1, 1, 1, 0, 0]],
                     ["13:00", [1, 0, 1, 0, 1, 0, 0]],
                     ["14:00", [1, 0, 1, 0, 1, 0, 0]],
                     ["15:00", [1, 1, 1, 1, 1, 0, 0]],
                     ["16:00", [0, 1, 1, 1, 0, 0, 0]],
                 ]},
                {"type": "html", "html": """
<div class="row small muted" style="margin-top:12px">
  <span style="display:inline-flex;align-items:center;gap:6px">
    <span style="width:12px;height:12px;background:var(--ink);border-radius:2px"></span>
    Available</span>
  <span style="display:inline-flex;align-items:center;gap:6px;margin-left:14px">
    <span style="width:12px;height:12px;border:1px solid var(--line-2);
          background:repeating-linear-gradient(45deg,#fafafa,#fafafa 3px,#ececec 3px,#ececec 6px);
          border-radius:2px"></span>
    Not available</span>
</div>"""},
            ]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Add or replace a block", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Day of week", "type": "select",
                             "options": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday",
                                         "Saturday", "Sunday"]},
                            {"label": "Available", "type": "select",
                             "options": ["Available", "Not available"]},
                            {"label": "Start time", "required": True, "placeholder": "08:00"},
                            {"label": "End time", "required": True, "placeholder": "12:00",
                             "error": "End time must be later than start time."},
                        ]},
                    ], "buttons": [("Save block", "primary"), ("Cancel", "outline")]},
                ]},
            ], "right": [
                {"type": "card", "title": "Blocking rule", "items": [
                    {"type": "html", "html": """
<div class="small muted">A new block may not overlap an existing block on the same day.
An overlap is rejected and the existing block is left untouched. Appointments already
booked inside a block keep their slot even if the block is later shortened.</div>"""},
                ]},
                {"type": "card", "title": "Slot preview", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="margin-bottom:8px">Wednesday morning, 30-minute type</div>
<div class="slots">
  <div class="slot free">10:00</div><div class="slot free">10:30</div>
  <div class="slot free">11:00</div><div class="slot taken">11:30</div>
</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-C-03", title="Appointment queue", role="counselor", folder="counselor",
        nav_active="counselor.guidance.appointments", route="counselor.guidance.appointments",
        module="M11",
        reqs="FR-GUID-09, FR-GUID-11, NFR-PERF-03",
        purpose="List every appointment belonging to this counselor, filtered by status, so a "
                "request can be reviewed without leaving the queue.",
        annotations=[
            "Tabs follow the appointment state machine: requested, confirmed, completed, "
            "rejected and cancelled.",
            "Only requested rows show Confirm and Reject; confirmed rows show Complete.",
            "The queue is scoped to the signed-in counselor — another counselor's appointments "
            "are not reachable by changing the identifier.",
            "Date range and student-name filters combine with the active tab.",
            "Counts on each tab reflect the filtered set, not the global total.",
            "Pagination is server-side, 15 rows per page.",
        ],
        responsive=[
            ("Desktop 1440", "Tabs, filter bar, then a table with seven columns."),
            ("Tablet 1024", "Type column folds under the student name."),
            ("Mobile 390", "Each appointment renders as a card; decisions become full-width buttons."),
        ],
        blocks=[
            {"type": "header", "title": "My appointments",
             "subtitle": "Requests you have received and sessions you have held.",
             "actions": [("Update availability", "ghost")]},
            {"type": "tabs", "items": [("Requested (2)", True), ("Confirmed (5)", False),
                                       ("Completed (18)", False), ("Rejected (1)", False),
                                       ("Cancelled (2)", False)]},
            {"type": "filters",
             "fields": [("Search student", "search"),
                        ("From", "date"), ("To", "date"),
                        ("Appointment type", "select",
                         ["All types", "Academic counseling", "Course selection",
                          "Personal counseling", "Career counseling"])],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Student", "Student no.", "Date", "Time", "Type", "Status", ""],
             "rows": [
                 ["Maria L. Santos", "2023-00123", "18 Nov 2026", "10:00 – 10:30",
                  "Academic counseling", "requested", ""],
                 ["Jose P. Rizal", "2022-00456", "19 Nov 2026", "14:30 – 15:00",
                  "Course selection", "requested", ""],
                 ["Ana C. Reyes", "2024-00991", "15 Nov 2026", "09:00 – 09:30",
                  "Personal counseling", "confirmed", ""],
                 ["Mark T. Lim", "2021-00311", "15 Nov 2026", "11:00 – 11:30",
                  "Academic counseling", "confirmed", ""],
             ],
             "status_col": 5, "actions": ["Review", "Confirm"]},
            {"type": "pager", "current": 1, "total": 2, "count": "4 of 28 appointments"},
        ],
    ),

    dict(
        id="WF-C-04", title="Appointment detail and decisions", role="counselor", folder="counselor",
        nav_active="counselor.guidance.appointments",
        route="counselor.guidance.appointments.show", module="M11",
        reqs="FR-GUID-09, FR-GUID-13, BR-18, BR-19",
        purpose="Review one appointment request with the student's stated reason, then confirm, "
                "reject with a required reason, or complete a confirmed session.",
        annotations=[
            "Only transitions permitted by the state machine are rendered as actions.",
            "Reject requires a written reason; the reason is shown to the student verbatim.",
            "Confirm re-checks the slot for conflict before committing, so a stale page cannot "
            "double-book.",
            "Complete is available only from confirmed, and stamps the completion in history.",
            "Counselor notes are private working notes and are not visible to the student.",
            "The student's submitted reason is read-only here.",
        ],
        responsive=[
            ("Desktop 1440", "Details left, decision panel and private notes right."),
            ("Tablet 1024", "Decision panel moves directly under the details."),
            ("Mobile 390", "Single column; decision buttons stack and stay full width."),
        ],
        blocks=[
            {"type": "header", "title": "Appointment · 18 Nov 2026, 10:00",
             "subtitle": "Maria L. Santos · Academic counseling · requested 6 hours ago",
             "actions": [("Back to queue", "ghost")]},
            {"type": "steps", "items": [("Requested", "now"), ("Confirmed", ""),
                                        ("Completed", "")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Request", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Student", "pairs": [
                            ("Name", "Maria L. Santos"),
                            ("Student number", "2023-00123"),
                            ("College", "College of Computing and Information Sciences"),
                            ("Program and year", "BS Information Technology · 3rd year"),
                        ]},
                        {"heading": "Appointment", "pairs": [
                            ("Date", "18 November 2026 (Wednesday)"),
                            ("Time", "10:00 – 10:30"),
                            ("Type", "Academic counseling (30 minutes)"),
                            ("Location", "Guidance Office, Room 204"),
                            ("Reason given", "I would like to discuss my scholarship "
                                             "requirements and whether my current load "
                                             "affects eligibility."),
                            ("Status", "Requested"),
                        ]},
                    ]},
                ]},
                {"type": "card", "title": "History", "items": [
                    {"type": "timeline", "items": [
                        ("13 Nov 2026 15:40", "Requested",
                         "Requested by Maria L. Santos through the student portal."),
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Decision", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="margin-bottom:10px">
  Confirming reserves the slot and notifies the student. Rejecting requires a reason.</div>
<div style="display:grid;gap:8px">
  <div class="field">
    <label>Rejection reason <span class="req">*</span></label>
    <div class="ctl area"><span class="ph">Required when rejecting. The student sees this
      text.</span></div>
  </div>
  <button class="wf-btn primary" style="justify-content:center">Confirm appointment</button>
  <button class="wf-btn" style="justify-content:center">Reject request</button>
</div>"""},
                ]},
                {"type": "card", "title": "Private counselor notes", "items": [
                    {"type": "html", "html": """
<div class="field">
  <label>Notes</label>
  <div class="ctl area"><span class="ph">Working notes for your own reference. Not shown to
    the student.</span></div>
  <div class="fhelp">Saved with the appointment and visible only to you and administrators.</div>
</div>
<div class="row" style="margin-top:10px">
  <button class="wf-btn sm">Save notes</button></div>"""},
                ]},
            ]},
            {"type": "notice", "kind": "warn", "title": "Completion is irreversible",
             "text": "Once an appointment is marked completed it cannot return to confirmed, "
                     "because the state machine has no reverse transition."},
        ],
    ),

    dict(
        id="WF-C-05", title="Enrollment verification queue", role="counselor", folder="counselor",
        nav_active="enrollment-verifications.index", route="enrollment-verifications.index",
        module="M05",
        reqs="FR-RBAC-04, FR-ENR-01, FR-ENR-04, FR-ENR-06",
        purpose="Review the enrollment-verification records for the current term and open any "
                "record to record a decision.",
        annotations=[
            "Both counselors and administrators reach this screen; the queue is not scoped to "
            "the counselor's own students because no such relationship exists in the schema.",
            "Status filter defaults to pending, because decided records need no action.",
            "Current term is highlighted from configuration; other terms remain visible but are "
            "not actionable for the current gate.",
            "The student's profile enrollment status is shown alongside the record status so a "
            "mismatch is visible.",
            "GAP-01: no screen in the application creates these records. This queue reviews "
            "records that exist; it cannot create them.",
            "Pagination is server-side with 20 rows per page.",
        ],
        responsive=[
            ("Desktop 1440", "Filter bar plus a six-column table."),
            ("Tablet 1024", "Term column folds into the student cell as a secondary line."),
            ("Mobile 390", "Rows become cards with the status badge in the header."),
        ],
        blocks=[
            {"type": "header", "title": "Enrollment verification",
             "subtitle": "Term 2025–2026 · Second semester · records shown for the current term "
                         "unless another term is selected.",
             "actions": [("Export list", "ghost")]},
            {"type": "notice", "kind": "warn", "title": "Records cannot be created here",
             "text": "This screen reviews verification records that already exist. No route in "
                     "the delivered application creates a new record (GAP-01), so a missing "
                     "student must be added out of band until an intake screen is delivered."},
            {"type": "filters",
             "fields": [("Search student", "search"),
                        ("Status", "select", ["Pending", "Verified", "Rejected",
                                              "Unable to verify", "All statuses"]),
                        ("Academic year", "select", ["2025–2026", "2024–2025"]),
                        ("Semester", "select", ["Second semester", "First semester"])],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Student", "Student no.", "College", "Submitted", "Record", ""],
             "rows": [
                 ["Maria L. Santos", "2023-00123", "CCIS", "10 Nov 2026", "pending", ""],
                 ["Jose P. Rizal", "2022-00456", "CENG", "10 Nov 2026", "pending", ""],
                 ["Ana C. Reyes", "2024-00991", "CTE", "09 Nov 2026", "verified", ""],
                 ["Mark T. Lim", "2021-00311", "CBA", "08 Nov 2026", "rejected", ""],
                 ["Grace U. Tan", "2023-00777", "CCIS", "08 Nov 2026", "unable_to_verify", ""],
             ],
             "status_col": 4, "actions": ["Review"]},
            {"type": "pager", "current": 1, "total": 2, "count": "5 of 32 records"},
        ],
    ),

    dict(
        id="WF-C-06", title="Enrollment verification review", role="counselor", folder="counselor",
        nav_active="enrollment-verifications.index", route="enrollment-verifications.show",
        module="M05",
        reqs="FR-ENR-03, FR-ENR-05, BR-22, BR-23",
        purpose="Record the enrollment decision for one student and term, with the reason that "
                "the student will act on.",
        annotations=[
            "Three outcomes: verified, rejected, unable to verify.",
            "Notes are mandatory for rejected and unable to verify, and optional for verified.",
            "Saving updates the record and the student's profile enrollment status in one "
            "transaction.",
            "The decision is attributed to the signed-in user and stamped with a timestamp.",
            "Verification is term-scoped: it satisfies the enrollment gate only for the "
            "configured academic year and semester.",
            "The student is notified of the outcome, so the reason text is written for them.",
        ],
        responsive=[
            ("Desktop 1440", "Record summary left, decision form right."),
            ("Tablet 1024", "Decision form moves under the summary."),
            ("Mobile 390", "Single column with the student summary first."),
        ],
        blocks=[
            {"type": "header", "title": "Verify enrollment · Maria L. Santos",
             "subtitle": "2025–2026 · Second semester · record created 10 Nov 2026",
             "actions": [("Back to queue", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Student and record", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Student", "pairs": [
                            ("Name", "Maria L. Santos"),
                            ("Student number", "2023-00123"),
                            ("College", "College of Computing and Information Sciences"),
                            ("Program and year", "BS Information Technology · 3rd year"),
                            ("Profile enrollment status", "Unknown"),
                        ]},
                        {"heading": "Record", "pairs": [
                            ("Academic year", "2025–2026"),
                            ("Semester", "Second semester"),
                            ("Current status", "Pending"),
                            ("Created", "10 Nov 2026"),
                            ("Previously decided by", "—"),
                        ]},
                    ]},
                ]},
                {"type": "card", "title": "Verification history for this student", "items": [
                    {"type": "timeline", "items": [
                        ("05 Nov 2026", "Verified · 2024–2025, Second semester",
                         "Decided by Prof. R. Dela Cruz."),
                        ("12 Aug 2026", "Verified · 2024–2025, First semester",
                         "Decided by A. Bautista."),
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Record a decision", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Decision", "type": "select", "span": 2, "required": True,
                             "options": ["Verified — student is enrolled",
                                         "Rejected — student is not enrolled",
                                         "Unable to verify"]},
                            {"label": "Reason or notes", "type": "textarea", "span": 2,
                             "placeholder": "Required for rejected and unable to verify.",
                             "help": "The student sees this text in the notification."},
                        ]},
                    ], "buttons": [("Save decision", "primary"), ("Cancel", "outline")]},
                ]},
                {"type": "notice", "kind": "info", "text":
                 "The decision applies only to the term shown. A new decision is required next "
                 "semester."},
            ]},
        ],
    ),
]
