#!/usr/bin/env python3
"""mk_ds.py - render the design-system page for the mockup deliverable."""

from __future__ import annotations

import pathlib

import mk_render


def contrast(a: str, b: str) -> float:
    def lin(c):
        c = c / 255
        return c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4

    def lum(h):
        h = h.lstrip("#")
        r, g, bl = (int(h[i:i + 2], 16) for i in (0, 2, 4))
        return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(bl)

    la, lb = lum(a), lum(b)
    hi, lo = max(la, lb), min(la, lb)
    return (hi + 0.05) / (lo + 0.05)


PALETTE = [
    ("Brand / primary", [("brand-deep", "#062e1d"), ("brand-dark", "#083f28"),
                         ("brand", "#0b5d3b"), ("brand-100", "#d5e7dd"),
                         ("brand-50", "#eaf3ee")]),
    ("Accent", [("accent", "#c9a227"), ("accent-soft", "#fbf5e2")]),
    ("Surface", [("canvas", "#f4f7f5"), ("paper", "#ffffff"), ("fill", "#f9fafb"),
                 ("fill-2", "#f3f4f6"), ("line-2", "#e5e7eb"), ("line", "#d1d5db")]),
    ("Text", [("text", "#1f2937"), ("ink-2", "#374151"), ("muted", "#6b7280")]),
    ("Semantic", [("ok", "#047857"), ("warn", "#b45309"), ("danger", "#b91c1c"),
                  ("info", "#1d4ed8")]),
]

STATUS_TABLE = [
    ("draft", "Draft", "Neutral", "Not yet visible to students"),
    ("submitted", "Submitted", "Info", "Received, no decision yet"),
    ("under_review", "Under review", "Info", "Being assessed"),
    ("document_verification", "Document verification", "Info", "Waiting on document checks"),
    ("approved", "Approved", "Success", "Final, favourable"),
    ("rejected", "Rejected", "Danger", "Final, unfavourable; reason required"),
    ("requested", "Requested", "Warning", "Appointment awaiting counselor"),
    ("confirmed", "Confirmed", "Success", "Slot reserved"),
    ("completed", "Completed", "Success", "Session held"),
    ("cancelled", "Cancelled", "Neutral", "Withdrawn by either party"),
    ("pending", "Pending", "Warning", "Verification not yet decided"),
    ("verified", "Verified", "Success", "Enrollment confirmed for the term"),
    ("unable_to_verify", "Unable to verify", "Warning", "Evidence insufficient, not a refusal"),
    ("processing", "Processing", "Info", "Handler is working on it"),
    ("requires_information", "Requires information", "Warning", "Waiting on the student"),
    ("open", "Open", "Success", "Accepting applications"),
    ("closed", "Closed", "Neutral", "Window passed"),
    ("archived", "Archived", "Neutral", "Hidden from students"),
    ("active", "Active", "Success", "Account or record usable"),
    ("inactive", "Inactive", "Neutral", "Login refused, history retained"),
]


def badge(status, label, kind):
    klass = "badge" + (" dash" if kind == "Neutral" else "")
    return f'<span class="{klass}" data-status="{status}">{label}</span>'


def section(num, title, lede, body):
    return (f'<section class="ds-sec"><h2>{num}. {title}</h2>'
            f'<p>{lede}</p>{body}</section>')


def swatch_block():
    out = []
    for group, items in PALETTE:
        out.append(f'<div class="ds-h3">{group}</div><div class="ds-swatches">')
        for name, hexv in items:
            edge = ("box-shadow:inset 0 0 0 1px #e5e7eb;" if contrast(hexv, "#ffffff") < 2.2
                    else "")
            out.append(f'<div class="ds-sw"><div class="chip" style="background:{hexv};'
                       f'{edge}"></div><div class="meta"><b>{name}</b>'
                       f'<code>{hexv}</code></div></div>')
        out.append("</div>")
    return "".join(out)


