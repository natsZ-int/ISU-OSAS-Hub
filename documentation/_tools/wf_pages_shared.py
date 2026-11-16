"""Wireframe page specs: shared and guest screens (WF-G-01 … WF-G-06)."""

from wf_kit import esc  # noqa: F401  (kept for building raw html blocks)

PAGES = [
    dict(
        id="WF-G-01", title="Login", role="guest", folder="shared",
        route="login", module="M01",
        reqs="FR-AUTH-01, FR-AUTH-02, FR-AUTH-07, FR-AUTH-08",
        purpose="Authenticate a registered account with email and password, with a "
                "remember-me option and a single non-revealing failure message.",
        annotations=[
            "Single column form; labels sit above inputs and errors appear under the field.",
            "Failure message is generic — it never states whether the email or the password was wrong.",
            "Remember-me checkbox is optional and defaults to off.",
            "Login is throttled to 5 attempts per minute per client; the throttle message appears here.",
            "There is no 'forgot password' link because no self-service reset route exists (GAP-05).",
            "Primary action is the last control in the form; keyboard focus reaches it by tabbing.",
        ],
        responsive=[
            ("Desktop 1440", "Two columns: brand panel left, form card right, 45/55 split."),
            ("Tablet 1024", "Brand panel narrows; form card keeps a comfortable 420 px measure."),
            ("Mobile 390", "Single column; brand panel collapses to a compact banner above the form."),
        ],
        blocks=[
            {"type": "split", "ratio": "2:1", "left": [
                {"type": "html", "html": """
<div class="auth-hero" style="min-height:430px;display:flex;flex-direction:column;
     justify-content:center;padding:34px 30px;border:1px solid var(--line-2);
     border-radius:8px;background:var(--fill)">
  <div style="display:flex;gap:11px;align-items:center;margin-bottom:26px">
    <span style="width:46px;height:46px;border:1.5px solid var(--ink);border-radius:9px;
          display:grid;place-items:center;font:700 13px/1 var(--mono)">ISU</span>
    <div><div style="font-weight:700;font-size:15px">ISU OSAS Hub</div>
      <div style="font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;
           color:var(--muted)">Student Affairs &amp; Services</div></div>
  </div>
  <h2 style="margin:0 0 8px;font-size:24px;line-height:1.25">
    One portal for scholarships,<br>guidance and student services.</h2>
  <p style="color:var(--muted);font-size:13px;margin:0 0 22px;max-width:44ch">
    Isabela State University &mdash; Office of Student Affairs and Services.</p>
  <ul style="margin:0;padding:0;list-style:none;display:grid;gap:9px;font-size:12.5px;
      color:var(--ink-2)">
    <li>&#10003;&nbsp;&nbsp; Screen scholarship eligibility before applying</li>
    <li>&#10003;&nbsp;&nbsp; Book guidance appointments against published availability</li>
    <li>&#10003;&nbsp;&nbsp; Track every service request from submission to completion</li>
  </ul>
</div>"""},
            ], "right": [
                {"type": "html", "html": """
<div class="card">
  <div class="card-h"><h3>Sign in</h3></div>
  <div class="card-b">
    <div class="field" style="margin-bottom:13px">
      <label>Email address <span class="req">*</span></label>
      <div class="ctl"><span class="ph">name@isu.edu.ph</span></div>
    </div>
    <div class="field" style="margin-bottom:10px">
      <label>Password <span class="req">*</span></label>
      <div class="ctl"><span class="ph">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span>
        <span class="mono" style="font-size:10.5px;color:var(--muted)">Show</span></div>
      <div class="fhelp">Minimum 8 characters.</div>
    </div>
    <div class="row small" style="margin:12px 0 16px">
      <span style="width:14px;height:14px;border:1px solid var(--line);border-radius:3px;
            display:inline-block"></span>
      <span>Remember me on this device</span>
    </div>
    <button class="wf-btn primary" style="width:100%;justify-content:center">Log in</button>
    <div class="divider"></div>
    <div class="small" style="display:grid;gap:7px">
      <span>No account yet? <b>Register as a student</b></span>
      <span class="muted">Counselor and administrator accounts are issued by OSAS.</span>
    </div>
  </div>
</div>"""},
            ]},
        ],
    ),

    dict(
        id="WF-G-02", title="Student registration", role="guest", folder="shared",
        route="register", module="M01",
        reqs="FR-AUTH-04, FR-PROF-01, BR-04, BR-05",
        purpose="Create a student account and its linked student profile in one submission, "
                "then require email verification before any student screen opens.",
        annotations=[
            "Three sections: account credentials, academic details, and personal and financial details.",
            "Student number is validated as unique, maximum 20 characters, letters/digits/dash only.",
            "GWA accepts 1.00–5.00; family income accepts a non-negative amount with two decimals.",
            "Password requires a confirmation field and a minimum of eight characters.",
            "Privacy consent is mandatory; the submit button stays disabled until it is ticked.",
            "Errors are field-level and previously entered values are preserved on redisplay.",
            "On success the account is created but unusable until the OTP is verified (WF-G-03).",
        ],
        responsive=[
            ("Desktop 1440", "Two-column field grid inside a single centred form card (max 860 px)."),
            ("Tablet 1024", "Grid remains two columns; card fills the content width."),
            ("Mobile 390", "Every field is full width and stacked in document order."),
        ],
        blocks=[
            {"type": "header", "title": "Create a student account",
             "subtitle": "Use your official name and student number. Academic details cannot be "
                         "changed later without OSAS approval.",
             "actions": [("Already registered? Log in", "ghost")]},
            {"type": "html", "html": """
<div class="card" style="max-width:860px">
 <div class="card-b">
  <div class="form-sec">
    <div class="fh">1 &middot; Account credentials</div>
    <div class="fgrid">
      <div class="field span2"><label>Full name <span class="req">*</span></label>
        <div class="ctl"><span class="ph">As it appears on your enrolment record</span></div></div>
      <div class="field"><label>Email address <span class="req">*</span></label>
        <div class="ctl"><span class="ph">name@isu.edu.ph</span></div>
        <div class="ferr">! This email is already registered.</div></div>
      <div class="field"><label>Student number <span class="req">*</span></label>
        <div class="ctl"><span class="ph">2023-00123</span></div>
        <div class="fhelp">Letters, digits and dashes only.</div></div>
      <div class="field"><label>Password <span class="req">*</span></label>
        <div class="ctl"><span class="ph">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span></div></div>
      <div class="field"><label>Confirm password <span class="req">*</span></label>
        <div class="ctl"><span class="ph">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span></div></div>
    </div>
  </div>
  <div class="form-sec">
    <div class="fh">2 &middot; Academic details</div>
    <div class="fgrid">
      <div class="field"><label>College <span class="req">*</span></label>
        <div class="ctl"><span class="ph">Select college</span><span class="car">&#9660;</span></div></div>
      <div class="field"><label>Program <span class="req">*</span></label>
        <div class="ctl"><span class="ph">Select program</span><span class="car">&#9660;</span></div></div>
      <div class="field"><label>Year level <span class="req">*</span></label>
        <div class="ctl"><span class="ph">Select year</span><span class="car">&#9660;</span></div></div>
      <div class="field"><label>General weighted average <span class="req">*</span></label>
        <div class="ctl"><span class="ph">1.00 – 5.00</span></div>
        <div class="fhelp">Philippine scale; 1.00 is highest.</div></div>
    </div>
  </div>
  <div class="form-sec">
    <div class="fh">3 &middot; Contact and household</div>
    <div class="fgrid">
      <div class="field"><label>Contact number <span class="req">*</span></label>
        <div class="ctl"><span class="ph">09XX XXX XXXX</span></div></div>
      <div class="field"><label>Annual family income <span class="req">*</span></label>
        <div class="ctl"><span class="ph">0.00</span></div>
        <div class="fhelp">Used only for scholarship eligibility screening.</div></div>
      <div class="field span2"><label>Home address <span class="req">*</span></label>
        <div class="ctl area"><span class="ph">Street, barangay, municipality, province</span></div></div>
      <div class="field span2">
        <div class="row small"><span style="width:14px;height:14px;border:1px solid var(--line);
          border-radius:3px;display:inline-block"></span>
          <span>I consent to the collection and processing of these details for scholarship,
          guidance and student-service purposes.</span></div></div>
    </div>
  </div>
  <div class="facts"><button class="wf-btn primary">Create account</button>
    <button class="wf-btn">Cancel</button></div>
 </div>
</div>"""},
        ],
    ),

    dict(
        id="WF-G-03", title="Email verification (OTP)", role="guest", folder="shared",
        route="verification.notice", module="M01",
        reqs="FR-AUTH-05, FR-AUTH-06, NFR-SEC-07",
        purpose="Require a six-digit one-time passcode sent to the registered address before "
                "the student panel becomes reachable.",
        annotations=[
            "Six single-character inputs that advance focus automatically and accept a pasted code.",
            "The masked email address is shown so the user can confirm the destination.",
            "Resend is disabled during the cooldown and shows the remaining seconds.",
            "Consuming the code invalidates it; a wrong code increments the attempt counter.",
            "After too many attempts the code is invalidated and a new one must be requested.",
            "A 'wrong email?' escape hatch returns to registration but does not delete the account.",
        ],
        responsive=[
            ("Desktop 1440", "Centred card, 460 px wide, with the code inputs on one row."),
            ("Tablet 1024", "Same card, centred; inputs keep a 44 px touch target."),
            ("Mobile 390", "Card fills the width; inputs shrink but remain 38 px tall."),
        ],
        blocks=[
            {"type": "html", "html": """
<div style="max-width:470px;margin:26px auto 0">
 <div class="card">
  <div class="card-b" style="text-align:center;padding:26px 22px">
    <div style="width:52px;height:52px;border:1.5px solid var(--ink);border-radius:50%;
         display:grid;place-items:center;margin:0 auto 14px;font-size:20px">&#9993;</div>
    <h2 style="margin:0 0 6px;font-size:19px">Check your email</h2>
    <p class="small muted" style="margin:0 0 20px">
      We sent a six-digit passcode to <b class="mono" style="color:var(--ink)">m.santos@isu.edu.ph</b>.
      It expires in 10 minutes.</p>
    <div style="display:flex;gap:8px;justify-content:center;margin-bottom:8px">
      <div class="ctl" style="width:46px;height:52px;justify-content:center;
           font:700 18px/1 var(--mono)">4</div>
      <div class="ctl" style="width:46px;height:52px;justify-content:center;
           font:700 18px/1 var(--mono)">1</div>
      <div class="ctl" style="width:46px;height:52px;justify-content:center;
           font:700 18px/1 var(--mono)">9</div>
      <div class="ctl" style="width:46px;height:52px;justify-content:center;
           font:700 18px/1 var(--mono)">2</div>
      <div class="ctl" style="width:46px;height:52px;justify-content:center;
           font:700 18px/1 var(--mono)">&nbsp;</div>
      <div class="ctl" style="width:46px;height:52px;justify-content:center;
           font:700 18px/1 var(--mono)">&nbsp;</div>
    </div>
    <div class="ferr" style="margin-bottom:14px">! That code is incorrect. 4 attempts remaining.</div>
    <button class="wf-btn primary" style="width:100%;justify-content:center">Verify email</button>
    <div class="divider"></div>
    <div class="small muted" style="display:grid;gap:6px">
      <span>Didn't receive it? <b style="color:var(--ink)">Resend in 0:42</b></span>
      <span>Wrong address? <b style="color:var(--ink)">Start over</b></span>
    </div>
  </div>
 </div>
</div>"""},
        ],
    ),

    dict(
        id="WF-G-04", title="Access denied and not found", role="guest", folder="shared",
        route="— (errors/403, errors/404)", module="M02",
        reqs="FR-RBAC-02, NFR-SEC-03",
        purpose="Refuse an unauthorised request without disclosing whether the record exists, "
                "and explain the refusal in plain language.",
        annotations=[
            "403 is returned when an authenticated user requests another role's route.",
            "404 is returned for a missing record and for a scholarship that is not open.",
            "No record data is rendered on either page — no titles, identifiers or counts.",
            "Recovery actions are limited to safe destinations for the current role.",
            "The same template is used for both codes; only the heading and body text differ.",
        ],
        responsive=[
            ("Desktop 1440", "Centred block, max 560 px, vertically centred in the content area."),
            ("Tablet 1024", "Identical layout, slightly reduced type scale."),
            ("Mobile 390", "Full-width block with stacked actions."),
        ],
        blocks=[
            {"type": "html", "html": """
<div style="max-width:560px;margin:34px auto;text-align:center">
  <div class="mono" style="font-size:64px;font-weight:800;letter-spacing:-.04em;
       border:2px dashed var(--line);border-radius:12px;padding:16px 0;margin-bottom:20px">403</div>
  <h2 style="margin:0 0 8px;font-size:21px">You don't have access to this page</h2>
  <p class="small muted" style="margin:0 0 20px">
    Your account does not carry the role this page requires. If you believe this is a
    mistake, contact the OSAS office and quote your student number.</p>
  <div class="row" style="justify-content:center">
    <button class="wf-btn primary">Go to my dashboard</button>
    <button class="wf-btn">Back to previous page</button>
  </div>
  <div class="divider"></div>
  <div class="small muted" style="text-align:left;border:1px solid var(--line-2);
       border-radius:8px;padding:14px">
    <b style="color:var(--ink);display:block;margin-bottom:8px">404 variant — record not found</b>
    The same layout is used when a record does not exist or is not open to students:
    <div style="margin-top:10px;padding:12px;background:var(--fill);border-radius:6px">
      <b style="color:var(--ink)">404 &middot; Page not found</b><br>
      This scholarship is not open, or the address is incorrect.
    </div>
  </div>
</div>"""},
        ],
    ),

    dict(
        id="WF-G-05", title="Notification centre", role="student", folder="shared",
        nav_active="notifications.index", search=False,
        route="notifications.index", module="M13",
        reqs="FR-NOTIF-04, FR-NOTIF-05, FR-NOTIF-06",
        purpose="List the signed-in user's notifications with category, read state and a safe "
                "link to the related record.",
        annotations=[
            "Unread items are marked with a filled dot and bolder title; read items are plain.",
            "Category filter separates scholarship, guidance, service and system notifications.",
            "Mark-all-read is a single action next to the filter, not per row.",
            "Each row links only to a record the user is permitted to open.",
            "The top-bar badge and the sidebar counter both reflect the same unread count.",
            "Empty state explains which event types will appear here.",
        ],
        responsive=[
            ("Desktop 1440", "Single column list at 860 px measure with a right-hand filter rail."),
            ("Tablet 1024", "Filter rail moves above the list as a horizontal filter bar."),
            ("Mobile 390", "List is full width; per-row action becomes an icon-sized button."),
        ],
        blocks=[
            {"type": "header", "title": "Notifications",
             "subtitle": "Everything the system has told you, newest first.",
             "actions": [("Mark all as read", "primary")]},
            {"type": "tabs", "items": [("All (12)", True), ("Unread (3)", False),
                                       ("Scholarships", False), ("Guidance", False),
                                       ("Services", False), ("System", False)]},
            {"type": "card", "title": "Recent activity", "actions": [("Filter", "ghost")],
             "items": [{"type": "feed", "items": [
                 {"title": "Your application is under review",
                  "body": "SCH-2026-000184 · DOST-SEI Undergraduate Scholarship",
                  "when": "Today, 09:14", "unread": True, "action": "Open"},
                 {"title": "Appointment confirmed",
                  "body": "18 Nov 2026, 10:00–10:30 with Prof. R. Dela Cruz",
                  "when": "Today, 08:02", "unread": True, "action": "Open"},
                 {"title": "Additional information required",
                  "body": "SR-2026-000042 · Good Moral Certificate",
                  "when": "Yesterday, 16:40", "unread": True, "action": "Open"},
                 {"title": "Document verified",
                  "body": "Certificate of Enrollment for SCH-2026-000184",
                  "when": "12 Nov 2026, 11:22", "unread": False, "action": "Open"},
                 {"title": "New scholarship published",
                  "body": "CHED Merit Scholarship — closes 30 Nov 2026",
                  "when": "11 Nov 2026, 08:00", "unread": False, "action": "Open"},
             ]}]},
            {"type": "pager", "current": 1, "total": 3, "count": "5 of 12 notifications"},
        ],
    ),

    dict(
        id="WF-G-06", title="Application shell (sidebar and top bar)", role="student",
        folder="shared", nav_active="student.dashboard",
        route="— (layouts/app, components/navigation/*)", module="M16",
        reqs="NFR-USA-01, NFR-PORT-01, NFR-ACC-01, FR-NOTIF-06",
        purpose="Define the persistent frame shared by every authenticated page: a 264 px "
                "sidebar, a 60 px top bar, and a single main content region.",
        annotations=[
            "Sidebar width is fixed at 264 px on desktop and holds role-appropriate links only.",
            "Section labels group links; the active link carries a left rule and a filled background.",
            "The notification count appears twice: on the sidebar link and on the top-bar bell.",
            "Top bar carries the menu toggle (small screens), quick search, bell, user chip and logout.",
            "Below 1024 px the sidebar leaves the flow and the menu toggle reveals it as a drawer.",
            "A skip link precedes the shell so keyboard users can jump straight to main content.",
            "Main content begins with a page header; the shell never renders page-specific actions.",
        ],
        responsive=[
            ("Desktop 1440", "Sidebar fixed at 264 px; content region 1176 px minus padding."),
            ("Tablet 1024", "Sidebar hidden; menu toggle in the top bar opens it as an overlay drawer."),
            ("Mobile 390", "Top bar reduces to toggle, bell and user; search moves into the page."),
        ],
        blocks=[
            {"type": "tabs", "items": [("Student shell", True), ("Counselor shell", False),
                                       ("Administrator shell", False)]},
            {"type": "html", "html": """
<div class="shell-spec" style="display:grid;grid-template-columns:264px minmax(0,1fr);gap:0;
     border:1px solid var(--line-2);border-radius:8px;overflow:hidden;min-height:340px">
  <div class="spec-side" style="background:var(--fill);border-right:1px solid var(--line-2);padding:14px 16px">
    <div class="mono small muted" style="margin-bottom:8px">1 &middot; SIDEBAR 264 px</div>
    <div style="display:grid;gap:6px;font-size:12px">
      <div style="font-weight:700">Brand &mdash; ISU mark, system name</div>
      <div class="muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;
           margin-top:8px">Section label</div>
      <div style="border-left:3px solid var(--ink);background:var(--fill-2);padding:5px 8px;
           font-weight:600">Active link</div>
      <div style="padding:5px 8px;color:var(--ink-2)">Inactive link</div>
      <div style="padding:5px 8px;color:var(--ink-2)">Inactive link
        <span class="badge" style="float:right;font-size:9px">3</span></div>
      <div class="muted" style="font-size:10px;border-top:1px solid var(--line-2);
           margin-top:10px;padding-top:8px">Footer &mdash; university and office</div>
    </div>
  </div>
  <div>
    <div style="height:60px;border-bottom:1px solid var(--line-2);display:flex;align-items:center;
         gap:10px;padding:0 14px;background:#fff">
      <div class="mono small muted">2 &middot; TOP BAR 60 px</div>
      <span style="flex:1"></span>
      <div class="wf-search" style="min-width:170px;font-size:11px">&#9906; Quick search</div>
      <div class="wf-bell">&#128276;<span class="dot">3</span></div>
      <div class="small">User<br><span class="muted" style="font-size:10px">(Role)</span></div>
      <button class="wf-btn sm">Logout</button>
    </div>
    <div style="padding:16px">
      <div class="mono small muted" style="margin-bottom:8px">3 &middot; MAIN REGION</div>
      <div class="card" style="border-style:dashed">
        <div class="card-b">
          <b style="font-size:13px">Page header</b>
          <div class="small muted">Title, one-line purpose, and the page's primary actions.</div>
          <div class="divider"></div>
          <div class="small muted">Page content: filters, results, forms, detail panels.
            The shell contributes no page-specific markup.</div>
        </div>
      </div>
      <div class="small muted" style="margin-top:10px">
        4 &middot; Skip link precedes the shell and targets this main region.</div>
    </div>
  </div>
</div>"""},
            {"type": "notice", "kind": "info", "title": "Role variants",
             "text": "The counselor shell replaces Management with Guidance and OSAS sections; "
                     "the administrator shell adds Management, Insights and System sections. "
                     "The frame dimensions never change between roles."},
        ],
    ),
]
