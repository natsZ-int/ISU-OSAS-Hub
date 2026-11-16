"""Wireframe page specs: student screens (WF-S-01 … WF-S-19)."""

PAGES = [
    dict(
        id="WF-S-01", title="Student dashboard", role="student", folder="student",
        nav_active="student.dashboard", route="student.dashboard", module="M03, M09, M11, M12",
        reqs="FR-PROF-02, FR-ENR-05, FR-APP-06, FR-GUID-05",
        purpose="Give the student one screen that answers: what can I apply for, what is "
                "happening to my applications, and what is scheduled next.",
        annotations=[
            "Four summary tiles: open scholarships, active applications, upcoming appointments, open requests.",
            "Enrollment banner appears only when verification is missing for the current term.",
            "Recommended scholarships are ranked by match score and capped at three cards.",
            "Upcoming appointments show date, time, counselor and status — never counselor notes.",
            "Recent applications table carries the human-readable application number, not the database id.",
            "Every tile and row links to the full screen; the dashboard itself is read-only.",
        ],
        responsive=[
            ("Desktop 1440", "Tiles in four columns; content grid of 2:1 with recommendations left."),
            ("Tablet 1024", "Tiles in two columns; the 2:1 split becomes stacked regions."),
            ("Mobile 390", "Single column; tiles stack; tables scroll horizontally inside their wrapper."),
        ],
        blocks=[
            {"type": "header", "title": "Good morning, Maria",
             "subtitle": "Second semester, A.Y. 2025–2026. Your enrollment is verified.",
             "actions": [("Browse scholarships", "primary")]},
            {"type": "stats", "cols": 4, "items": [
                ("Open scholarships", "4", "2 closing within 7 days"),
                ("Active applications", "2", "1 awaiting your document"),
                ("Upcoming appointments", "1", "18 Nov 2026, 10:00"),
                ("Open service requests", "1", "Additional information required"),
            ]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "cards", "cols": 3, "items": [
                    {"title": "DOST-SEI Undergraduate", "tag": "92% match", "tagkind": "solid",
                     "meta": "Deadline 30 Nov 2026 · Full tuition + stipend",
                     "body": "You meet every criterion. Two of three documents are ready.",
                     "footer": [("View", "primary"), ("Check eligibility", "ghost")]},
                    {"title": "CHED Merit Scholarship", "tag": "84% match", "tagkind": "solid",
                     "meta": "Deadline 15 Dec 2026 · Tuition subsidy",
                     "body": "GWA and income are met; document readiness is lower.",
                     "footer": [("View", "primary"), ("Check eligibility", "ghost")]},
                    {"title": "ISU Presidential Grant", "tag": "71% match",
                     "meta": "Deadline 30 Nov 2026 · Book allowance",
                     "body": "Potential match — one criterion is not yet met.",
                     "footer": [("View", "primary")]},
                ]},
            ], "right": [
                {"type": "card", "title": "Next appointment", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Date", "18 Nov 2026"),
                        ("Time", "10:00 – 10:30"),
                        ("Counselor", "Prof. R. Dela Cruz"),
                        ("Type", "Academic counseling"),
                        ("Status", "Confirmed"),
                    ]}]},
                    {"type": "html", "html": '<div class="row" style="margin-top:10px">'
                     '<button class="wf-btn sm primary">View appointment</button>'
                     '<button class="wf-btn sm">Cancel</button></div>'},
                ]},
            ]},
            {"type": "card", "title": "Recent applications",
             "actions": [("View all", "ghost")],
             "items": [{"type": "table",
                        "columns": ["Application no.", "Scholarship", "Submitted", "Status", ""],
                        "rows": [
                            ["SCH-2026-000184", "DOST-SEI Undergraduate", "12 Nov 2026", "under_review", ""],
                            ["SCH-2026-000121", "ISU Presidential Grant", "03 Oct 2026", "rejected", ""],
                        ],
                        "status_col": 3, "actions": ["View"]}]},
        ],
    ),

    dict(
        id="WF-S-02", title="My profile (view)", role="student", folder="student",
        nav_active="student.profile.show", route="student.profile.show", module="M03, M04, M05",
        reqs="FR-PROF-02, FR-PASS-03, FR-ENR-01",
        purpose="Show the student's own academic and personal record, the current enrollment "
                "verification state, and whether the profile is locked.",
        annotations=[
            "Read-only. The only write actions are Edit profile, Lock, and Passcode settings.",
            "Student number, college, program, year level and enrollment status are OSAS-managed.",
            "Enrollment verification panel shows the latest term decision and its reason.",
            "A locked profile hides field values until the passcode is entered.",
            "Verification history is a short list; older terms remain visible but are not editable.",
            "No passcode hash, password field or internal note is ever rendered here.",
        ],
        responsive=[
            ("Desktop 1440", "Two columns: identity and academic detail left, status panels right."),
            ("Tablet 1024", "Panels stack beneath the detail list."),
            ("Mobile 390", "Definition lists collapse to label-above-value pairs."),
        ],
        blocks=[
            {"type": "header", "title": "My profile",
             "subtitle": "Your record as OSAS holds it. Some fields are maintained by the office.",
             "actions": [("Edit profile", "primary"), ("Security and passcode", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Personal and academic details", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Identity", "pairs": [
                            ("Student number", "2023-00123"),
                            ("Full name", "Maria L. Santos"),
                            ("Email address", "m.santos@isu.edu.ph"),
                            ("Contact number", "0917 555 0142"),
                        ]},
                        {"heading": "Academic", "pairs": [
                            ("College", "College of Computing and Information Sciences"),
                            ("Program", "BS Information Technology"),
                            ("Year level", "3rd year"),
                            ("General weighted average", "1.42"),
                        ]},
                        {"heading": "Household", "pairs": [
                            ("Annual family income", "PHP 148,000.00"),
                            ("Home address", "Brgy. San Isidro, Echague, Isabela"),
                        ]},
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Enrollment verification", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Current term", "2025–2026 · Second semester"),
                        ("Status", "Verified"),
                        ("Decided by", "Prof. R. Dela Cruz"),
                        ("Decided on", "05 Nov 2026"),
                    ]}]},
                    {"type": "notice", "kind": "info", "text":
                     "Verification is valid only for the term shown. A new decision is required "
                     "every semester."},
                ]},
                {"type": "card", "title": "Profile protection", "items": [
                    {"type": "html", "html": """
<div class="small" style="display:grid;gap:8px">
  <div class="row"><span class="badge dash">Unlocked</span>
    <span class="muted">Visible in this session</span></div>
  <div class="muted">A passcode locks these fields when you use a shared computer.</div>
  <div class="row" style="margin-top:4px">
    <button class="wf-btn sm">Lock profile</button>
    <button class="wf-btn sm ghost">Set passcode</button></div>
</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-03", title="Edit profile", role="student", folder="student",
        nav_active="student.profile.show", route="student.profile.edit", module="M03",
        reqs="FR-PROF-03, BR-06, BR-07, NFR-USA-02",
        purpose="Let the student correct the four fields they own, while showing the "
                "OSAS-managed fields as read-only.",
        annotations=[
            "Only contact number, address, GWA and family income are editable.",
            "OSAS-managed fields are rendered disabled and labelled 'Managed by OSAS'.",
            "Submitting a crafted change to a locked field is rejected server-side as well.",
            "GWA is constrained to 1.00–5.00; income must be non-negative.",
            "Validation messages appear under the offending field and values are preserved.",
            "A passcode-protected profile must be unlocked before this form opens.",
        ],
        responsive=[
            ("Desktop 1440", "Two-column field grid in a max-820 px card."),
            ("Tablet 1024", "Two columns retained; help text wraps under each field."),
            ("Mobile 390", "Single column; disabled fields remain visually distinct."),
        ],
        blocks=[
            {"type": "header", "title": "Edit profile",
             "subtitle": "Correct your contact details and household information. "
                         "Academic identity is maintained by OSAS.",
             "actions": [("Back to profile", "ghost")]},
            {"type": "notice", "kind": "info", "text":
             "Requests to change your name, student number, program, year level or enrollment "
             "status must be filed with the OSAS office."},
            {"type": "card", "items": [
                {"type": "form", "sections": [
                    {"heading": "Editable details", "fields": [
                        {"label": "Contact number", "required": True, "placeholder": "09XX XXX XXXX"},
                        {"label": "Annual family income", "required": True, "placeholder": "148000.00",
                         "help": "Two decimals; used for eligibility screening."},
                        {"label": "General weighted average", "required": True, "placeholder": "1.42",
                         "help": "1.00 is highest, 5.00 is lowest.", "error":
                         "GWA must be between 1.00 and 5.00."},
                        {"label": "Home address", "type": "textarea", "span": 2,
                         "placeholder": "Street, barangay, municipality, province"},
                    ]},
                    {"heading": "Managed by OSAS (read-only)", "fields": [
                        {"label": "Student number", "placeholder": "2023-00123"},
                        {"label": "Full name", "placeholder": "Maria L. Santos"},
                        {"label": "College", "placeholder": "College of Computing and Information Sciences"},
                        {"label": "Program", "placeholder": "BS Information Technology"},
                        {"label": "Year level", "placeholder": "3rd year"},
                        {"label": "Enrollment status", "placeholder": "Enrolled"},
                    ]},
                ], "buttons": [("Save changes", "primary"), ("Cancel", "outline")]},
            ]},
        ],
    ),

    dict(
        id="WF-S-04", title="Profile security and passcode", role="student", folder="student",
        nav_active="student.profile.show", route="student.profile.security", module="M04",
        reqs="FR-PASS-01, FR-PASS-02, FR-PASS-03, NFR-SEC-05",
        purpose="Let the student set, change or remove a passcode that locks profile fields "
                "for the current session.",
        annotations=[
            "Three states are shown: no passcode set, passcode set and unlocked, passcode set and locked.",
            "Current passcode is required to change or remove an existing passcode.",
            "The stored value is a one-way hash; the plaintext is never displayed or returned.",
            "Unlock attempts are throttled and the remaining attempts are shown after a failure.",
            "Removing protection is a destructive action and requires confirmation.",
            "Losing the passcode requires an OSAS administrator to reset it (WF-A-06).",
        ],
        responsive=[
            ("Desktop 1440", "Two cards side by side: passcode form and current state."),
            ("Tablet 1024", "Cards stack, form first."),
            ("Mobile 390", "Single column; buttons become full width."),
        ],
        blocks=[
            {"type": "header", "title": "Profile security",
             "subtitle": "A passcode protects your profile on shared or public computers.",
             "actions": [("Back to profile", "ghost")]},
            {"type": "split", "left": [
                {"type": "card", "title": "Set or change passcode", "items": [
                    {"type": "form", "sections": [
                        {"fields": [
                            {"label": "Current passcode", "placeholder": "••••••",
                             "help": "Leave blank if no passcode is set."},
                            {"label": "New passcode", "required": True, "placeholder": "At least 6 characters"},
                            {"label": "Confirm new passcode", "required": True, "placeholder": "Repeat passcode"},
                        ]},
                    ], "buttons": [("Update passcode", "primary"), ("Cancel", "outline")]},
                ]},
            ], "right": [
                {"type": "card", "title": "Current state", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Protection", "Enabled"),
                        ("Session state", "Unlocked"),
                        ("Last changed", "02 Nov 2026"),
                    ]}]},
                    {"type": "html", "html": """
<div class="row" style="margin-top:12px">
  <button class="wf-btn sm">Lock now</button>
  <button class="wf-btn sm danger">Remove protection</button></div>
<div class="small muted" style="margin-top:10px">
  Removing protection deletes the stored hash. This cannot be undone.</div>"""},
                ]},
                {"type": "card", "title": "Forgotten your passcode?", "items": [
                    {"type": "html", "html": """
<div class="small muted">Only an OSAS administrator can reset a forgotten passcode.
Visit the OSAS office with your student ID. The reset is recorded in the audit log.</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-05", title="Browse scholarships", role="student", folder="student",
        nav_active="student.scholarships.index", route="student.scholarships.index", module="M06, M16",
        reqs="FR-SCH-06, FR-SCH-07, FR-SEARCH-02",
        purpose="List every scholarship currently open and inside its application window, "
                "with filters and a stable sort order.",
        annotations=[
            "Only scholarships with status open and a current date inside the window appear.",
            "Filters: search term, scholarship type, provider, program, eligibility, sort order.",
            "Sort options are deadline, name, newest and match score, matching the search service.",
            "Each card shows deadline, award summary, provider and a match or eligibility hint.",
            "Filter state is reflected in the URL so results can be shared or bookmarked.",
            "Empty state explains that no scholarship matches and offers a filter reset.",
            "Cards are keyboard-focusable and the whole card is one link target.",
        ],
        responsive=[
            ("Desktop 1440", "Three cards per row with a full-width filter bar above."),
            ("Tablet 1024", "Two cards per row; filters wrap onto two lines."),
            ("Mobile 390", "One card per row; filters collapse to a disclosure panel."),
        ],
        blocks=[
            {"type": "header", "title": "Browse scholarships",
             "subtitle": "Open scholarships you can apply for this term.",
             "actions": [("Recommended for me", "ghost")]},
            {"type": "filters",
             "fields": [("Search", "search"), ("Type", "select", ["All types", "Merit", "Grant", "Assistance"]),
                        ("Provider", "select", ["All providers", "DOST", "CHED", "ISU", "Private"]),
                        ("Eligibility", "select", ["All", "Eligible only", "Not yet eligible"]),
                        ("Sort by", "select", ["Deadline (soonest)", "Name", "Newest", "Match score"])],
             "buttons": [("Apply filters", "primary"), ("Reset", "outline")]},
            {"type": "cards", "cols": 3, "items": [
                {"title": "DOST-SEI Undergraduate Scholarship", "tag": "Open", "tagkind": "solid",
                 "meta": "DOST · Deadline 30 Nov 2026 · 12 days left",
                 "body": "Full tuition, monthly stipend and book allowance for high-performing "
                         "students in priority science and technology programs.",
                 "footer": [("View details", "primary"), ("Check eligibility", "ghost")]},
                {"title": "CHED Merit Scholarship", "tag": "Open", "tagkind": "solid",
                 "meta": "CHED · Deadline 15 Dec 2026 · 27 days left",
                 "body": "Tuition subsidy for students with a GWA of 1.75 or better and a "
                         "household income below the threshold.",
                 "footer": [("View details", "primary"), ("Check eligibility", "ghost")]},
                {"title": "ISU Presidential Grant", "tag": "Open", "tagkind": "solid",
                 "meta": "ISU · Deadline 30 Nov 2026 · 12 days left",
                 "body": "Book and transportation allowance for students from low-income "
                         "households enrolled in any undergraduate program.",
                 "footer": [("View details", "primary"), ("Check eligibility", "ghost")]},
                {"title": "OSAS Emergency Assistance", "tag": "Open", "tagkind": "solid",
                 "meta": "OSAS · Deadline 05 Dec 2026 · 17 days left",
                 "body": "One-time financial assistance for students affected by calamity or "
                         "sudden loss of household income.",
                 "footer": [("View details", "primary")]},
            ]},
            {"type": "pager", "current": 1, "total": 1, "count": "4 of 4 scholarships"},
        ],
    ),

    dict(
        id="WF-S-06", title="Scholarship detail", role="student", folder="student",
        nav_active="student.scholarships.index", route="student.scholarships.show", module="M06, M09",
        reqs="FR-SCH-09, FR-APP-01, FR-APP-03, FR-ELIG-04",
        purpose="Present the full scholarship record, its document requirements, the student's "
                "eligibility summary, and the single action appropriate to their state.",
        annotations=[
            "A scholarship that is not open returns 404 — there is no draft preview for students.",
            "Eligibility summary is a compact three-way verdict with a link to the full explanation.",
            "Requirements table distinguishes required from optional and shows upload readiness.",
            "The primary action changes with state: Apply, Continue draft, Submitted, Approved, Rejected.",
            "Only one application per student per scholarship is ever possible (BR-09).",
            "Award amount and provider are stated in full; no figure is illustrative on screen.",
        ],
        responsive=[
            ("Desktop 1440", "Two columns: narrative and requirements left, sticky action panel right."),
            ("Tablet 1024", "Action panel moves above the narrative as a summary strip."),
            ("Mobile 390", "Single column; requirements table scrolls horizontally."),
        ],
        blocks=[
            {"type": "header", "title": "DOST-SEI Undergraduate Scholarship",
             "subtitle": "Department of Science and Technology · Applications close 30 Nov 2026",
             "actions": [("Check eligibility", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "About this scholarship", "items": [
                    {"type": "html", "html": """
<p class="small" style="margin:0 0 12px">
  The DOST-SEI Undergraduate Scholarship supports students in priority science and technology
  programs who demonstrate strong academic performance and financial need. The award covers
  full tuition, a monthly living allowance and an annual book allowance for the duration of
  the program, subject to yearly renewal.</p>
<div class="grid c3">
  <div class="stat"><div class="k">Award</div><div class="v" style="font-size:17px">Full tuition</div>
    <div class="m">+ PHP 7,000 monthly stipend</div></div>
  <div class="stat"><div class="k">Minimum GWA</div><div class="v" style="font-size:17px">2.00</div>
    <div class="m">1.00 is highest</div></div>
  <div class="stat"><div class="k">Income ceiling</div><div class="v" style="font-size:17px">PHP 400,000</div>
    <div class="m">Annual household</div></div>
</div>"""},
                ]},
                {"type": "card", "title": "Document requirements", "items": [
                    {"type": "table", "columns": ["Requirement", "Needed", "Your upload", ""],
                     "rows": [
                         ["Certificate of Enrollment", "Required", "Uploaded · pending", "Remove"],
                         ["Certificate of Grades", "Required", "Uploaded · verified", "Remove"],
                         ["Certificate of Indigency", "Required", "Not uploaded", "Upload"],
                         ["Barangay Clearance", "Optional", "Not uploaded", "Upload"],
                     ], "status_col": 2},
                ]},
            ], "right": [
                {"type": "card", "title": "Your status", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Eligibility", "Eligible"),
                        ("Application", "Draft in progress"),
                        ("Documents ready", "2 of 3 required"),
                    ]}]},
                    {"type": "notice", "kind": "warn", "text":
                     "One required document is still missing. You can submit only after uploading it."},
                    {"type": "html", "html": """
<div style="display:grid;gap:8px;margin-top:6px">
  <button class="wf-btn primary" style="justify-content:center">Continue my draft</button>
  <button class="wf-btn" style="justify-content:center">View eligibility in detail</button>
</div>
<div class="small muted" style="margin-top:10px">
  Starting a second application for the same scholarship is not permitted, even after a
  rejection.</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-07", title="Eligibility preview", role="student", folder="student",
        nav_active="student.scholarships.index", route="student.scholarships.eligibility", module="M07",
        reqs="FR-ELIG-01, FR-ELIG-02, FR-ELIG-03, FR-ELIG-04",
        purpose="Explain, criterion by criterion, why the student is or is not eligible for a "
                "particular scholarship, without creating an application.",
        annotations=[
            "One row per criterion: GWA, family income, program, year level, enrollment status.",
            "Each row shows the student's value, the required value, the outcome and a reason sentence.",
            "Outcomes are met, not met, or not applicable; an unconfigured criterion is never a failure.",
            "The overall verdict is derived from the criteria, not computed separately.",
            "The page is read-only and creates no record; it can be revisited freely.",
            "A failed mandatory criterion states exactly what would need to change.",
        ],
        responsive=[
            ("Desktop 1440", "Verdict banner on top, criteria table full width, notes at the side."),
            ("Tablet 1024", "Criteria table keeps four columns; notes stack below."),
            ("Mobile 390", "Table becomes a stack of criterion cards, one per rule."),
        ],
        blocks=[
            {"type": "header", "title": "Eligibility preview",
             "subtitle": "DOST-SEI Undergraduate Scholarship · evaluated against your current profile",
             "actions": [("Back to scholarship", "ghost")]},
            {"type": "notice", "kind": "ok", "title": "You are eligible",
             "text": "Every configured criterion is met. You may start an application."},
            {"type": "card", "title": "Criterion by criterion", "items": [
                {"type": "table", "columns": ["Criterion", "Your value", "Required", "Outcome", "Reason"],
                 "rows": [
                     ["General weighted average", "1.42", "≤ 2.00", "met",
                      "Your GWA is better than the required maximum."],
                     ["Annual family income", "PHP 148,000", "≤ PHP 400,000", "met",
                      "Household income is below the ceiling."],
                     ["Program", "BS Information Technology", "Priority S&T programs", "met",
                      "Your program is on the eligible list."],
                     ["Year level", "3rd year", "2nd to 4th year", "met",
                      "Continuing students in this range qualify."],
                     ["Enrollment status", "Enrolled", "Enrolled", "met",
                      "Verified for the current term."],
                 ], "status_col": 3},
            ]},
            {"type": "notice", "kind": "info", "title": "How to read 'not applicable'",
             "text": "A criterion the scholarship does not configure is reported as not applicable "
                     "and excluded from the decision. It is not treated as a failure."},
            {"type": "split", "left": [
                {"type": "card", "title": "What this means", "items": [
                    {"type": "html", "html": """
<p class="small" style="margin:0">Eligibility is a screening result, not an award decision.
Meeting every criterion means your application will be reviewed; it does not guarantee
approval. The review also considers the documents you upload.</p>"""},
                ]},
            ], "right": [
                {"type": "html", "html": """<div style="display:grid;gap:8px">
<button class="wf-btn primary" style="justify-content:center">Start application</button>
<button class="wf-btn" style="justify-content:center">Compare with other scholarships</button>
</div>"""},
            ]},
        ],
    ),

    dict(
        id="WF-S-08", title="Recommended scholarships", role="student", folder="student",
        nav_active="student.scholarships.recommendations",
        route="student.scholarships.recommendations", module="M08",
        reqs="FR-MATCH-04, FR-MATCH-05, FR-MATCH-01",
        purpose="Rank scholarships for this student by weighted match score and separate "
                "eligible recommendations from potential matches.",
        annotations=[
            "Three groups: eligible recommendations, potential matches, and not eligible.",
            "Each card states the match percentage and the single biggest reason for the rank.",
            "Ranking is score descending, then deadline, then title, then identifier — stable across reloads.",
            "Not-eligible items show the failing mandatory criteria instead of a low score.",
            "Weights are administrator-configured; the student cannot change them.",
            "The full match list (WF-S-09) exposes every factor for students who want detail.",
        ],
        responsive=[
            ("Desktop 1440", "Grouped sections, three cards per row, ranked left to right."),
            ("Tablet 1024", "Two cards per row; groups remain separate sections."),
            ("Mobile 390", "One card per row with the match badge inline in the title row."),
        ],
        blocks=[
            {"type": "header", "title": "Recommended for you",
             "subtitle": "Ranked by academic fit, financial fit, program, year level and document readiness.",
             "actions": [("See full match list", "ghost")]},
            {"type": "stats", "cols": 3, "items": [
                ("Eligible recommendations", "3", "You meet every criterion"),
                ("Potential matches", "2", "One criterion not yet met"),
                ("Not eligible", "4", "Screening reasons shown below"),
            ]},
            {"type": "html", "html": '<div class="h3">Eligible recommendations</div>'},
            {"type": "cards", "cols": 3, "items": [
                {"title": "DOST-SEI Undergraduate", "tag": "92%", "tagkind": "solid",
                 "meta": "Strongest academic and financial fit",
                 "body": "All criteria met; two of three documents already uploaded.",
                 "footer": [("Open", "primary"), ("Eligibility", "ghost")]},
                {"title": "CHED Merit Scholarship", "tag": "84%", "tagkind": "solid",
                 "meta": "Strong academic fit",
                 "body": "All criteria met; document readiness lowers the score.",
                 "footer": [("Open", "primary"), ("Eligibility", "ghost")]},
                {"title": "ISU Presidential Grant", "tag": "76%", "tagkind": "solid",
                 "meta": "Financial fit, year level met",
                 "body": "All criteria met; no documents uploaded yet.",
                 "footer": [("Open", "primary"), ("Eligibility", "ghost")]},
            ]},
            {"type": "html", "html": '<div class="h3">Potential matches</div>'},
            {"type": "cards", "cols": 2, "items": [
                {"title": "OSAS Emergency Assistance", "tag": "Potential", "tagkind": "dash",
                 "meta": "Income criterion not met",
                 "body": "Your reported household income exceeds the configured ceiling by PHP 12,000.",
                 "footer": [("Open", "ghost")]},
                {"title": "Private Foundation Grant", "tag": "Potential", "tagkind": "dash",
                 "meta": "Program criterion not met",
                 "body": "This grant is limited to education and engineering programs.",
                 "footer": [("Open", "ghost")]},
            ]},
        ],
    ),

    dict(
        id="WF-S-09", title="Full match list", role="student", folder="student",
        nav_active="student.scholarships.recommendations",
        route="student.scholarships.matches", module="M08",
        reqs="FR-MATCH-01, FR-MATCH-05, FR-MATCH-06",
        purpose="Expose every scoring factor behind each recommendation so the ranking is "
                "inspectable rather than opaque.",
        annotations=[
            "Columns mirror the five factors: academic, financial, program, year level, documents.",
            "Each factor cell shows met or not met; document readiness shows a fraction.",
            "Total score is the weight-renormalised sum, displayed as a percentage.",
            "Factors that do not apply are shown as a dash and excluded from the denominator.",
            "Sorting by any column is permitted; the default order is score descending.",
            "Weights in use are stated in the caption so the percentages are interpretable.",
        ],
        responsive=[
            ("Desktop 1440", "Wide table with all factor columns visible."),
            ("Tablet 1024", "Table scrolls horizontally; score column is pinned first."),
            ("Mobile 390", "Table becomes a per-scholarship factor card list."),
        ],
        blocks=[
            {"type": "header", "title": "Full match list",
             "subtitle": "Active weights: academic 35%, financial 25%, program 20%, "
                         "year level 10%, document readiness 10%.",
             "actions": [("Back to recommendations", "ghost")]},
            {"type": "filters",
             "fields": [("Search", "search"), ("Category", "select", ["All", "Eligible", "Potential", "Not eligible"]),
                        ("Sort by", "select", ["Score (highest)", "Deadline", "Scholarship name"])],
             "buttons": [("Apply", "primary")]},
            {"type": "table",
             "columns": ["Scholarship", "Academic", "Financial", "Program", "Year", "Documents", "Score", ""],
             "rows": [
                 ["DOST-SEI Undergraduate", "Met", "Met", "Met", "Met", "2 / 3", "92.00%", ""],
                 ["CHED Merit Scholarship", "Met", "Met", "Met", "Met", "1 / 3", "84.00%", ""],
                 ["ISU Presidential Grant", "Met", "Met", "Met", "Met", "0 / 2", "76.00%", ""],
                 ["Private Foundation Grant", "Met", "Met", "—", "Met", "0 / 2", "68.00%", ""],
                 ["OSAS Emergency Assistance", "—", "Not met", "—", "—", "0 / 1", "—", ""],
             ],
             "actions": ["Open"]},
            {"type": "pager", "current": 1, "total": 2, "count": "5 of 9 scholarships"},
            {"type": "notice", "kind": "info", "text":
             "A dash means the scholarship does not configure that criterion, so the factor is "
             "excluded from both the numerator and the denominator of the score."},
        ],
    ),

    dict(
        id="WF-S-10", title="My applications", role="student", folder="student",
        nav_active="student.applications.index", route="student.applications.index", module="M09",
        reqs="FR-APP-06, FR-APP-09, NFR-PERF-03",
        purpose="List every application the student has ever submitted or drafted, with the "
                "current status and the next action.",
        annotations=[
            "Application number is shown in full and is the stable reference for any enquiry.",
            "Status badges use the same vocabulary as the review queue and the timeline.",
            "Draft rows expose 'Continue draft'; submitted rows expose 'View' only.",
            "Rejected rows remain visible permanently because reapplication is blocked.",
            "Pagination is server-side; the table never loads the full set.",
            "Empty state links to the scholarship catalogue.",
        ],
        responsive=[
            ("Desktop 1440", "Full table with six columns and a per-row action."),
            ("Tablet 1024", "Submitted date column collapses into a secondary line under the scholarship."),
            ("Mobile 390", "Each application renders as a stacked card with status and action."),
        ],
        blocks=[
            {"type": "header", "title": "My applications",
             "subtitle": "Every application you have started, including drafts.",
             "actions": [("Browse scholarships", "primary")]},
            {"type": "filters",
             "fields": [("Search", "search"), ("Status", "select", ["All statuses", "draft", "submitted", "under review", "document verification", "approved", "rejected"]),
                        ("Scholarship", "select", ["All scholarships"])],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Application no.", "Scholarship", "Started", "Submitted", "Status", ""],
             "rows": [
                 ["SCH-2026-000184", "DOST-SEI Undergraduate", "10 Nov 2026", "12 Nov 2026", "under_review", ""],
                 ["SCH-2026-000121", "ISU Presidential Grant", "28 Sep 2026", "03 Oct 2026", "rejected", ""],
                 ["SCH-2026-000095", "CHED Merit Scholarship", "14 Aug 2026", "—", "draft", ""],
             ],
             "status_col": 4, "actions": ["View", "Continue"]},
            {"type": "pager", "current": 1, "total": 1, "count": "3 of 3 applications"},
        ],
    ),

    dict(
        id="WF-S-11", title="Application detail and documents", role="student", folder="student",
        nav_active="student.applications.index", route="student.applications.show", module="M09, M10",
        reqs="FR-APP-04, FR-APP-05, FR-DOC-01, FR-DOC-02, FR-APP-09",
        purpose="Show one application in full: its workflow position, its documents, its "
                "history, and the one action the student may take next.",
        annotations=[
            "Progress stepper reflects the state machine, not a generic four-step bar.",
            "Upload controls are present only while the application is in draft.",
            "Each uploaded file shows verification state and any rejection remarks verbatim.",
            "Submit is refused while a required document is missing, with the reason listed.",
            "Status history is append-only and shows actor, time and remarks.",
            "Downloads stream through an authorised route; no direct file path is exposed.",
        ],
        responsive=[
            ("Desktop 1440", "Stepper full width, then 2:1 split of documents and sidebar."),
            ("Tablet 1024", "Stepper wraps to two lines; sidebar moves below documents."),
            ("Mobile 390", "Stepper becomes a vertical list; document rows become cards."),
        ],
        blocks=[
            {"type": "header", "title": "SCH-2026-000184",
             "subtitle": "DOST-SEI Undergraduate Scholarship · submitted 12 Nov 2026",
             "actions": [("Withdraw", "ghost")]},
            {"type": "steps", "items": [
                ("Draft", "done"), ("Submitted", "done"), ("Under review", "now"),
                ("Document verification", ""), ("Decision", "")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Documents", "items": [
                    {"type": "table",
                     "columns": ["Requirement", "File", "Uploaded", "State", ""],
                     "rows": [
                         ["Certificate of Enrollment", "coe_santos.pdf", "10 Nov 2026", "pending", ""],
                         ["Certificate of Grades", "cog_santos.pdf", "10 Nov 2026", "verified", ""],
                         ["Certificate of Indigency", "—", "—", "not uploaded", ""],
                     ],
                     "status_col": 3, "actions": ["View", "Remove"]},
                    {"type": "html", "html": """
<div class="card" style="margin-top:12px;border-style:dashed">
  <div class="card-b">
    <b style="font-size:13px">Upload a document</b>
    <div class="fgrid" style="margin-top:10px">
      <div class="field"><label>Requirement</label>
        <div class="ctl"><span>Certificate of Indigency</span><span class="car">&#9660;</span></div></div>
      <div class="field"><label>File</label>
        <div class="ctl" style="border-style:dashed"><span class="ph">Choose file</span>
          <span>&#8682;</span></div>
        <div class="fhelp">PDF, JPG, JPEG or PNG. Maximum 5 MB.</div></div>
    </div>
    <div class="row" style="margin-top:10px">
      <button class="wf-btn sm primary">Upload</button>
      <span class="small muted">Uploads are blocked once the application is submitted.</span></div>
  </div>
</div>"""},
                ]},
                {"type": "card", "title": "Status history", "items": [
                    {"type": "timeline", "items": [
                        ("12 Nov 2026 09:14", "Submitted", "Submitted by Maria L. Santos."),
                        ("10 Nov 2026 16:02", "Draft created", "Application started; number assigned."),
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Application summary", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Scholarship", "DOST-SEI Undergraduate"),
                        ("Provider", "Department of Science and Technology"),
                        ("Submitted", "12 Nov 2026, 09:14"),
                        ("Current status", "Under review"),
                        ("Documents", "1 verified, 1 pending, 1 missing"),
                    ]}]},
                    {"type": "notice", "kind": "warn", "text":
                     "A required document is still missing. Review cannot advance to approval "
                     "until it is uploaded and verified."},
                ]},
                {"type": "card", "title": "Need help?", "items": [
                    {"type": "html", "html": """<div class="small muted">Quote the application
number in any enquiry at the OSAS office. Status changes are also sent to your
notification centre and email address.</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-12", title="Guidance counselors", role="student", folder="student",
        nav_active="student.guidance.appointments", route="student.guidance.counselors", module="M11",
        reqs="FR-GUID-05, FR-GUID-06",
        purpose="List counselors who have published availability so the student can choose who "
                "to book with.",
        annotations=[
            "Only counselors with at least one active availability block are listed.",
            "Each card shows the counselor's name, the appointment types they offer, and next free day.",
            "Choosing a counselor leads to slot selection (WF-S-13) with the counselor pre-selected.",
            "A counselor with no published availability is omitted rather than shown as unavailable.",
            "No counselor notes, caseload or internal remarks appear on this screen.",
        ],
        responsive=[
            ("Desktop 1440", "Three cards per row."),
            ("Tablet 1024", "Two cards per row."),
            ("Mobile 390", "One card per row; the whole card is one link target."),
        ],
        blocks=[
            {"type": "header", "title": "Choose a counselor",
             "subtitle": "Book a guidance appointment with a counselor who has published availability.",
             "actions": [("My appointments", "ghost")]},
            {"type": "notice", "kind": "info", "text":
             "Slots are generated from each counselor's published weekly availability, office "
             "hours and the minimum booking notice."},
            {"type": "cards", "cols": 3, "items": [
                {"title": "Prof. R. Dela Cruz", "tag": "Available", "tagkind": "solid",
                 "meta": "Guidance Office · 3 appointment types · next free 18 Nov",
                 "body": "Academic counseling, course selection, and scholarship guidance.",
                 "footer": [("See available slots", "primary")]},
                {"title": "Prof. J. Ramirez", "tag": "Available", "tagkind": "solid",
                 "meta": "Guidance Office · 2 appointment types · next free 19 Nov",
                 "body": "Personal counseling and student welfare concerns.",
                 "footer": [("See available slots", "primary")]},
                {"title": "Ms. L. Aquino", "tag": "Available", "tagkind": "solid",
                 "meta": "Guidance Office · 2 appointment types · next free 18 Nov",
                 "body": "Career counseling and internship preparation.",
                 "footer": [("See available slots", "primary")]},
            ]},
        ],
    ),

    dict(
        id="WF-S-13", title="Appointment slots and booking", role="student", folder="student",
        nav_active="student.guidance.appointments", route="student.guidance.slots", module="M11",
        reqs="FR-GUID-06, FR-GUID-07, FR-GUID-08, BR-15, BR-16, BR-17",
        purpose="Offer only slots that satisfy availability, office hours, booking notice, the "
                "booking horizon, and both counselor and student conflict rules.",
        annotations=[
            "Date picker is bounded before today and after the maximum future booking day.",
            "Appointment type changes the slot duration and therefore the slot grid.",
            "Conflicting slots are shown struck through rather than hidden, so the student sees why.",
            "Submitting re-validates every rule inside a transaction; the UI is not the guard.",
            "The reason field is required and is visible to the counselor.",
            "A failed booking returns to this page with the specific conflict stated.",
        ],
        responsive=[
            ("Desktop 1440", "Slot grid four to six per row; booking form in a right-hand panel."),
            ("Tablet 1024", "Grid three per row; form moves below the grid."),
            ("Mobile 390", "Grid two per row; form stacks with full-width controls."),
        ],
        blocks=[
            {"type": "header", "title": "Book with Prof. R. Dela Cruz",
             "subtitle": "Wednesday, 18 November 2026 · Guidance Office, Room 204",
             "actions": [("Change counselor", "ghost")]},
            {"type": "filters",
             "fields": [("Appointment type", "select", ["Academic counseling (30 min)", "Course selection (30 min)", "Scholarship guidance (45 min)"]),
                        ("Date", "date")],
             "buttons": [("Show slots", "primary")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Available slots", "items": [
                    {"type": "html", "html": """
<div class="small muted" style="margin-bottom:10px">Morning</div>
<div class="slots">
  <div class="slot taken">08:00<small>booked</small></div>
  <div class="slot free">08:30<small>30 min</small></div>
  <div class="slot free">09:00<small>30 min</small></div>
  <div class="slot taken">09:30<small>booked</small></div>
  <div class="slot sel">10:00<small>selected</small></div>
  <div class="slot free">10:30<small>30 min</small></div>
</div>
<div class="small muted" style="margin:14px 0 10px">Afternoon</div>
<div class="slots">
  <div class="slot free">13:00<small>30 min</small></div>
  <div class="slot free">13:30<small>30 min</small></div>
  <div class="slot taken">14:00<small>booked</small></div>
  <div class="slot free">14:30<small>30 min</small></div>
  <div class="slot free">15:00<small>30 min</small></div>
  <div class="slot free">15:30<small>30 min</small></div>
</div>"""},
                    {"type": "notice", "kind": "info", "text":
                     "Struck-through slots overlap an existing appointment for this counselor or "
                     "for you. They are shown so the reason is visible, but they cannot be chosen."},
                ]},
            ], "right": [
                {"type": "card", "title": "Confirm booking", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Counselor", "Prof. R. Dela Cruz"),
                        ("Date", "18 Nov 2026"),
                        ("Time", "10:00 – 10:30"),
                        ("Type", "Academic counseling"),
                        ("Duration", "30 minutes"),
                    ]}]},
                    {"type": "html", "html": """
<div class="field" style="margin-top:10px">
  <label>Reason for the appointment <span class="req">*</span></label>
  <div class="ctl area"><span class="ph">Briefly describe what you would like to discuss&hellip;</span></div>
  <div class="fhelp">Visible to the counselor. Do not include sensitive medical details.</div>
</div>
<button class="wf-btn primary" style="width:100%;justify-content:center;margin-top:12px">
  Request this slot</button>
<div class="small muted" style="margin-top:9px">
  The appointment stays 'requested' until the counselor confirms it.</div>"""},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-14", title="My appointments", role="student", folder="student",
        nav_active="student.guidance.appointments", route="student.guidance.appointments", module="M11",
        reqs="FR-GUID-07, FR-GUID-10, FR-GUID-12",
        purpose="List the student's guidance appointments across all statuses with the action "
                "available for each.",
        annotations=[
            "Tabs separate upcoming, past and cancelled appointments without changing the URL.",
            "Status vocabulary matches the appointment state machine exactly.",
            "Cancel is offered only for requested or confirmed appointments outside the notice window.",
            "A reminder indicator shows when the 24-hour reminder has been dispatched.",
            "Historical appointments remain readable but expose no actions.",
        ],
        responsive=[
            ("Desktop 1440", "Table with date, time, counselor, type, status and action."),
            ("Tablet 1024", "Type column folds into the counselor cell."),
            ("Mobile 390", "Each appointment becomes a card with the status badge in the header."),
        ],
        blocks=[
            {"type": "header", "title": "My appointments",
             "subtitle": "Guidance sessions you have requested or attended.",
             "actions": [("Book an appointment", "primary")]},
            {"type": "tabs", "items": [("Upcoming (1)", True), ("Past (4)", False), ("Cancelled (1)", False)]},
            {"type": "table",
             "columns": ["Date", "Time", "Counselor", "Type", "Status", ""],
             "rows": [
                 ["18 Nov 2026", "10:00 – 10:30", "Prof. R. Dela Cruz", "Academic counseling", "confirmed", ""],
                 ["20 Nov 2026", "13:30 – 14:00", "Ms. L. Aquino", "Career counseling", "requested", ""],
             ],
             "status_col": 4, "actions": ["View", "Cancel"]},
            {"type": "notice", "kind": "info", "text":
             "Cancellation is refused once the appointment falls inside the configured "
             "cancellation notice window. Contact the Guidance Office instead."},
        ],
    ),

    dict(
        id="WF-S-15", title="Appointment detail", role="student", folder="student",
        nav_active="student.guidance.appointments", route="student.guidance.appointments.show", module="M11",
        reqs="FR-GUID-10, FR-GUID-12, BR-19",
        purpose="Show one appointment in full and offer cancellation only when the notice window "
                "still allows it.",
        annotations=[
            "Status stepper shows requested, confirmed, then completed or cancelled.",
            "Cancellation notice window is stated in plain language with the exact cut-off time.",
            "Rejection reason from the counselor is shown verbatim when present.",
            "Student-visible notes only; counselor notes are never rendered to the student.",
            "Reminder state is shown so the student knows whether an email was already sent.",
        ],
        responsive=[
            ("Desktop 1440", "Detail panel left, status and actions right."),
            ("Tablet 1024", "Action panel moves below the detail panel."),
            ("Mobile 390", "Single column with the status banner first."),
        ],
        blocks=[
            {"type": "header", "title": "Appointment · 18 Nov 2026",
             "subtitle": "Academic counseling with Prof. R. Dela Cruz",
             "actions": [("Cancel appointment", "ghost")]},
            {"type": "steps", "items": [("Requested", "done"), ("Confirmed", "now"),
                                        ("Completed", "")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Appointment details", "items": [
                    {"type": "detail", "sections": [
                        {"heading": "Schedule", "pairs": [
                            ("Date", "18 November 2026 (Wednesday)"),
                            ("Time", "10:00 – 10:30"),
                            ("Duration", "30 minutes"),
                            ("Location", "Guidance Office, Room 204"),
                        ]},
                        {"heading": "Session", "pairs": [
                            ("Type", "Academic counseling"),
                            ("Counselor", "Prof. R. Dela Cruz"),
                            ("Your reason", "I would like to discuss my scholarship requirements "
                                            "and whether my current load affects eligibility."),
                            ("Status", "Confirmed"),
                        ]},
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Cancellation window", "items": [
                    {"type": "notice", "kind": "info", "text":
                     "You may cancel until 24 hours before the start time, that is until "
                     "17 Nov 2026, 10:00. After that the Guidance Office must be contacted."},
                    {"type": "html", "html": """<button class="wf-btn danger"
                      style="width:100%;justify-content:center">Cancel appointment</button>"""},
                ]},
                {"type": "card", "title": "Reminder", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Email reminder", "Scheduled"),
                        ("Sent", "—"),
                        ("Recipient", "m.santos@isu.edu.ph"),
                    ]}]},
                ]},
            ]},
            {"type": "card", "title": "History", "items": [
                {"type": "timeline", "items": [
                    ("14 Nov 2026 09:02", "Confirmed", "Confirmed by Prof. R. Dela Cruz."),
                    ("13 Nov 2026 15:40", "Requested", "Requested by Maria L. Santos."),
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-16", title="Service catalogue", role="student", folder="student",
        nav_active="student.services.catalog", route="student.services.catalog", module="M12",
        reqs="FR-SVC-01, FR-SVC-03",
        purpose="Present the active OSAS service types with their instructions and required "
                "attachments, and start a request.",
        annotations=[
            "Only service types flagged active are listed, in administrator-defined display order.",
            "Each card states what the student must prepare before starting the request.",
            "Required attachments are counted so the student knows the effort involved.",
            "Starting a request opens the form with the service type pre-selected.",
            "Instructions are shown in full; there is no hidden policy text.",
        ],
        responsive=[
            ("Desktop 1440", "Three cards per row."),
            ("Tablet 1024", "Two cards per row."),
            ("Mobile 390", "One card per row with the action pinned to the bottom."),
        ],
        blocks=[
            {"type": "header", "title": "OSAS services",
             "subtitle": "Request certificates, clearances and assistance from the office.",
             "actions": [("My requests", "ghost")]},
            {"type": "cards", "cols": 3, "items": [
                {"title": "Good Moral Certificate", "tag": "1 attachment", "tagkind": "dash",
                 "meta": "Issued within 3 working days",
                 "body": "Certifies that you have no recorded disciplinary case. Bring a valid "
                         "student ID when claiming.",
                 "footer": [("Request this service", "primary")]},
                {"title": "Certificate of Enrollment", "tag": "No attachment",
                 "meta": "Issued within 1 working day",
                 "body": "Confirms your enrollment for the current term. Often required by "
                         "external scholarship providers.",
                 "footer": [("Request this service", "primary")]},
                {"title": "Financial Assistance", "tag": "2 attachments", "tagkind": "dash",
                 "meta": "Assessment within 5 working days",
                 "body": "One-time assistance for calamity-affected or low-income students, "
                         "subject to fund availability.",
                 "footer": [("Request this service", "primary")]},
                {"title": "Lost ID Replacement Slip", "tag": "1 attachment", "tagkind": "dash",
                 "meta": "Issued same day",
                 "body": "Temporary slip while a replacement student ID is processed.",
                 "footer": [("Request this service", "primary")]},
            ]},
        ],
    ),

    dict(
        id="WF-S-17", title="New service request", role="student", folder="student",
        nav_active="student.services.catalog", route="student.services.create", module="M12",
        reqs="FR-SVC-03, FR-SVC-04, BR-13, BR-24",
        purpose="Collect the service type, a description of at least ten characters, and the "
                "supporting documents required by that service.",
        annotations=[
            "Service type is pre-selected when arriving from the catalogue.",
            "Description requires at least ten characters; the counter shows the remaining minimum.",
            "Attachments are limited to PDF, JPG, JPEG and PNG at 5 MB each.",
            "Required attachments are listed per the chosen service type's requirements.",
            "Submitting generates a request number in the form SR-YYYY-NNNNNN.",
            "The request is created with status submitted and the student is returned to detail.",
        ],
        responsive=[
            ("Desktop 1440", "Single form card at 860 px with a two-column field grid."),
            ("Tablet 1024", "Two columns retained; attachment rows wrap."),
            ("Mobile 390", "Single column; file pickers become full width."),
        ],
        blocks=[
            {"type": "header", "title": "New service request",
             "subtitle": "Tell the office what you need and attach the supporting documents.",
             "actions": [("Back to catalogue", "ghost")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "items": [
                    {"type": "form", "sections": [
                        {"heading": "Request details", "fields": [
                            {"label": "Service type", "type": "select", "span": 2,
                             "value": "Good Moral Certificate"},
                            {"label": "Description", "type": "textarea", "span": 2, "required": True,
                             "placeholder": "State the purpose of the certificate and where it will be submitted.",
                             "help": "At least 10 characters. Be specific; this is the office's only brief."},
                        ]},
                        {"heading": "Supporting documents", "fields": [
                            {"label": "Valid student ID", "type": "file", "span": 2,
                             "required": True, "help": "Required for this service type."},
                            {"label": "Barangay certification", "type": "file", "span": 2,
                             "help": "Optional. Accepted formats: PDF, JPG, JPEG, PNG. Max 5 MB each."},
                        ]},
                    ], "buttons": [("Submit request", "primary"), ("Cancel", "outline")]},
                ]},
            ], "right": [
                {"type": "card", "title": "What happens next", "items": [
                    {"type": "html", "html": """<ol class="small muted"
                      style="margin:0;padding-left:18px;display:grid;gap:8px">
                      <li>You receive a request number immediately.</li>
                      <li>The office assigns a handler and starts processing.</li>
                      <li>You may be asked for additional information.</li>
                      <li>The request is completed or cancelled, and you are notified.</li>
                    </ol>"""},
                ]},
                {"type": "card", "title": "Processing time", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Typical", "3 working days"),
                        ("Office hours", "08:00 – 17:00, Mon–Fri"),
                    ]}]},
                ]},
            ]},
        ],
    ),

    dict(
        id="WF-S-18", title="My service requests", role="student", folder="student",
        nav_active="student.services.catalog", route="student.services.index", module="M12",
        reqs="FR-SVC-05, FR-SVC-08, NFR-USA-05",
        purpose="List every request the student has raised, with its status, handler and date.",
        annotations=[
            "Request number, service type, submission date, handler and status are always shown.",
            "Status labels use the service-request vocabulary, including requires_information.",
            "Only requests that can still change status expose a Cancel action.",
            "The handler column may be blank while a request is unassigned.",
            "Filters mirror those on the administrative queue for predictability.",
        ],
        responsive=[
            ("Desktop 1440", "Full table with five columns and row actions."),
            ("Tablet 1024", "Handler column folds into a secondary line."),
            ("Mobile 390", "Stacked cards with the status badge in the header."),
        ],
        blocks=[
            {"type": "header", "title": "My service requests",
             "subtitle": "Everything you have asked the office to process.",
             "actions": [("New request", "primary")]},
            {"type": "filters",
             "fields": [("Search", "search"),
                        ("Status", "select", ["All statuses", "submitted", "processing", "requires information", "completed", "cancelled"])],
             "buttons": [("Filter", "primary"), ("Reset", "outline")]},
            {"type": "table",
             "columns": ["Request no.", "Service", "Submitted", "Handler", "Status", ""],
             "rows": [
                 ["SR-2026-000042", "Good Moral Certificate", "14 Nov 2026", "A. Bautista", "requires_information", ""],
                 ["SR-2026-000031", "Certificate of Enrollment", "04 Nov 2026", "A. Bautista", "completed", ""],
                 ["SR-2026-000026", "Financial Assistance", "22 Oct 2026", "Unassigned", "cancelled", ""],
             ],
             "status_col": 4, "actions": ["View", "Cancel"]},
            {"type": "pager", "current": 1, "total": 1, "count": "3 of 3 requests"},
        ],
    ),

    dict(
        id="WF-S-19", title="Service request detail", role="student", folder="student",
        nav_active="student.services.catalog", route="student.services.show", module="M12",
        reqs="FR-SVC-05, FR-SVC-08, FR-SVC-09",
        purpose="Show one request, its documents, its status history, and any information the "
                "office still needs from the student.",
        annotations=[
            "An outstanding information request is surfaced as a prominent banner, not a hidden status.",
            "Office remarks and internal remarks are separated; only the former is shown here.",
            "Additional documents can be attached only while the request is non-terminal.",
            "Status history is append-only with actor and timestamp.",
            "Cancellation is offered only when the state machine still permits it.",
        ],
        responsive=[
            ("Desktop 1440", "Two-column layout: details and history left, actions right."),
            ("Tablet 1024", "Actions move under the details block."),
            ("Mobile 390", "Single column; the outstanding-action banner appears first."),
        ],
        blocks=[
            {"type": "header", "title": "SR-2026-000042",
             "subtitle": "Good Moral Certificate · submitted 14 Nov 2026",
             "actions": [("Cancel request", "ghost")]},
            {"type": "notice", "kind": "warn", "title": "The office needs more information",
             "text": "Please upload a clearer copy of your student ID. The current scan is not "
                     "legible. Your request will resume processing once uploaded."},
            {"type": "steps", "items": [("Submitted", "done"), ("Processing", "done"),
                                        ("Information required", "now"), ("Completed", "")]},
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "card", "title": "Request", "items": [
                    {"type": "detail", "sections": [{"pairs": [
                        ("Service type", "Good Moral Certificate"),
                        ("Description", "Needed for a scholarship application with a private foundation."),
                        ("Submitted", "14 Nov 2026, 08:41"),
                        ("Handler", "A. Bautista"),
                        ("Status", "Requires information"),
                    ]}]},
                ]},
                {"type": "card", "title": "Documents", "items": [
                    {"type": "table", "columns": ["Requirement", "File", "Uploaded", ""],
                     "rows": [
                         ["Valid student ID", "id_front.jpg", "14 Nov 2026", "Replace"],
                         ["Barangay certification", "—", "—", "Upload"],
                     ], "actions": ["View"]},
                ]},
                {"type": "card", "title": "History", "items": [
                    {"type": "timeline", "items": [
                        ("15 Nov 2026 10:12", "Additional information required",
                         "Requested by A. Bautista: student ID scan is illegible."),
                        ("14 Nov 2026 09:05", "Processing started",
                         "Assigned to A. Bautista."),
                        ("14 Nov 2026 08:41", "Submitted", "Submitted by Maria L. Santos."),
                    ]},
                ]},
            ], "right": [
                {"type": "card", "title": "Upload the requested document", "items": [
                    {"type": "html", "html": """
<div class="field" style="margin-bottom:10px">
  <label>File <span class="req">*</span></label>
  <div class="ctl" style="border-style:dashed"><span class="ph">Choose file</span>
    <span>&#8682;</span></div>
  <div class="fhelp">PDF, JPG, JPEG or PNG. Maximum 5 MB.</div>
</div>
<button class="wf-btn primary" style="width:100%;justify-content:center">Upload</button>"""},
                ]},
                {"type": "card", "title": "Office remarks", "items": [
                    {"type": "html", "html": """<div class="small muted">Remarks are written by the
assigned handler and are visible to you. Internal handling notes are never shown on this
screen.</div>"""},
                ]},
            ]},
        ],
    ),
]