def contrast_block():
    pairs = [("#1f2937", "#ffffff", "Body text on white"),
             ("#6b7280", "#ffffff", "Muted text on white"),
             ("#0b5d3b", "#ffffff", "Brand on white"),
             ("#ffffff", "#0b5d3b", "White on brand (primary button)"),
             ("#ffffff", "#062e1d", "White on sidebar deep green"),
             ("#dcece4", "#062e1d", "Sidebar link on deep green"),
             ("#c9a227", "#062e1d", "Accent on deep green"),
             ("#047857", "#ecfdf5", "Success badge"),
             ("#b45309", "#fffbeb", "Warning badge"),
             ("#b91c1c", "#fef2f2", "Danger badge"),
             ("#1d4ed8", "#eff6ff", "Info badge")]
    rows = []
    for fg, bg, use in pairs:
        r = contrast(fg, bg)
        verdict = "AAA" if r >= 7 else ("AA" if r >= 4.5 else
                                        ("AA large" if r >= 3 else "Below AA"))
        role = "ok" if r >= 4.5 else ("warn" if r >= 3 else "error")
        rows.append(f'<tr><td>{use}</td><td class="mono">{fg} on {bg}</td>'
                    f'<td class="mono">{r:.2f}:1</td>'
                    f'<td><span class="badge {role}">{verdict}</span></td></tr>')
    return ('<div class="tw"><table class="wt"><thead><tr><th>Use</th><th>Pair</th>'
            '<th>Ratio</th><th>WCAG 2.2 AA</th></tr></thead>'
            f'<tbody>{"".join(rows)}</tbody></table></div>')


def type_block():
    rows = [
        ("<span style=\"font-size:22px;font-weight:650;letter-spacing:-.02em\">"
         "Scholarship applications</span>", "Page title &middot; 22 / 1.25 / 650"),
        ("<span style=\"font-size:13.5px;font-weight:650\">Requests awaiting your "
         "decision</span>", "Section title &middot; 13.5 / 650"),
        ("<span style=\"font-size:30px;font-weight:700;color:#083f28\">1,284</span>",
         "Metric &middot; 30 / 700 / tabular"),
        ("<span>Applicants must upload every required document.</span>",
         "Body &middot; 14 / 1.5"),
        ("<span style=\"font-size:12px;color:#6b7280\">Submitted 12 Nov 2026 &middot; "
         "3 days waiting</span>", "Small / meta &middot; 12 / 1.5"),
        ("<span style=\"font-size:10.5px;font-weight:700;text-transform:uppercase;"
         "letter-spacing:.07em;color:#6b7280\">Academic year</span>",
         "Label &middot; 10.5 / 700 / uppercase"),
        ("<span class=\"mono\" style=\"font-size:12.5px\">SCH-2026-000184</span>",
         "Identifier &middot; 12.5 / mono"),
    ]
    body = "".join(f'<tr><td>{specimen}</td><td class="spec">{spec}</td></tr>'
                   for specimen, spec in rows)
    return f'<div class="ds-type"><table><tbody>{body}</tbody></table></div>'


def buttons_block():
    return ('<div class="ds-row"><span class="lab">Variants</span>'
            '<button class="wf-btn primary">Save changes</button>'
            '<button class="wf-btn">Cancel</button>'
            '<button class="wf-btn outline">Export CSV</button>'
            '<button class="wf-btn ghost">View queue</button>'
            '<button class="wf-btn danger">Deactivate account</button></div>'
            '<div class="ds-row"><span class="lab">Sizes</span>'
            '<button class="wf-btn primary">Default 36 px</button>'
            '<button class="wf-btn primary sm">Small 28 px</button>'
            '<button class="wf-btn sm">Small secondary</button>'
            '<span class="small muted">One primary action per screen; destructive '
            'actions sit away from the primary path.</span></div>')


