"""Wireframe page specs: administrator screens, part B (WF-A-14 … WF-A-27)."""

PAGES = [
    dict(
        id="WF-A-14", title="Guidance appointment types", role="admin", folder="admin",
        nav_active="admin.guidance.appointments", route="admin.guidance.types",
        module="M11",
        reqs="FR-GUID-03, BR-15",
        purpose="Maintain the catalogue of appointment types, their default duration and the "
                "order in which students see them.",
        annotations=[
            "Each type has a name, a description, a duration in minutes, an active flag and a "
            "display order.",
            "Duration is what the slot generator uses; changing it changes future slot grids but "
            "not existing appointments.",
            "An inactive type disappears from the student booking form but remains on historical "
            "appointments.",
            "Display order controls the order in the type selector, not the slot order.",
            "A type with existing appointments cannot be deleted, only deactivated.",
            "Duration must be a positive number of minutes within the office day.",
        ],
        responsive=[
            ("Desktop 1440", "Table left, create form right."),
            ("Tablet 1024", "Form moves above the table."),
            ("Mobile 390", "Each type becomes a card with edit and deactivate actions."),
        ],
        blocks=[
            {"type": "header", "title": "Guidance appointment types",
             "subtitle": "Types offered to students when booking. Duration drives the slot grid.",
             "actions": [("Back to guidance", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Configured types", "items": [
                    {"type": "table",
                     "columns": ["Order", "Type", "Duration", "Appointments", "State", ""],
                     "rows": [
                         ["1", "Academic counseling", "30 min", "64", "active", ""],
                         ["2", "Course selection", "30 min", "22", "active", ""],
                         ["3", "Personal counseling", "45 min", "31", "active", ""],
                         ["4", "Career counseling", "45 min", "12", "active", ""],
                         ["5", "Scholarship guidance", "45 min", "8", "inactive", ""],
                     ],
                     "status_col": 4, "actions": ["Edit", "Deactivate"]},
                ]},
            ], "right": [
                {"type": "card", "title": "Add a type", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Name", "required": True, "span": 2,
                             "placeholder": "Academic counseling"},
                            {"label": "Description", "type": "textarea", "span": 2,
                             "placeholder": "What the student can expect from this session."},
                            {"label": "Duration (minutes)", "required": True, "placeholder": "30"},
                            {"label": "Display order", "placeholder": "6"},
                            {"label": "State", "type": "select",
                             "options": ["Active", "Inactive"]},
                        ]},
                    ], "buttons": [("Add type", "primary"), ("Cancel", "outline")]},
                ]},
            ]},
            {"type": "notice", "kind": "info", "text":
             "Changing a duration affects only newly generated slots. Appointments already booked "
             "keep the start and end times they were created with."},
        ],
    ),

    dict(
        id="WF-A-15", title="Guidance scheduling settings", role="admin", folder="admin",
        nav_active="admin.guidance.appointments", route="admin.guidance.settings",
        module="M11",
        reqs="FR-GUID-04, BR-16, BR-17",
        purpose="Set the office hours, default duration, minimum booking notice, cancellation "
                "notice and booking horizon used by the slot generator.",
        annotations=[
            "Office start must precede office end; both are time values on a 24-hour clock.",
            "Default duration applies when an appointment type does not specify one.",
            "Minimum booking notice prevents a student booking a slot a few minutes ahead.",
            "Cancellation notice is the cut-off after which the student must contact the office.",
            "Booking horizon caps how far ahead a slot can be requested.",
            "Only one active settings row applies; saving supersedes the previous configuration.",
        ],
        responsive=[
            ("Desktop 1440", "Two setting cards side by side, save bar beneath."),
            ("Tablet 1024", "Cards stack vertically in the order they affect a booking."),
            ("Mobile 390", "Single column with the derived effect shown under each field."),
        ],
        blocks=[
            {"type": "header", "title": "Guidance scheduling settings",
             "subtitle": "These values apply to every counselor and every appointment type.",
             "actions": [("Appointment types", "ghost")]},
            {"type": "grid", "cols": 2, "items": [
                {"type": "card", "title": "Office hours", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Office start time", "required": True, "placeholder": "08:00"},
                            {"label": "Office end time", "required": True, "placeholder": "17:00"},
                            {"label": "Default duration (minutes)", "required": True,
                             "placeholder": "30"},
                            {"label": "Settings state", "type": "select",
                             "options": ["Active", "Inactive"]},
                        ]},
                    ], "buttons": [("Save settings", "primary"), ("Cancel", "outline")]},
                ]},
                {"type": "card", "title": "Booking rules", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Minimum booking notice (minutes)", "required": True,
                             "placeholder": "240",
                             "help": "A slot starting sooner than this cannot be requested."},
                            {"label": "Student cancellation notice (minutes)", "required": True,
                             "placeholder": "1440",
                             "help": "After this cut-off the student can no longer cancel."},
                            {"label": "Maximum future booking (days)", "required": True,
                             "placeholder": "30"},
                        ]},
                    ], "buttons": [("Save rules", "primary"), ("Cancel", "outline")]},
                ]},
            ]},
            {"type": "card", "title": "Effect on a booking", "items": [
                {"type": "html", "html": """
<div class="small muted" style="display:grid;gap:7px">
  <span>A student looking at today at 07:00 sees no slot before 11:00, because the minimum
  booking notice is 240 minutes.</span>
  <span>A student cannot request a date more than 30 days ahead.</span>
  <span>A confirmed appointment can be cancelled by the student until 24 hours before it
  starts; afterwards the Guidance Office must intervene.</span>
  <span>Slots are only generated inside 08:00 – 17:00 and inside a published availability
  block.</span>
</div>"""},
            ]},
            {"type": "notice", "kind": "warn", "title": "Changes are immediate",
             "text": "Settings apply to the next slot request. Appointments already booked are "
                     "not rescheduled automatically."},
        ],
    ),

    dict(
        id="WF-A-16", title="Guidance oversight", role="admin", folder="admin",
        nav_active="admin.guidance.appointments", route="admin.guidance.appointments",
        module="M11",
        reqs="FR-GUID-11, NFR-PERF-03",
        purpose="Give the administrator a read-only view of every appointment across all "
                "counselors for monitoring and reporting.",
        annotations=[
            "Unlike the counselor queue, this list is not scoped to one counselor.",
            "Filters cover counselor, status, appointment type and date range.",
            "The administrator does not confirm, reject or complete appointments; those "
            "decisions belong to the assigned counselor (GAP-08: administrative view is "
            "read-only).",
            "Counselor notes are not rendered here; they remain private to the counselor.",
            "Volume and no-show patterns are visible through status counts, not per-student "
            "commentary.",
            "Export is offered for reporting; the export uses the same filters as the view.",
        ],
        responsive=[
            ("Desktop 1440", "Filter bar plus a seven-column table."),
            ("Tablet 1024", "Type folds under the counselor name."),
            ("Mobile 390", "Cards ordered by date, with status and counselor in the header."),
        ],
        blocks=[
            {"type": "header", "title": "Guidance oversight",
             "subtitle": "All appointments across counselors · 15 Nov 2026",
             "actions": [("Export CSV", "ghost"), ("Scheduling settings", "ghost")]},
            {"type": "notice", "kind": "info", "text":
             "This screen is read-only. Only the assigned counselor can confirm, reject or "
             "complete an appointment."},
            {"type": "filters",
             "fields": [("Search student", "search"),
                        ("Counselor", "select", ["All counselors", "Prof. R. Dela Cruz",
                                                 "Prof. J. Ramirez", "Ms. L. Aquino"]),
                        ("Status", "select", ["All statuses", "Requested", "Confirmed",
                                              "Completed", "Rejected", "Cancelled"]),
                        ("From", "date"), ("To", "date")],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Date", "Time", "Student", "Counselor", "Type", "Status", ""],
             "rows": [
                 ["18 Nov 2026", "10:00 – 10:30", "Maria L. Santos", "Prof. R. Dela Cruz",
                  "Academic counseling", "confirmed", ""],
                 ["18 Nov 2026", "13:30 – 14:00", "Ana C. Reyes", "Prof. J. Ramirez",
                  "Personal counseling", "requested", ""],
                 ["19 Nov 2026", "14:30 – 15:00", "Jose P. Rizal", "Prof. R. Dela Cruz",
                  "Course selection", "requested", ""],
                 ["15 Nov 2026", "09:00 – 09:30", "Ana C. Reyes", "Prof. R. Dela Cruz",
                  "Personal counseling", "completed", ""],
                 ["14 Nov 2026", "11:00 – 11:45", "Mark T. Lim", "Ms. L. Aquino",
                  "Career counseling", "cancelled", ""],
             ],
             "status_col": 5, "actions": ["View"]},
            {"type": "pager", "current": 1, "total": 9, "count": "5 of 110 appointments"},
        ],
    ),

    dict(
        id="WF-A-17", title="Guidance oversight detail", role="admin", folder="admin",
        nav_active="admin.guidance.appointments", route="admin.guidance.appointments.show",
        module="M11",
        reqs="FR-GUID-11, FR-AUDIT-03",
        purpose="Show one appointment in full for oversight, including its decision history, "
                "without offering any state change.",
        annotations=[
            "Every field is read-only; there is no decision form on this screen.",
            "The student's stated reason is visible; the counselor's private notes are not.",
            "Rejection or cancellation reasons are shown because the student has already seen "
            "them.",
            "History records actor, timestamp and remarks, matching the audit record.",
            "The reminder timestamp is shown so a missing reminder is diagnosable.",
            "An explicit banner states that oversight is read-only, so the absence of buttons is "
            "not mistaken for a permission fault.",
        ],
        responsive=[
            ("Desktop 1440", "Details left, metadata and history right."),
            ("Tablet 1024", "Metadata moves below the details."),
            ("Mobile 390", "Single column; status banner first."),
        ],
        blocks=[
            {"type": "header", "title": "Appointment · 18 Nov 2026, 10:00",
             "subtitle": "Maria L. Santos · Prof. R. Dela Cruz · Academic counseling",
             "actions": [("Back to oversight", "ghost")]},
            {"type": "notice", "kind": "info", "title": "Read-only view",
             "text": "Administrators monitor appointments here. Confirmation, rejection and "
                     "completion are performed by the assigned counselor."},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Appointment", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Schedule", "pairs": [
                            ("Date", "18 November 2026 (Wednesday)"),
                            ("Time", "10:00 – 10:30"),
                            ("Duration", "30 minutes"),
                            ("Location", "Guidance Office, Room 204"),
                        ]},
                        {"heading": "Participants", "pairs": [
                            ("Student", "Maria L. Santos · 2023-00123"),
                            ("Counselor", "Prof. R. Dela Cruz"),
                            ("Type", "Academic counseling"),
                        ]},
                        {"heading": "Session", "pairs": [
                            ("Status", "Confirmed"),
                            ("Student's reason", "I would like to discuss my scholarship "
                                                 "requirements and whether my current load "
                                                 "affects eligibility."),
                            ("Rejection reason", "—"),
                            ("Reminder sent", "15 Nov 2026, 10:00"),
                        ]},
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "History", "items": [
                    {"type": "timeline", "items": [
                        ("14 Nov 2026 09:02", "Confirmed",
                         "Confirmed by Prof. R. Dela Cruz."),
                        ("13 Nov 2026 15:40", "Requested",
                         "Requested by Maria L. Santos."),
                    ]},
                ]},
                {"type": "card", "title": "Record metadata", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Record id", "APT-000412"),
                        ("Created", "13 Nov 2026, 15:40"),
                        ("Last updated", "14 Nov 2026, 09:02"),
                        ("Blocking", "Yes — occupies the slot"),
                    ]}]},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-A-18", title="Service types", role="admin", folder="admin",
        nav_active="admin.service-types.index", route="admin.service-types.index",
        module="M12",
        reqs="FR-SVC-02, BR-13",
        purpose="Maintain the OSAS service catalogue: what students can request, the "
                "instructions they see, and the attachments each service demands.",
        annotations=[
            "Each type has a name, description, instructions, active flag, display order and a "
            "set of requirements.",
            "Only active types appear in the student catalogue, ordered by display order.",
            "The instructions field is shown verbatim to students, so it is written for them.",
            "Requirement count is shown per type so the attachment burden is visible.",
            "A type with existing requests cannot be deleted, only deactivated.",
            "Display order is edited here, not by drag and drop, so the order is explicit and "
            "auditable.",
        ],
        responsive=[
            ("Desktop 1440", "Table with a requirements column and row actions."),
            ("Tablet 1024", "Instructions preview folds into a tooltip-style secondary line."),
            ("Mobile 390", "Cards with the requirement count as a badge."),
        ],
        blocks=[
            {"type": "header", "title": "Service types",
             "subtitle": "4 active services · 1 inactive · 48 requests this term",
             "actions": [("New service type", "primary")]},
            {"type": "table",
             "columns": ["Order", "Service", "Instructions", "Requirements", "State", ""],
             "rows": [
                 ["1", "Good Moral Certificate", "Bring a valid student ID when claiming.",
                  "1 required", "active", ""],
                 ["2", "Certificate of Enrollment", "Issued for the current term only.",
                  "None", "active", ""],
                 ["3", "Financial Assistance", "Subject to fund availability.",
                  "2 required", "active", ""],
                 ["4", "Lost ID Replacement Slip", "Valid for 30 days.",
                  "1 required", "active", ""],
                 ["5", "Transcript Request", "Handled by the Registrar.",
                  "None", "inactive", ""],
             ],
             "status_col": 4, "actions": ["Edit", "Requirements", "Deactivate"]},
            {"type": "notice", "kind": "info", "text":
             "A service type with existing requests is deactivated rather than deleted, so "
             "historical requests keep a readable service name."},
        ],
    ),

    dict(
        id="WF-A-19", title="Service type create and edit", role="admin", folder="admin",
        nav_active="admin.service-types.index", route="admin.service-types.create / "
        "admin.service-types.edit", module="M12",
        reqs="FR-SVC-02, BR-13, BR-24",
        purpose="Define a service type, its student-facing instructions and the ordered "
                "attachments a request must supply.",
        annotations=[
            "Name, description and instructions are all student-visible text.",
            "Instructions are displayed on the catalogue card and again on the request form.",
            "Requirements are ordered; the order determines how attachments are requested.",
            "A requirement marked required is enforced at submission time.",
            "Deactivating hides the service from students without affecting open requests.",
            "Display order places the service in the catalogue; lower numbers appear first.",
        ],
        responsive=[
            ("Desktop 1440", "Identity form left, requirements manager right."),
            ("Tablet 1024", "Requirements manager moves below the identity form."),
            ("Mobile 390", "Single column; each requirement is an edit-and-delete row."),
        ],
        blocks=[
            {"type": "header", "title": "Edit service type",
             "subtitle": "Good Moral Certificate · 12 requests this term",
             "actions": [("Back to service types", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Service details", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Service name", "required": True, "span": 2,
                             "placeholder": "Good Moral Certificate"},
                            {"label": "Short description", "type": "textarea", "span": 2,
                             "placeholder": "One sentence shown on the catalogue card."},
                            {"label": "Instructions for the student", "type": "textarea",
                             "span": 2,
                             "placeholder": "What to prepare, how long it takes, where to claim."},
                            {"label": "Display order", "placeholder": "1"},
                            {"label": "State", "type": "select",
                             "options": ["Active", "Inactive"]},
                        ]},
                    ], "buttons": [("Save changes", "primary"), ("Cancel", "outline")]},
                ]},
            ], "right": [
                {"type": "card", "title": "Required attachments", "items": [
                    {"type": "table", "columns": ["Order", "Requirement", "Needed", ""],
                     "rows": [
                         ["1", "Valid student ID", "Required", ""],
                         ["2", "Barangay certification", "Optional", ""],
                     ], "status_col": 2, "actions": ["Edit", "Delete"]},
                    {"type": "html", "html": """
<div class="field" style="margin-top:12px">
  <label>Add a requirement</label>
  <div class="ctl"><span class="ph">Requirement name</span></div>
</div>
<div class="row" style="margin-top:8px">
  <div class="ctl" style="flex:1"><span>Required</span>
    <span class="car">&#9660;</span></div>
  <button class="wf-btn sm primary">Add</button></div>"""},
                ]},
                {"type": "card", "title": "Submission rule", "items": [
                    {"type": "html", "html": """
<div class="small muted">A request cannot be submitted without every required attachment.
Accepted formats are PDF, JPG, JPEG and PNG, at most 5 MB per file. The description must be
at least 10 characters.</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-A-20", title="Service request queue", role="admin", folder="admin",
        nav_active="admin.student-service-requests.index",
        route="admin.student-service-requests.index", module="M12",
        reqs="FR-SVC-06, NFR-PERF-03",
        purpose="List all service requests so unassigned and stalled requests can be found and "
                "opened for processing.",
        annotations=[
            "Tabs follow the request vocabulary: submitted, processing, requires information, "
            "completed and cancelled.",
            "Handler is shown so unassigned requests are visible at a glance.",
            "Filtering by service type and date range matches the student-side filter labels.",
            "Requests requiring further information from the student are counted prominently.",
            "The queue is sorted oldest-first within the active tab to prevent starvation.",
            "No bulk transition is offered; each decision is made on the detail screen.",
        ],
        responsive=[
            ("Desktop 1440", "Tabs, filters, six-column table."),
            ("Tablet 1024", "Service type folds under the request number."),
            ("Mobile 390", "Cards with request number, service, handler and status."),
        ],
        blocks=[
            {"type": "header", "title": "Service requests",
             "subtitle": "27 open requests · 5 awaiting student information",
             "actions": [("Service types", "ghost"), ("Export CSV", "ghost")]},
            {"type": "tabs", "items": [("Submitted (8)", True), ("Processing (11)", False),
                                       ("Requires information (5)", False),
                                       ("Completed (34)", False), ("Cancelled (3)", False)]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("Service type", "select", ["All services", "Good Moral Certificate",
                                                    "Certificate of Enrollment",
                                                    "Financial Assistance",
                                                    "Lost ID Replacement Slip"]),
                        ("Handler", "select", ["Any handler", "Assigned", "Unassigned"]),
                        ("Submitted from", "date")],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Request no.", "Student", "Service type", "Submitted", "Handler",
                         "Status", ""],
             "rows": [
                 ["SR-2026-000042", "Maria L. Santos", "Good Moral Certificate",
                  "14 Nov 2026", "A. Bautista", "requires_information", ""],
                 ["SR-2026-000041", "Jose P. Rizal", "Certificate of Enrollment",
                  "14 Nov 2026", "Unassigned", "submitted", ""],
                 ["SR-2026-000038", "Ana C. Reyes", "Financial Assistance",
                  "13 Nov 2026", "A. Bautista", "processing", ""],
                 ["SR-2026-000035", "Mark T. Lim", "Lost ID Replacement Slip",
                  "12 Nov 2026", "Unassigned", "submitted", ""],
             ],
             "status_col": 5, "actions": ["Open"]},
            {"type": "pager", "current": 1, "total": 2, "count": "4 of 27 requests"},
        ],
    ),

    dict(
        id="WF-A-21", title="Service request detail and assignment", role="admin", folder="admin",
        nav_active="admin.student-service-requests.index",
        route="admin.student-service-requests.show", module="M12",
        reqs="FR-SVC-07, FR-SVC-10, NFR-USA-04, BR-14",
        purpose="Process one request: assign a handler, read the student's documents, record "
                "remarks and advance the request along a permitted transition.",
        annotations=[
            "Assigning a handler and changing status are separate controls because they are "
            "separate decisions.",
            "Remarks are mandatory when moving to requires_information or completed; the form "
            "states this before submission.",
            "Student-visible remarks and internal remarks are separate fields; the student never "
            "sees the internal field.",
            "Only transitions permitted by the request state machine are listed.",
            "Attachments are downloadable through an authorised route; no storage path is shown.",
            "Completion and cancellation stamp a timestamp and the acting user.",
        ],
        responsive=[
            ("Desktop 1440", "Details and documents left, processing panel right."),
            ("Tablet 1024", "Processing panel moves below the documents."),
            ("Mobile 390", "Single column; status and handler actions come first."),
        ],
        blocks=[
            {"type": "header", "title": "SR-2026-000042",
             "subtitle": "Maria L. Santos · Good Moral Certificate · submitted 14 Nov 2026",
             "actions": [("Back to queue", "ghost")]},
            {"type": "steps", "items": [("Submitted", "done"), ("Processing", "done"),
                                        ("Information required", "now"), ("Completed", "")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Request", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Student", "pairs": [
                            ("Name", "Maria L. Santos"),
                            ("Student number", "2023-00123"),
                            ("College", "College of Computing and Information Sciences"),
                            ("Enrollment", "Verified for the current term"),
                        ]},
                        {"heading": "Request", "pairs": [
                            ("Service type", "Good Moral Certificate"),
                            ("Description", "Needed for a scholarship application with a "
                                            "private foundation. The foundation requires a "
                                            "certificate dated this month."),
                            ("Submitted", "14 Nov 2026, 08:41"),
                            ("Handler", "A. Bautista"),
                            ("Status", "Requires information"),
                        ]},
                    ]},
                ]},
                {"type": "card", "title": "Attachments", "items": [
                    {"type": "table", "columns": ["Requirement", "File", "Size", ""],
                     "rows": [
                         ["Valid student ID", "id_front.jpg", "412 KB", ""],
                         ["Barangay certification", "—", "—", ""],
                     ], "actions": ["Download"]},
                ]},
                {"type": "card", "title": "History", "items": [
                    {"type": "timeline", "items": [
                        ("15 Nov 2026 10:12", "Additional information required",
                         "Requested by A. Bautista: student ID scan is illegible."),
                        ("14 Nov 2026 09:05", "Processing started", "Assigned to A. Bautista."),
                        ("14 Nov 2026 08:41", "Submitted", "Submitted by Maria L. Santos."),
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Assignment", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Handler", "type": "select", "span": 2,
                             "options": ["A. Bautista", "Unassigned", "M. Cruz"]},
                        ]},
                    ], "buttons": [("Save assignment", "primary")]},
                ]},
                {"type": "card", "title": "Status transition", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Next status", "type": "select", "span": 2,
                             "options": ["Processing", "Completed", "Cancelled"],
                             "help": "Only transitions permitted from requires_information "
                                     "are listed."},
                            {"label": "Remarks for the student", "type": "textarea", "span": 2,
                             "required": True,
                             "placeholder": "Mandatory when completing or requesting "
                                            "information.",
                             "help": "Shown in the student's request detail and notification."},
                            {"label": "Internal remarks", "type": "textarea", "span": 2,
                             "placeholder": "Never shown to the student."},
                        ]},
                    ], "buttons": [("Apply transition", "primary")]},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-A-22", title="Audit log", role="admin", folder="admin",
        nav_active="admin.audit-logs.index", route="admin.audit-logs.index",
        module="M15",
        reqs="FR-AUDIT-01, FR-AUDIT-02, FR-AUDIT-03, FR-AUDIT-05, NFR-SEC-08",
        purpose="Provide a searchable, append-only record of security-relevant and "
                "state-changing actions, filterable by actor, action and module.",
        annotations=[
            "Columns are time, actor, action, module, record and IP address.",
            "Filters cover date range, actor, action and module; the search box matches the "
            "record description.",
            "The log is read-only: no row can be edited or deleted from any screen.",
            "Login failures, blocked inactive logins, password resets and workflow transitions "
            "are all recorded.",
            "Record identifiers are shown but are not links, because the target may since have "
            "been deleted or moved out of scope.",
            "Retention is a database concern; the screen does not offer pruning.",
        ],
        responsive=[
            ("Desktop 1440", "Filter bar plus a six-column table."),
            ("Tablet 1024", "IP address folds under the actor."),
            ("Mobile 390", "Cards with action, actor and timestamp; description wraps."),
        ],
        blocks=[
            {"type": "header", "title": "Audit log",
             "subtitle": "Append-only record of access and state changes.",
             "actions": [("Export CSV", "ghost")]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("Action", "select", ["All actions", "login", "login_failed",
                                              "logout", "password_reset",
                                              "application.transition",
                                              "service_request.transition",
                                              "appointment.transition"]),
                        ("Module", "select", ["All modules", "Authentication", "Scholarships",
                                              "Guidance", "Services", "Accounts"]),
                        ("From", "date"), ("To", "date")],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Time", "Actor", "Action", "Module", "Record", "IP address", ""],
             "rows": [
                 ["15 Nov 2026 07:52", "A. Bautista", "application.transition", "Scholarships",
                  "SCH-2026-000177", "10.20.4.18", ""],
                 ["15 Nov 2026 07:40", "A. Bautista", "scholarship.publish", "Scholarships",
                  "CHED Merit Scholarship", "10.20.4.18", ""],
                 ["15 Nov 2026 07:31", "m.santos@isu.edu.ph", "login", "Authentication",
                  "user 1284", "10.20.9.77", ""],
                 ["15 Nov 2026 07:29", "unknown", "login_failed", "Authentication",
                  "m.santos@isu.edu.ph", "10.20.9.77", ""],
                 ["14 Nov 2026 16:18", "A. Bautista", "document.reject", "Scholarships",
                  "AppDoc 8841", "10.20.4.18", ""],
             ],
             "actions": ["View"]},
            {"type": "pager", "current": 1, "total": 6, "count": "5 of 8,412 entries"},
            {"type": "notice", "kind": "info", "text":
             "The audit log cannot be edited or cleared from the application. Any correction is "
             "recorded as a new entry."},
        ],
    ),

    dict(
        id="WF-A-23", title="Audit log detail", role="admin", folder="admin",
        nav_active="admin.audit-logs.index", route="admin.audit-logs.show",
        module="M15",
        reqs="FR-AUDIT-04, NFR-SEC-08",
        purpose="Show one audit entry in full, including the request context captured with it.",
        annotations=[
            "All captured context is shown: actor, action, module, record type and id, "
            "description, IP address, user agent and timestamp.",
            "The description is displayed as stored, without interpretation.",
            "The entry cannot be modified or deleted; there are no actions on this screen.",
            "Related entries for the same record can be listed to reconstruct a sequence.",
            "A missing or deleted target record is stated plainly rather than shown as an error.",
            "IP address and user agent are shown because they are part of the security record.",
        ],
        responsive=[
            ("Desktop 1440", "Definition list left, related entries right."),
            ("Tablet 1024", "Related entries move below."),
            ("Mobile 390", "Single column, label above value."),
        ],
        blocks=[
            {"type": "header", "title": "Audit entry · 8,411",
             "subtitle": "application.transition · Scholarships · 15 Nov 2026, 07:52:14",
             "actions": [("Back to log", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Entry", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "What happened", "pairs": [
                            ("Action", "application.transition"),
                            ("Module", "Scholarships"),
                            ("Description", "Moved SCH-2026-000177 from "
                                            "document_verification to approved."),
                            ("Record type", "ScholarshipApplication"),
                            ("Record id", "177"),
                        ]},
                        {"heading": "Who and how", "pairs": [
                            ("Actor", "A. Bautista (Administrator)"),
                            ("Actor id", "1"),
                            ("IP address", "10.20.4.18"),
                            ("User agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                                           "Chrome/131.0 Safari/537.36"),
                            ("Recorded at", "15 Nov 2026, 07:52:14"),
                        ]},
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Related entries for this record", "items": [
                    {"type": "timeline", "items": [
                        ("15 Nov 2026 07:52", "approved",
                         "application.transition by A. Bautista."),
                        ("14 Nov 2026 10:04", "document_verification",
                         "application.transition by A. Bautista."),
                        ("13 Nov 2026 08:19", "under_review",
                         "application.transition by A. Bautista."),
                        ("12 Nov 2026 09:14", "submitted",
                         "Application submitted by the student."),
                    ]},
                ]},
                {"type": "notice", "kind": "info", "text":
                 "Target records are not linked: the record may have moved or been removed, and "
                 "the audit entry must remain readable regardless."},
            ]},
        ],
    ),

    dict(
        id="WF-A-24", title="Analytics overview", role="admin", folder="admin",
        nav_active="admin.analytics.index", route="admin.analytics.index",
        module="M14",
        reqs="FR-ANLYT-01, FR-ANLYT-05, FR-ANLYT-06",
        purpose="Present volume and outcome metrics across scholarships, guidance and services "
                "for a chosen period, with an export of the same filtered dataset.",
        annotations=[
            "The period filter is the primary control and applies to every figure on the screen.",
            "Summary cards are counts, not rates, so they can be reconciled with the queues.",
            "Tabs switch between the three analytical views without losing the period filter.",
            "Export produces exactly the dataset on screen for the active period and tab.",
            "Charts are descriptive only; no forecasting is claimed.",
            "An invalid range is refused with an explicit message rather than silently "
            "defaulting.",
        ],
        responsive=[
            ("Desktop 1440", "Period filter bar, four cards, then two charts side by side."),
            ("Tablet 1024", "Cards in two columns; charts stack."),
            ("Mobile 390", "Single column; charts scroll horizontally where a series is dense."),
        ],
        blocks=[
            {"type": "header", "title": "Analytics and reports",
             "subtitle": "01 Jun 2026 – 15 Nov 2026 · all colleges · all scholarships",
             "actions": [("Export CSV", "primary")]},
            {"type": "tabs", "items": [("Overview", True), ("Scholarships", False),
                                       ("Guidance", False), ("Student services", False)]},
            {"type": "filters",
             "fields": [("From", "date"), ("To", "date"),
                        ("College", "select", ["All colleges", "CCIS", "CENG", "CTE", "CBA"]),
                        ("Scholarship", "select", ["All scholarships"])],
             "buttons": [("Apply period", "primary"), ("Reset", "outline")]},
            {"type": "stats", "cols": 4, "items": [
                ("Applications", "146", "In the selected period"),
                ("Approved", "41", "28% of applications"),
                ("Appointments", "110", "63 completed"),
                ("Service requests", "61", "34 completed"),
            ]},
            {"type": "grid", "cols": 2, "items": [
                {"type": "chart", "kind": "bars", "title": "Applications per month",
                 "sub": "Submitted in the selected period",
                 "labels": ["Jun", "Jul", "Aug", "Sep", "Oct", "Nov"],
                 "values": [12, 18, 24, 31, 38, 23]},
                {"type": "chart", "kind": "line", "title": "Service requests per week",
                 "sub": "Submitted in the selected period",
                 "labels": ["01 Jun", "15 Jun", "01 Jul", "15 Jul", "01 Aug", "15 Aug",
                            "01 Sep", "15 Sep", "01 Oct", "15 Oct", "01 Nov", "15 Nov"],
                 "values": [3, 5, 4, 6, 5, 7, 4, 6, 5, 6, 6, 4]},
            ]},
            {"type": "notice", "kind": "info", "text":
             "Export uses the same filters and produces identical totals. A report that "
             "disagrees with the screen is a defect, not a rounding difference."},
        ],
    ),

    dict(
        id="WF-A-25", title="Analytics — scholarships", role="admin", folder="admin",
        nav_active="admin.analytics.index", route="admin.analytics.scholarships",
        module="M14",
        reqs="FR-ANLYT-02",
        purpose="Break application volume and outcomes down by scholarship, provider and "
                "status for the selected period.",
        annotations=[
            "The same period filter as the overview applies and is carried in the URL.",
            "Table rows are scholarships; columns are volume, and counts per status.",
            "Counts per status sum to the volume for that scholarship.",
            "Provider grouping is available because providers request their own reports.",
            "No student-level data appears on this screen; it is aggregate only.",
            "Export matches the table exactly.",
        ],
        responsive=[
            ("Desktop 1440", "Chart above, full-width table below."),
            ("Tablet 1024", "Table scrolls horizontally; scholarship column pinned."),
            ("Mobile 390", "One card per scholarship with status counts as a compact grid."),
        ],
        blocks=[
            {"type": "header", "title": "Scholarship analytics",
             "subtitle": "Applications by scholarship and outcome · 01 Jun – 15 Nov 2026",
             "actions": [("Export CSV", "primary")]},
            {"type": "filters",
             "fields": [("From", "date"), ("To", "date"),
                        ("Provider", "select", ["All providers", "DOST", "CHED", "ISU",
                                                "OSAS", "Private"])],
             "buttons": [("Apply", "primary")]},
            {"type": "chart", "kind": "bars", "title": "Applications by scholarship",
             "sub": "Total submitted in the selected period",
             "labels": ["DOST-SEI", "CHED Merit", "ISU Presidential", "OSAS Emergency",
                        "Book Allowance"],
             "values": [54, 38, 31, 14, 9]},
            {"type": "table",
             "columns": ["Scholarship", "Provider", "Applications", "Approved", "Rejected",
                         "In progress"],
             "rows": [
                 ["DOST-SEI Undergraduate", "DOST", "54", "18", "4", "32"],
                 ["CHED Merit Scholarship", "CHED", "38", "12", "3", "23"],
                 ["ISU Presidential Grant", "ISU", "31", "9", "2", "20"],
                 ["OSAS Emergency Assistance", "OSAS", "14", "2", "0", "12"],
                 ["University Book Allowance", "ISU", "9", "0", "0", "9"],
             ], "actions": None},
            {"type": "pager", "current": 1, "total": 1, "count": "5 of 5 scholarships"},
        ],
    ),

    dict(
        id="WF-A-26", title="Analytics — guidance", role="admin", folder="admin",
        nav_active="admin.analytics.index", route="admin.analytics.guidance",
        module="M14",
        reqs="FR-ANLYT-03",
        purpose="Show appointment volume and outcomes by type, counselor and status for the "
                "selected period.",
        annotations=[
            "Outcome split reflects the appointment state machine: requested, confirmed, "
            "completed, rejected, cancelled.",
            "Counselor breakdown supports workload review; it contains no per-student detail.",
            "Type breakdown shows which services students actually use.",
            "Cancellation rate is derived and labelled as derived, not stored.",
            "The period filter is shared with the other analytics tabs.",
            "Export matches the on-screen table.",
        ],
        responsive=[
            ("Desktop 1440", "Two charts side by side, then a counselor table."),
            ("Tablet 1024", "Charts stack; table scrolls horizontally."),
            ("Mobile 390", "One chart at a time with the table rendered as cards."),
        ],
        blocks=[
            {"type": "header", "title": "Guidance analytics",
             "subtitle": "Appointments by type and counselor · 01 Jun – 15 Nov 2026",
             "actions": [("Export CSV", "primary")]},
            {"type": "filters",
             "fields": [("From", "date"), ("To", "date"),
                        ("Counselor", "select", ["All counselors", "Prof. R. Dela Cruz",
                                                 "Prof. J. Ramirez", "Ms. L. Aquino"])],
             "buttons": [("Apply", "primary")]},
            {"type": "grid", "cols": 2, "items": [
                {"type": "chart", "kind": "bars", "title": "Appointments by type",
                 "sub": "All statuses",
                 "labels": ["Academic", "Course", "Personal", "Career", "Scholarship"],
                 "values": [64, 22, 31, 12, 8]},
                {"type": "chart", "kind": "donut", "title": "Outcomes",
                 "sub": "All appointments in the period",
                 "labels": ["Completed", "Confirmed", "Requested", "Cancelled", "Rejected"],
                 "values": ["63", "27", "12", "8", "0"]},
            ]},
            {"type": "table",
             "columns": ["Counselor", "Appointments", "Completed", "Cancelled", "Cancellation rate"],
             "rows": [
                 ["Prof. R. Dela Cruz", "62", "39", "4", "6.5%"],
                 ["Prof. J. Ramirez", "31", "18", "3", "9.7%"],
                 ["Ms. L. Aquino", "17", "6", "1", "5.9%"],
             ], "actions": None},
            {"type": "notice", "kind": "info", "text":
             "Cancellation rate is completed as cancelled over total appointments in the period. "
             "It is derived on read and is not stored."},
        ],
    ),

    dict(
        id="WF-A-27", title="Analytics — student services", role="admin", folder="admin",
        nav_active="admin.analytics.index", route="admin.analytics.services",
        module="M14",
        reqs="FR-ANLYT-04",
        purpose="Show service-request volume by service type and status, including the "
                "proportion that needed further information.",
        annotations=[
            "Volume is grouped by service type, then split by current status.",
            "The requires_information count is highlighted because it represents work waiting on "
            "a student, not on the office.",
            "Median handling time is shown only where the sample is large enough to be meaningful.",
            "The period filter is shared with the other analytics tabs.",
            "No handler-level performance ranking is produced; the data is operational, not "
            "evaluative.",
            "Export matches the on-screen table.",
        ],
        responsive=[
            ("Desktop 1440", "Bar chart above a five-column table."),
            ("Tablet 1024", "Table scrolls horizontally."),
            ("Mobile 390", "One card per service type with status counts."),
        ],
        blocks=[
            {"type": "header", "title": "Student services analytics",
             "subtitle": "Service requests by type and status · 01 Jun – 15 Nov 2026",
             "actions": [("Export CSV", "primary")]},
            {"type": "filters",
             "fields": [("From", "date"), ("To", "date"),
                        ("Service type", "select", ["All services",
                                                    "Good Moral Certificate",
                                                    "Certificate of Enrollment",
                                                    "Financial Assistance",
                                                    "Lost ID Replacement Slip"])],
             "buttons": [("Apply", "primary")]},
            {"type": "chart", "kind": "bars", "title": "Requests by service type",
             "sub": "Total submitted in the selected period",
             "labels": ["Good Moral", "Enrollment", "Financial aid", "Lost ID"],
             "values": [24, 19, 11, 7]},
            {"type": "table",
             "columns": ["Service type", "Requests", "Completed", "Requires information",
                         "Median handling"],
             "rows": [
                 ["Good Moral Certificate", "24", "17", "3", "2 days"],
                 ["Certificate of Enrollment", "19", "15", "1", "1 day"],
                 ["Financial Assistance", "11", "4", "2", "5 days"],
                 ["Lost ID Replacement Slip", "7", "6", "0", "Same day"],
             ], "actions": None},
            {"type": "notice", "kind": "info", "text":
             "Requests in requires_information are waiting on the student. They are counted "
             "separately so the backlog is not misread as an office delay."},
        ],
    ),
]