def controls_block():
    return ('<div class="ds-row" style="align-items:flex-start">'
            '<div class="field" style="width:224px"><label>Academic year</label>'
            '<div class="ctl"><span>2025&ndash;2026</span>'
            '<span class="car">&#9660;</span></div>'
            '<div class="fhelp">Applies to every figure on the screen.</div></div>'
            '<div class="field" style="width:224px"><label>Search student</label>'
            '<div class="ctl"><span class="ph">Name or student number</span>'
            '<span class="car">&#9906;</span></div></div>'
            '<div class="field" style="width:224px"><label>Decision '
            '<span class="req">*</span></label>'
            '<div class="ctl" style="border-color:#b91c1c"><span>Not selected</span>'
            '<span class="car">&#9660;</span></div>'
            '<div class="ferr">! Choose a decision before saving.</div></div>'
            '<div class="field" style="width:224px"><label>Deadline</label>'
            '<div class="ctl"><span class="ph">dd / mm / yyyy</span>'
            '<span class="car">&#128197;</span></div></div></div>')


def status_block():
    first = "".join(badge(s, label, kind) for s, label, kind, _d in STATUS_TABLE[:10])
    second = "".join(badge(s, label, kind) for s, label, kind, _d in STATUS_TABLE[10:])
    rows = "".join(
        f'<tr><td class="mono">{s}</td><td>{badge(s, label, kind)}</td>'
        f'<td>{kind}</td><td>{desc}</td></tr>'
        for s, label, kind, desc in STATUS_TABLE)
    table = ('<div class="tw" style="margin-top:14px"><table class="wt"><thead><tr>'
             '<th>Stored value</th><th>Displayed as</th><th>Colour role</th>'
             '<th>Meaning</th></tr></thead>'
             f'<tbody>{rows}</tbody></table></div>')
    return f'<div class="ds-row">{first}</div><div class="ds-row">{second}</div>{table}'


def table_block():
    return """<div class="tw"><table class="wt"><thead><tr>
<th>Application no.</th><th>Student</th><th>Scholarship</th><th>Documents</th>
<th>Status</th><th></th></tr></thead><tbody>
<tr><td class="mono">SCH-2026-000184</td><td>Maria L. Santos</td>
<td>DOST-SEI Undergraduate</td><td class="num">1 / 3</td>
<td><span class="badge" data-status="under_review">Under review</span></td>
<td class="act"><button class="wf-btn sm ghost">Review</button></td></tr>
<tr><td class="mono">SCH-2026-000181</td><td>Jose P. Rizal</td>
<td>CHED Merit Scholarship</td><td class="num">3 / 3</td>
<td><span class="badge" data-status="document_verification">Document verification</span></td>
<td class="act"><button class="wf-btn sm ghost">Review</button></td></tr>
<tr><td class="mono">SCH-2026-000177</td><td>Ana C. Reyes</td>
<td>ISU Presidential Grant</td><td class="num">2 / 2</td>
<td><span class="badge solid" data-status="approved">Approved</span></td>
<td class="act"><button class="wf-btn sm ghost">Open</button></td></tr>
</tbody></table></div>"""


def metrics_block():
    stats = """<div class="grid c4">
<div class="stat"><div class="k">Registered students</div><div class="v">1,284</div>
<div class="m">42 awaiting verification</div></div>
<div class="stat"><div class="k">Live applications</div><div class="v">96</div>
<div class="m">31 in document verification</div></div>
<div class="stat"><div class="k">Pending appointments</div><div class="v">12</div>
<div class="m">Oldest waiting 1 day</div></div>
<div class="stat"><div class="k">Open service requests</div><div class="v">27</div>
<div class="m">5 awaiting student information</div></div>
</div>"""
    notices = """<div class="notice"><span class="ic">i</span><div>
<b>Documents are verified individually</b>Approval stays blocked until every required
requirement has at least one verified upload.</div></div>
<div class="notice warn"><span class="ic">!</span><div>
<b>Completion is irreversible</b>A completed appointment cannot return to confirmed.</div></div>
<div class="notice error"><span class="ic">!</span><div>
<b>Weights must total 100</b>The entered weights total 96.00. Nothing was saved.</div></div>
<div class="notice ok"><span class="ic">&#10003;</span><div>
<b>Decision recorded</b>The student was notified and the audit entry was written.</div></div>"""
    timeline = """<ul class="tl">
<li><div class="when">13 Nov 2026 08:12</div><div class="what">Moved to under review</div>
<div class="why">Reviewed by A. Bautista.</div></li>
<li><div class="when">12 Nov 2026 09:14</div><div class="what">Submitted</div>
<div class="why">Submitted by Maria L. Santos.</div></li>
<li><div class="when">10 Nov 2026 16:02</div><div class="what">Draft created</div>
<div class="why">Application SCH-2026-000184 assigned.</div></li>
</ul>"""
    steps = """<div class="steps"><div class="st done"><span class="dot">&#10003;</span>Submitted</div>
<span class="ln"></span><div class="st now"><span class="dot">2</span>Under review</div>
<span class="ln"></span><div class="st"><span class="dot">3</span>Document verification</div>
<span class="ln"></span><div class="st"><span class="dot">4</span>Decision</div></div>"""
    slots = """<div class="slots">
<div class="slot taken">09:00<small>Taken</small></div>
<div class="slot free">09:30<small>Available</small></div>
<div class="slot sel">10:00<small>Selected</small></div>
<div class="slot free">10:30<small>Available</small></div>
<div class="slot taken">11:00<small>Taken</small></div>
</div>"""
    return (stats + '<div class="divider"></div>' + notices +
            '<div class="divider"></div>' + timeline +
            '<div class="ds-h3">Workflow position</div>' + steps +
            '<div class="ds-h3">Bookable slots</div>' + slots)


def charts_block():
    bars = "".join(
        f'<div class="b"><em>{v}</em><i style="height:{v / 38 * 100:.0f}%"></i>'
        f'<span>{l}</span></div>'
        for l, v in [("Jun", 12), ("Jul", 18), ("Aug", 24), ("Sep", 31),
                     ("Oct", 38), ("Nov", 23)])
    return ('<div class="ds-grid2">'
            '<div class="chart"><h4>Applications per month</h4>'
            '<div class="sub">Submitted in the selected period</div>'
            f'<div class="bars">{bars}</div></div>'
            '<div class="chart"><h4>Service requests</h4>'
            '<div class="sub">By current status</div>'
            '<div class="donut"><div class="ring"></div><div class="lg">'
            '<div><i></i>Completed <b>34</b></div><div><i></i>Processing <b>11</b></div>'
            '<div><i></i>Submitted <b>8</b></div><div><i></i>Requires info <b>5</b></div>'
            '</div></div></div></div>')


def icons_block():
    names = ("grid", "award", "star", "file", "calendar", "layers", "user", "bell",
             "clock", "check", "users", "id", "clipboard", "sliders", "inbox", "tag",
             "chart", "shield", "search", "menu", "logout")
    return '<div class="ds-row" style="padding:20px;gap:14px">' + "".join(
        '<div style="display:flex;flex-direction:column;align-items:center;gap:7px;'
        'width:74px"><span style="color:#0b5d3b">' + mk_render.icon(n) + '</span>'
        f'<code style="font-size:9.5px;color:#6b7280">{n}</code></div>'
        for n in names) + '</div>'


def nav_block():
    sidebar = mk_render.mk_sidebar("admin", "admin.scholarships.index")
    topbar = mk_render.mk_topbar("admin")
    return ('<div class="ds-grid2">'
            '<div><div class="ds-h3">Sidebar &mdash; 264 px, deep green</div>'
            '<div style="border-radius:14px;overflow:hidden;box-shadow:0 4px 12px '
            'rgba(16,24,40,.08);max-width:264px">' + sidebar + '</div></div>'
            '<div><div class="ds-h3">Top bar &mdash; 60 px, white</div>'
            '<div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;'
            'background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.05)">' + topbar + '</div>'
            '<div class="ds-note">The sidebar is persistent above 1024 px and collapses '
            'to the menu toggle below it. The search field is dropped at phone width, '
            'where it would compete with the page title. The notification count and the '
            'account chip are the only always-visible status indicators.</div></div></div>')


def content_block():
    return """<div class="ds-grid2">
<div class="card"><div class="card-h"><h3>Do</h3></div><div class="card-b">
<div class="small" style="display:grid;gap:8px">
<p style="margin:0">&ldquo;Two required documents have no verified upload.&rdquo;</p>
<p style="margin:0">&ldquo;The deadline must be on or after the start date.&rdquo;</p>
<p style="margin:0">&ldquo;Requests in this state are waiting on the student.&rdquo;</p>
</div></div></div>
<div class="card"><div class="card-h"><h3>Do not</h3></div><div class="card-b">
<div class="small" style="display:grid;gap:8px">
<p style="margin:0">&ldquo;Oops! Something went wrong.&rdquo;</p>
<p style="margin:0">&ldquo;Invalid input.&rdquo;</p>
<p style="margin:0">&ldquo;Please kindly be informed that&hellip;&rdquo;</p>
</div></div></div>
</div>
<div class="ds-note">Sentence case for everything except badges and table headers. Dates
are written <span class="ds-token">15 Nov 2026</span>; identifiers keep their generated
form <span class="ds-token">SCH-2026-000184</span>; money uses two decimals and a
thousands separator <span class="ds-token">PHP 148,000.00</span>; percentages use one
decimal <span class="ds-token">6.5%</span>. Status words are never abbreviated.</div>"""


def governance_block():
    return """<div class="tw"><table class="wt"><thead><tr><th>Token group</th>
<th>Defined in</th><th>Change process</th></tr></thead><tbody>
<tr><td>Colour, radius, spacing, type</td><td class="mono">public/css/app.css</td>
<td>Change the application first, then regenerate this page; the mockups follow.</td></tr>
<tr><td>Component markup</td><td class="mono">documentation/_tools/wf_kit.py</td>
<td>One renderer serves both wireframes and mockups, so a change is made once.</td></tr>
<tr><td>Mockup styling</td><td class="mono">UI-UX-Mockups/assets/mockup.css</td>
<td>Values are copied from the application stylesheet; no new colour is introduced.</td></tr>
<tr><td>Status vocabulary</td><td class="mono">_spec/requirements.csv</td>
<td>A new stored status must also reach the state machine, the badge map and the
traceability matrix.</td></tr>
</tbody></table></div>
<div class="ds-note">No Figma file accompanies this deliverable. The design system is
implemented as HTML and CSS so that it can be inspected in a browser, diffed in version
control, and rendered into the document without a second source of truth.</div>"""


def build_design_system(out_dir: pathlib.Path) -> pathlib.Path:
    hero = ('<div class="ds-hero">'
            '<div class="eyebrow">Design system &middot; version 1.0</div>'
            '<h1>ISU OSAS Hub interface language</h1>'
            '<p>A single visual and interaction vocabulary for the 46 high-fidelity '
            'screens in this deliverable. Every token is taken from the stylesheet the '
            'application actually loads (<span class="mono">public/css/app.css</span>), '
            'so the mockups and the delivered system describe the same interface. '
            'Where a value is not present in the application it is marked as proposed '
            'and never used as though it were implemented.</p></div>')

    body = "".join([
        hero,
        section(1, "Colour",
                "Institutional green carries structure and primary action; university "
                "gold is reserved for emphasis and never for status. Status never relies "
                "on colour alone: every badge also carries a word.", swatch_block()),
        section(2, "Typography",
                "One family, one scale. Segoe UI is first because it is present on the "
                "university's Windows fleet; the stack falls back to the platform UI "
                "font elsewhere. Monospace is used only for identifiers, times and "
                "weights, where character alignment aids comparison.", type_block()),
        section(3, "Space, radius and elevation",
                "A 4 px base unit governs padding and gaps. Radii are 6 px for controls, "
                "10 px for inputs and notices, 14 px for cards and panels. Only two "
                "shadows are used: a hairline for resting cards and a larger one for "
                "overlays and device frames.",
                '<div class="ds-row"><span class="lab">Spacing</span>'
                '<span class="mono small">4 &middot; 8 &middot; 12 &middot; 16 &middot; '
                '20 &middot; 24 &middot; 32 &middot; 40</span>'
                '<span class="small muted">Body padding 26/28 px; card padding 18/20 px; '
                'field gap 15 px.</span></div>'
                '<div class="ds-row"><span class="lab">Radius</span>'
                '<span style="width:56px;height:34px;border:1px solid #d1d5db;'
                'border-radius:6px;background:#fff"></span>'
                '<span style="width:56px;height:34px;border:1px solid #d1d5db;'
                'border-radius:10px;background:#fff"></span>'
                '<span style="width:56px;height:34px;border:1px solid #d1d5db;'
                'border-radius:14px;background:#fff"></span>'
                '<span class="small muted">6 control &middot; 10 input / notice &middot; '
                '14 card / panel</span></div>'
                '<div class="ds-row"><span class="lab">Elevation</span>'
                '<span style="width:120px;height:44px;border:1px solid #e5e7eb;'
                'border-radius:10px;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.05)">'
                '</span>'
                '<span style="width:120px;height:44px;border:1px solid #e5e7eb;'
                'border-radius:10px;background:#fff;box-shadow:0 4px 12px rgba(16,24,40,.08)">'
                '</span>'
                '<span class="small muted">Resting card &middot; raised panel / overlay'
                '</span></div>'),
        section(4, "Buttons",
                "A screen has exactly one primary action. Secondary actions are outlined; "
                "navigational actions are ghost. Destructive actions are visually "
                "distinct and never adjacent to the primary action without a "
                "confirmation step.", buttons_block()),
        section(5, "Form controls",
                "Labels sit above controls and are never used as placeholders. Required "
                "fields carry an asterisk; help text explains consequences; errors appear "
                "under the field and repeat at the top of the form after a failed submit.",
                controls_block()),
        section(6, "Status vocabulary",
                "The system stores snake_case values and displays sentence case. Each "
                "stored value maps to one colour role, and the word is always present so "
                "colour is redundant. This table is the contract between the database "
                "enum and the interface.", status_block()),
        section(7, "Tables",
                "Tables are the default for comparable records. The last column is "
                "reserved for row actions; numeric columns use tabular figures; status is "
                "a badge, never a coloured row. Below 768 px the header is dropped and "
                "each row restacks with its column label.", table_block()),
        section(8, "Metrics, notices and history",
                "Metrics answer a single question each and always carry a qualifier. "
                "Notices state a consequence, not encouragement. History is append-only "
                "and always names the actor and the time.", metrics_block()),
        section(9, "Navigation",
                "Navigation is fixed and identical on every authenticated screen: the "
                "sections mirror the route groups, so a user can predict where a function "
                "lives. The active item is marked by a gold rule and a brighter surface.",
                nav_block()),
        section(10, "Charts",
                "Charts are descriptive. They show counts that reconcile with the queues "
                "they summarise, never rates that cannot be traced back to records. A "
                "chart always states its series and its period.", charts_block()),
        section(11, "Icons",
                "A single stroke-based set at 24 px, drawn to inherit the text colour. "
                "Icons never carry meaning alone: every control whose only content is an "
                "icon has an accessible label.", icons_block()),
        section(12, "Accessibility",
                "Colour pairs are checked rather than assumed, focus is always visible, "
                "and no state is conveyed by colour alone. The ratios below are measured, "
                "not estimated.", contrast_block()),
        section(13, "Content rules",
                "Wording is the interface. These rules keep the screens speaking with one "
                "voice.", content_block()),
        section(14, "Governance",
                "The tokens are not aspirational. They are read from the application "
                "stylesheet, and this page is generated from the same source as the "
                "mockups.", governance_block()),
    ])

    doc = """<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ISU OSAS Hub &middot; Design system</title>
<link rel="stylesheet" href="assets/mockup.css">
<style>
  body { padding: 34px 28px 80px; background: #eef2f0; }
  .ds-sec { scroll-margin-top: 20px; }
</style>
</head><body><div class="ds">""" + body + "</div></body></html>"

    path = out_dir / "design-system.html"
    path.write_text(doc, encoding="utf-8", newline="\n")
    return path

PLATE_TOKENS = [
    ("brand-deep", "#062e1d"), ("brand-dark", "#083f28"), ("brand", "#0b5d3b"),
    ("brand-50", "#eaf3ee"), ("accent", "#c9a227"), ("canvas", "#f4f7f5"),
    ("paper", "#ffffff"), ("text", "#1f2937"), ("muted", "#6b7280"),
    ("ok", "#047857"), ("warn", "#b45309"), ("danger", "#b91c1c"),
]

PLATE_TYPE = [
    ("22 / 1.25 / 650",
     '<span style="font-size:20px;font-weight:650;letter-spacing:-.02em">'
     'Scholarship applications</span>'),
    ("13.5 / 650",
     '<span style="font-size:13px;font-weight:650">Requests awaiting your decision</span>'),
    ("14 / 1.5", '<span>Applicants must upload every required document.</span>'),
    ("12 / 1.5",
     '<span style="font-size:11.5px;color:#6b7280">Submitted 12 Nov 2026 &middot; 3 days waiting</span>'),
    ("10.5 / 700 / caps",
     '<span style="font-size:10px;font-weight:700;text-transform:uppercase;'
     'letter-spacing:.07em;color:#6b7280">Academic year</span>'),
    ("12.5 / mono",
     '<span class="mono" style="font-size:11.5px">SCH-2026-000184</span>'),
]


def build_plate(out_dir: pathlib.Path) -> pathlib.Path:
    """A single, dense plate of the core tokens, sized for the printed report."""
    sw = "".join(
        f'<div class="sw"><div class="chip" style="background:{hexv};'
        f'{"box-shadow:inset 0 0 0 1px #e5e7eb;" if contrast(hexv, "#ffffff") < 2.2 else ""}">'
        f'</div><b>{name}</b><code>{hexv}</code></div>'
        for name, hexv in PLATE_TOKENS)
    ty = "".join(
        f'<tr><td>{specimen}</td><td class="spec">{spec}</td></tr>'
        for spec, specimen in PLATE_TYPE)
    badges = "".join(
        badge(s, label, kind) for s, label, kind, _d in STATUS_TABLE[:8])
    doc = f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<title>Design system plate</title>
<link rel="stylesheet" href="mockup.css">
<style>
  body {{ margin: 0; padding: 20px 24px 22px; background: #fff;
    font-family: var(--sans); color: var(--text); width: 960px; }}
  .hd {{ display: flex; align-items: baseline; gap: 12px;
    border-bottom: 2px solid var(--brand); padding-bottom: 8px; }}
  .hd b {{ font-size: 14px; letter-spacing: -.01em; color: var(--brand-dark); }}
  .hd span {{ font-size: 9.5px; color: var(--muted); margin-left: auto;
    text-transform: uppercase; letter-spacing: .08em; }}
  .cols {{ display: grid; grid-template-columns: 1.15fr 1fr; gap: 22px; }}
  .sec {{ font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; color: var(--muted); margin: 12px 0 6px; }}
  .pal {{ display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 5px; }}
  .sw {{ border: 1px solid #e5e7eb; border-radius: 5px; overflow: hidden; }}
  .sw .chip {{ height: 26px; }}
  .sw b {{ display: block; font-size: 7.5px; font-weight: 650; padding: 3px 4px 0; }}
  .sw code {{ display: block; font: 600 7px/1.35 var(--mono); color: var(--muted);
    padding: 0 4px 4px; }}
  table {{ width: 100%; border-collapse: collapse; }}
  td {{ padding: 4px 0; border-bottom: 1px solid #eef1ef; vertical-align: baseline; }}
  tr:last-child td {{ border-bottom: 0; }}
  .spec {{ font: 600 8px/1.4 var(--mono); color: var(--muted);
    text-align: right; white-space: nowrap; }}
  .row {{ display: flex; gap: 6px; align-items: center; flex-wrap: wrap;
    padding: 7px 8px; background: #f9fafb; border: 1px solid #e5e7eb;
    border-radius: 7px; }}
  .row .wf-btn {{ font-size: 10.5px; padding: 6px 10px; }}
  .row .badge {{ font-size: 8.5px; padding: 2px 7px; }}
  table.demo td {{ padding: 5px 4px; font-size: 9.5px; }}
  table.demo td.num {{ text-align: right; }}
  table.demo .mono {{ font-family: var(--mono); font-size: 8.5px; }}
  table.demo .badge {{ font-size: 8px; padding: 2px 6px; }}
  .notice {{ font-size: 9.5px; padding: 6px 8px; margin: 0 0 5px; }}
  .notice b {{ display: block; font-size: 9.5px; }}
  .notice:last-child {{ margin-bottom: 0; }}
</style></head>
<body>
  <div class="hd"><b>ISU OSAS Hub &middot; design system plate</b>
    <span>tokens from public/css/app.css</span></div>
  <div class="cols">
    <div>
      <div class="sec">Colour &mdash; institutional green, university gold, neutral surfaces</div>
      <div class="pal">{sw}</div>
      <div class="sec">Type scale &mdash; one family, six roles</div>
      <table><tbody>{ty}</tbody></table>
    </div>
    <div>
      <div class="sec">Actions &mdash; one primary per screen</div>
      <div class="row" style="margin-bottom:2px">
        <button class="wf-btn primary">Save changes</button>
        <button class="wf-btn">Cancel</button>
        <button class="wf-btn outline">Export CSV</button>
        <button class="wf-btn danger">Deactivate</button>
      </div>
      <div class="sec">Status &mdash; stored value, word, colour</div>
      <div class="row">{badges}</div>
      <div class="sec">Records &mdash; one status word, never colour alone</div>
      <table class="demo"><tbody>
        <tr><td class="mono">SCH-2026-000184</td><td>Maria L. Santos</td>
          <td class="num">1 / 3</td>
          <td><span class="badge" data-status="under_review">Under review</span></td></tr>
        <tr><td class="mono">SCH-2026-000181</td><td>Jose P. Rizal</td>
          <td class="num">3 / 3</td>
          <td><span class="badge" data-status="document_verification">Document verification</span></td></tr>
        <tr><td class="mono">SCH-2026-000177</td><td>Ana C. Reyes</td>
          <td class="num">2 / 2</td>
          <td><span class="badge solid" data-status="approved">Approved</span></td></tr>
      </tbody></table>
      <div class="sec">Consequence, not encouragement</div>
      <div class="notice"><span class="ic">i</span><div>
        <b>Documents are verified individually</b>Approval stays blocked until every
        required document has a verified upload.</div></div>
      <div class="notice error"><span class="ic">!</span><div>
        <b>Weights must total 100</b>The entered weights total 96.00. Nothing was saved.</div></div>
    </div>
  </div>
</body></html>"""
    path = out_dir / "assets" / "design-plate.html"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(doc, encoding="utf-8", newline="\n")
    return path
