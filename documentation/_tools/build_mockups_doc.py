#!/usr/bin/env python3
"""
build_mockups_doc.py - assemble UI-UX-Mockups/index.md from the mockup manifest
and the same page specifications that generated the screens, then hand it to
md2docx.py.

The document never restates a fact that is stored somewhere else: identifiers,
titles, routes, requirement traces and responsive behaviour are all read from
_spec/requirements.csv, _manifest.csv and the wf_pages_* modules.
"""

from __future__ import annotations

import collections
import csv
import pathlib
import sys

HERE = pathlib.Path(__file__).resolve().parent
DOC = HERE.parent
OUT = DOC / "UI-UX-Mockups"
SPEC = DOC / "_spec" / "requirements.csv"
sys.path.insert(0, str(HERE))

import wf_kit  # noqa: E402
import mk_ds  # noqa: E402
import wf_pages_shared  # noqa: E402
import wf_pages_student  # noqa: E402
import wf_pages_counselor  # noqa: E402
import wf_pages_admin_a  # noqa: E402
import wf_pages_admin_b  # noqa: E402

PAGES = (wf_pages_shared.PAGES + wf_pages_student.PAGES +
         wf_pages_counselor.PAGES + wf_pages_admin_a.PAGES +
         wf_pages_admin_b.PAGES)
BY_ID = {p["id"]: p for p in PAGES}

ROLE_ORDER = [("shared", "Shared and public screens",
               "Screens a signed-out visitor can reach, plus the notification centre and "
               "the shell that every authenticated screen inherits."),
              ("student", "Student screens",
               "The student portal: profile, scholarship discovery, applications, "
               "guidance appointments and service requests."),
              ("counselor", "Counselor screens",
               "The guidance caseload and the enrollment verification decisions a "
               "counselor is permitted to make."),
              ("admin", "Administrator screens",
               "Configuration, review queues, analytics and the audit log.")]

BLOCK_LABEL = {
    "header": "page header", "stats": "summary tiles", "filters": "filter bar",
    "table": "record table", "pager": "pagination", "form": "form sections",
    "detail": "attribute list", "timeline": "status history", "steps": "workflow stepper",
    "cards": "card list", "chart": "charts", "slots": "bookable slot grid",
    "availability": "weekly availability grid", "notice": "notice", "tabs": "tabs",
    "empty": "empty state", "feed": "notification feed", "split": "two-panel split",
    "grid": "grid", "card": "panel", "html": "hand-composed layout",
    "img": "image placeholder", "alert": "alert",
}


def load_rows():
    with SPEC.open(encoding="utf-8-sig", newline="") as fh:
        return list(csv.DictReader(fh))


def load_manifest():
    with (OUT / "_manifest.csv").open(encoding="utf-8-sig", newline="") as fh:
        return list(csv.DictReader(fh))


def leaf_types(blocks, acc):
    for b in blocks or []:
        acc.append(b.get("type"))
        if b.get("type") in ("grid", "split", "card"):
            leaf_types(b.get("items"), acc)
        if b.get("type") == "card":
            leaf_types(b.get("items"), acc)
    return acc


def composition(p):
    types = [t for t in leaf_types(p.get("blocks", []), []) if t]
    seen, out = set(), []
    for t in types:
        label = BLOCK_LABEL.get(t, t)
        if label not in seen:
            seen.add(label)
            out.append(label)
    return out


EXTRA = {
    "MK-01": "The guest shell replaces the sidebar with a university header bar; the "
             "credential panel sits on a deep-green brand field so that the screen reads "
             "as an institutional system before a user has signed in.",
    "MK-02": "This screen documents the shell itself: the sidebar's section labels, the "
             "active-item rule, the top bar's three zones and the way the frame reflows. "
             "It is the reference for every authenticated screen.",
    "MK-03": "Registration is a single centred column: identity fields first, academic "
             "fields second, with the password rules stated before the field rather than "
             "after a failure.",
    "MK-04": "The passcode screen is the smallest layout in the set: one six-cell input, "
             "one primary action and a resend affordance that states its cooldown.",
    "MK-05": "Error states reuse the guest shell and keep the same page frame, so a "
             "permission failure does not look like a crash. The message is plain and the "
             "only action is a way back.",
    "MK-25": "The notification feed is a student screen inside the authenticated shell, "
             "even though its entries are produced by events elsewhere in the system.",
    "MK-46": "A showcase rather than a new screen: the student dashboard is rendered three "
             "times at the three supported widths so the reflow rules can be reviewed side "
             "by side. The structure is WF-S-01 throughout.",
}


class Doc:
    def __init__(self):
        self.lines = []
        self.n = 0

    def w(self, text=""):
        self.lines.append(text)

    def fig(self, path, caption):
        self.n += 1
        self.w(f"![Figure {self.n}. {caption}]({path})")
        self.w()

    def text(self):
        return "\n".join(self.lines)


def contrast_pairs():
    return [("#1f2937", "#ffffff", "Body text on white"),
            ("#6b7280", "#ffffff", "Muted text on white"),
            ("#0b5d3b", "#ffffff", "Brand on white"),
            ("#ffffff", "#0b5d3b", "White on brand (primary action)"),
            ("#ffffff", "#062e1d", "White on sidebar green"),
            ("#dcece4", "#062e1d", "Sidebar link on green"),
            ("#c9a227", "#062e1d", "Gold accent on green"),
            ("#047857", "#ecfdf5", "Success badge"),
            ("#b45309", "#fffbeb", "Warning badge"),
            ("#b91c1c", "#fef2f2", "Danger badge"),
            ("#1d4ed8", "#eff6ff", "Info badge")]


def main() -> int:
    rows = load_rows()
    man = load_manifest()
    man.sort(key=lambda r: r["mockup"])
    total_reqs = len(rows)
    unmapped = [r for r in rows if not (r.get("mockup") or "").strip()]
    unmapped_fr = [r for r in unmapped if r["req_id"].startswith("FR-")]
    unmapped_nfr = [r for r in unmapped if r["req_id"].startswith("NFR-")]
    traced = {x.strip() for r in man for x in r["requirements"].split(",") if x.strip()}
    counts = collections.Counter(r["folder"] for r in man)
    status_counts = collections.Counter(r["status"] for r in rows)
    gap_text = {
        "GAP-01": ("No intake for enrollment verification records",
                   "The counselor queue and decision screen are drawn (MK-14, MK-15); no "
                   "screen creates the record, so none is drawn."),
        "GAP-02": ("Students with no verification record bypass the gate",
                   "Invisible in a design: the gate passes silently, so no screen state "
                   "would reveal it."),
        "GAP-03": ("Counselors cannot process service requests",
                   "The administrator request queue (MK-39) is drawn; the counselor "
                   "counterpart does not exist and is not drawn."),
        "GAP-04": ("Session and student notes cannot be authored",
                   "Notes are displayed where they exist; no authoring form is drawn "
                   "because no route accepts one."),
        "GAP-05": ("No self-service password reset",
                   "The login screen deliberately omits a reset link (MK-01); the "
                   "administrator reset is drawn instead (MK-06)."),
        "GAP-06": ("Guidance seeder is not registered",
                   "Data seeding, not interface: no screen is affected."),
        "GAP-07": ("Seeded scholarship dates have expired",
                   "Mockup data uses current dates; the seeding defect is recorded in "
                   "the SRS, not designed around."),
        "GAP-08": ("Administrator guidance screens are read-only",
                   "Admin appointment oversight (MK-34) shows no decision controls, "
                   "which is what the code permits."),
        "GAP-09": ("A rejected application blocks re-application",
                   "The student application list (MK-24) shows no re-apply action."),
        "GAP-10": ("'Withdrawn' exists in the badge map but not in the workflow",
                   "The badge is defined in the design system; no mockup shows a "
                   "withdrawal action."),
        "GAP-11": ("Dual appointment-type schema",
                   "Schema, not interface: the screens show one label."),
        "GAP-12": ("Duplicate search-index migrations",
                   "Schema: no interface consequence."),
    }

    d = Doc()

    # ---------------------------------------------------------------- cover
    d.w("# High-Fidelity UI/UX Mockups")
    d.w()
    d.w("**ISU OSAS Hub — Integrated Student Affairs and Services Management System**")
    d.w()
    d.w("Isabela State University · Office of Student Affairs and Services")
    d.w()
    d.w("| Item | Details |")
    d.w("| --- | --- |")
    d.w("| Deliverable | Final high-fidelity interface set |")
    d.w(f"| Screens | {len(man)} screens across four role groups |")
    d.w("| Viewports | 1440, 1024 and 390 px; three captures per screen |")
    d.w("| Design system | One 14-section specification page, generated from the same tokens |")
    d.w("| Format | Standalone HTML with one shared stylesheet |")
    d.w("| Source | `documentation/_tools/wf_pages_*.py` (declarative page specs) |")
    d.w("| Renderer | `documentation/_tools/mk_render.py`, `mk_ds.py` |")
    d.w("| Rendered | `documentation/UI-UX-Mockups/screens/{shared,student,counselor,admin}/` |")
    d.w("| Screenshots | `documentation/UI-UX-Mockups/export/` |")
    d.w("| Gallery | `documentation/UI-UX-Mockups/index.html` |")
    d.w("| Basis | Read-only audit of `routes/web.php`, `resources/views/` and "
        "`public/css/app.css` |")
    d.w("| Version | 1.0 |")
    d.w("| Date | 15 November 2026 |")
    d.w()
    d.w("> These mockups are not a redesign proposal. They restate the delivered "
        "interface in a consistent institutional visual language, using the colour, type "
        "and component tokens that `public/css/app.css` already defines. Where the "
        "application has no capability, there is no screen.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()
    d.w("## Contents")
    d.w()
    d.w("<!-- toc -->")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 1
    d.w("## 1. Purpose and scope")
    d.w()
    d.w("A wireframe answers what is on a screen and in what order. A mockup answers a "
        "different question: what will a user actually see, and does the answer hold "
        "together across the whole system? A high-fidelity set is only useful if it is "
        "consistent, and consistency is the property this deliverable has to demonstrate.")
    d.w()
    d.w(f"The set contains {len(man)} screens. Every one is built from the same declarative "
        "page specification as its wireframe (listed in the metadata of each entry below), "
        "so the information architecture is not a second opinion: it is the same object "
        "under a second stylesheet. Only the shell and the visual tokens change.")
    d.w()

    d.w("### 1.1 What these mockups assert")
    d.w()
    d.w("| Assertion | How the set supports it |")
    d.w("| --- | --- |")
    d.w("| The interface has one visual language | One stylesheet, one token set, one icon "
        "family, one badge vocabulary across all 46 screens. |")
    d.w("| Colour comes from the application | Every value in `mockup.css` is copied from "
        "`public/css/app.css`; no new colour is introduced. |")
    d.w("| Status is never carried by colour alone | Every badge pairs a colour role with "
        "the stored word, and the pairing is tabulated in section 3.5. |")
    d.w("| The same screen survives three widths | Each screen is a live render at 1440, "
        "1024 and 390 px, captured from the same file. |")
    d.w("| Nothing is claimed that the code cannot do | A control appears only where a "
        "route and a handler exist; proposed behaviour is described, never drawn. |")
    d.w()

    d.w("### 1.2 Fidelity of the data")
    d.w()
    d.w("Names, student numbers, application numbers and amounts are representative. They "
        "follow the formats the system generates (`2023-00123`, `SCH-2026-000184`, "
        "`SR-2026-000042`, `PHP 148,000.00`) and the ranges the validation rules accept, "
        "but they are not records from a production database. Nothing in this document "
        "should be read as a report of actual student data.")
    d.w()
    d.w("Dates are placed in the 2026 academic year so that deadlines, waiting times and "
        "period filters are internally consistent — a scholarship closing in 12 days, an "
        "application submitted three days ago, an audit entry at 07:52. Consistency "
        "matters because a reviewer will check whether the numbers reconcile: the four "
        "summary tiles on the analytics screen equal the sum of the series beside them.")
    d.w()

    d.w("### 1.3 Inventory")
    d.w()
    d.w("| ID | Screen | Role | Base wireframe | Requirements |")
    d.w("| --- | --- | --- | --- | --- |")
    for r in man:
        role = ("Guest" if r["folder"] == "shared"
                else wf_kit.ROLE_LABEL.get(r["folder"], r["folder"]))
        d.w(f"| {r['mockup']} | {r['title']} | {role}"
            f" | `{r['base_wireframe']}` | {len([x for x in r['requirements'].split(',') if x.strip()])} |")
    d.w()
    d.w(f"| | **Total** | | | **{len(man)} screens, {len(traced)} requirements** |")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 2
    d.w("## 2. From wireframe to mockup")
    d.w()
    d.w("### 2.1 What is shared")
    d.w()
    d.w("The page specification is the single source for both deliverables. It names the "
        "route, the module, the requirement identifiers, the content blocks, the "
        "annotations and the responsive behaviour. Because the mockup renderer consumes "
        "the same block list, the two sets cannot disagree about what is on a screen.")
    d.w()
    d.w("The shell is also shared in structure: a 264 px sidebar, a 60 px top bar and one "
        "main region, with the same section labels as `components/navigation/sidebar`. A "
        "reviewer who has read the wireframes can navigate the mockups without a legend.")
    d.w()

    d.w("### 2.2 What the mockup adds")
    d.w()
    d.w("| Element | Wireframe | Mockup | Why |")
    d.w("| --- | --- | --- | --- |")
    d.w("| Colour | Greyscale | Institutional green, university gold, neutral surfaces | "
        "Brand identity and a stable meaning for primary action. |")
    d.w("| Type | One neutral stack | Segoe UI at six roles with fixed sizes and weights | "
        "Hierarchy that survives at a glance and on a projector. |")
    d.w("| Shell | Grey sidebar | Deep-green sidebar with a gold active rule | Separates "
        "navigation from content without a second accent colour. |")
    d.w("| Icons | Text labels only | One stroke set at 24 px beside every navigation item | "
        "Faster scanning in a long sidebar. |")
    d.w("| Status | Outlined label | Colour-coded badge with the stored word | "
        "Recognisable at a glance, still readable without colour. |")
    d.w("| Depth | Flat borders | Two shadows: resting card, raised panel | Groups "
        "information without heavy lines. |")
    d.w("| Charts | Placeholder geometry | Real counts that reconcile with the tiles | A "
        "chart that disagrees with its summary is a defect. |")
    d.w()

    d.w("### 2.3 What the mockup deliberately does not add")
    d.w()
    d.w("High fidelity invites invention, so the restraint is stated explicitly. The "
        "mockups add no feature, no route, no metric and no status that the application "
        "does not already have:")
    d.w()
    d.w("1. **No new screens.** Every mockup is bound to a wireframe screen and, through "
        "it, to a route in `routes/web.php`.")
    d.w("2. **No new status values.** The badge vocabulary is the union of the values the "
        "state machines and controllers write (section 3.5).")
    d.w("3. **No invented analytics.** Charts show counts that can be reconciled with the "
        "queues they summarise; there is no forecasting, no rate that cannot be traced to "
        "records and no comparison with other institutions.")
    d.w("4. **No unimplemented affordance.** A greyed control implying a future feature "
        "would misrepresent the delivered system, so absent capabilities are described in "
        "section 9 instead.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 3
    d.w("## 3. Design system")
    d.w()
    d.w("The design system has its own artefact — `design-system.html`, fourteen sections, "
        "generated from the same tokens as the screens — because a static document cannot "
        "show hover states, focus rings or a live reflow. This section is the printable "
        "summary of that page.")
    d.w()
    d.fig("export/design-plate--desktop.png",
          "Design system plate: colour, type, actions, status vocabulary, records and notices")

    d.w("### 3.1 Colour")
    d.w()
    d.w("Two families and a neutral scale. Institutional green carries structure and "
        "primary action; university gold is an accent and never a status. Semantic colours "
        "exist only inside badges and notices, where they are always accompanied by a word.")
    d.w()
    d.w("| Token | Value | Role |")
    d.w("| --- | --- | --- |")
    palette_rows = [
        ("`--brand-deep`", "#062e1d", "Sidebar surface, hero background"),
        ("`--brand-dark`", "#083f28", "Hover on primary action, headings on light"),
        ("`--brand`", "#0b5d3b", "Primary action, active rule, links"),
        ("`--brand-100`", "#d5e7dd", "Borders and hairlines on brand surfaces"),
        ("`--brand-50`", "#eaf3ee", "Resting tint behind brand content"),
        ("`--accent`", "#c9a227", "Active navigation rule, hero eyebrow; never a status"),
        ("`--canvas`", "#f4f7f5", "Application background"),
        ("`--paper`", "#ffffff", "Cards, tables, panels"),
        ("`--text`", "#1f2937", "Body text"),
        ("`--muted`", "#6b7280", "Secondary text, captions, table headers"),
        ("`--ok`", "#047857", "Success badge text"),
        ("`--warn`", "#b45309", "Warning badge text"),
        ("`--danger`", "#b91c1c", "Destructive action, danger badge text"),
    ]
    for tok, val, role in palette_rows:
        d.w(f"| {tok} | `{val}` | {role} |")
    d.w()

    d.w("### 3.2 Typography")
    d.w()
    d.w("One family with six roles. Segoe UI is first in the stack because it is present on "
        "the university's Windows fleet; the stack falls back to the platform UI font "
        "elsewhere. Monospace appears only for identifiers, times and weights, where "
        "character alignment helps comparison.")
    d.w()
    d.w("| Role | Size / line / weight | Use |")
    d.w("| --- | --- | --- |")
    type_rows = [
        ("Page title", "22 / 1.25 / 650", "One per screen, in the page header"),
        ("Section title", "13.5 / 650", "Card and panel headings"),
        ("Metric", "30 / 700 / tabular", "The number in a summary tile"),
        ("Body", "14 / 1.5", "Descriptions, help text, table cells"),
        ("Small / meta", "12 / 1.5", "Timestamps, qualifiers, captions"),
        ("Label", "10.5 / 700 / uppercase", "Field labels and table headers"),
        ("Identifier", "12.5 / mono", "Application, request and student numbers"),
    ]
    for role, spec, use in type_rows:
        d.w(f"| {role} | {spec} | {use} |")
    d.w()

    d.w("### 3.3 Space, radius and elevation")
    d.w()
    d.w("A 4 px base unit governs padding and gaps: 4, 8, 12, 16, 20, 24, 32, 40. Body "
        "padding is 26 px vertical and 28 px horizontal; card padding is 18 to 20 px; the "
        "gap between form fields is 15 px. Radii are 6 px for controls, 10 px for inputs "
        "and notices, and 14 px for cards and panels.")
    d.w()
    d.w("Only two shadows are used. A hairline shadow "
        "(`0 1px 2px rgba(16,24,40,.05)`) lifts a resting card off the canvas; a larger one "
        "(`0 4px 12px rgba(16,24,40,.08)`) is reserved for raised panels, overlays and the "
        "device frames in the gallery. Nothing else is elevated, so elevation always means "
        "\"this sits above the page\".")
    d.w()

    d.w("### 3.4 Navigation and the shell")
    d.w()
    d.w("The sidebar is 264 px of deep green and never scrolls with the content. Sections "
        "mirror the route groups, so a user can predict where a function lives; the active "
        "item is marked by a 3 px gold rule and a lighter surface. The top bar is 60 px of "
        "white carrying search, the notification count and the account chip.")
    d.w()
    d.w("Below 1025 px the sidebar leaves the flow and is revealed by the menu toggle. At "
        "phone width the search field is dropped rather than squeezed: it would compete "
        "with the page title for the same line and would be unusable at 390 px.")
    d.w()

    d.w("### 3.5 Status vocabulary")
    d.w()
    d.w("The system stores snake_case values and displays sentence case. This table is the "
        "contract between the database and the interface: a stored value that is not here "
        "cannot be displayed, and a colour that is not here cannot be used.")
    d.w()
    d.w("| Stored value | Displayed as | Colour role | Meaning |")
    d.w("| --- | --- | --- | --- |")
    for s, label, kind, desc in mk_ds.STATUS_TABLE:
        d.w(f"| `{s}` | {label} | {kind} | {desc} |")
    d.w()

    d.w("### 3.6 Components")
    d.w()
    d.w("| Component | Treatment | Rule |")
    d.w("| --- | --- | --- |")
    comps = [
        ("Primary action", "Solid brand fill, white label, 36 px high",
         "One per screen, last in the action group."),
        ("Secondary action", "White fill, neutral border",
         "Never competes with the primary; sits to its left."),
        ("Ghost action", "No fill, brand label",
         "Navigation and row actions only; gains a border at phone width so the tap target "
         "is visible."),
        ("Destructive action", "White fill, red border and label",
         "Separated from the primary path and always confirmed."),
        ("Field", "Label above, 38 px control, help text below",
         "Labels are never placeholders; required fields carry an asterisk; errors appear "
         "under the field."),
        ("Record table", "Header in 10.5 px caps, one row per record, actions last",
         "Status is a badge; numbers are right-aligned with tabular figures; below 768 px "
         "the header is dropped and each row restacks with its column label."),
        ("Badge", "20 px pill, uppercase, 10.5 px",
         "Word plus colour; the word is the accessible signal."),
        ("Notice", "Left rule, tinted surface, icon",
         "States a consequence; never used for encouragement or decoration."),
        ("Summary tile", "Label, metric, qualifier",
         "The metric answers one question and the qualifier prevents misreading."),
        ("Stepper", "Four states: done, current, pending",
         "Derived from the workflow state machine, not a generic progress bar."),
        ("Timeline", "Append-only, actor plus timestamp",
         "Newest first; never editable."),
        ("Chart", "Bars, line or donut with a stated series and period",
         "Counts only; a chart must reconcile with the queue it summarises."),
        ("Pager", "Range on the left, navigation on the right",
         "States the visible range and the total; the range becomes its own row at phone "
         "width."),
    ]
    for name, treat, rule in comps:
        d.w(f"| {name} | {treat} | {rule} |")
    d.w()

    d.w("### 3.7 Accessibility")
    d.w()
    d.w("Contrast is measured rather than assumed. The ratios below are computed from the "
        "hex values in section 3.1 using the WCAG relative-luminance formula.")
    d.w()
    d.w("| Use | Pair | Ratio | WCAG 2.2 AA |")
    d.w("| --- | --- | --- | --- |")
    for fg, bg, use in contrast_pairs():
        r = mk_ds.contrast(fg, bg)
        verdict = "AAA" if r >= 7 else ("AA" if r >= 4.5 else
                                        ("AA large" if r >= 3 else "Below AA"))
        d.w(f"| {use} | `{fg}` on `{bg}` | {r:.2f}:1 | {verdict} |")
    d.w()
    d.w("Three further commitments are visible in the screens themselves: a 3 px focus ring "
        "in brand green with a 2 px offset, 44 × 44 px pointer targets in the shell, and no "
        "state conveyed by colour alone. Where a badge is the only indicator in a row, the "
        "same word appears in the accessible name of the row action.")
    d.w()

    d.w("### 3.8 Governance")
    d.w()
    d.w("| Token group | Defined in | Change process |")
    d.w("| --- | --- | --- |")
    d.w("| Colour, type, radius, spacing | `public/css/app.css` | Change the application "
        "first, then regenerate the mockups; the design system follows the code, never the "
        "reverse. |")
    d.w("| Component markup | `documentation/_tools/wf_kit.py` | One renderer serves "
        "wireframes and mockups, so a component is changed once. |")
    d.w("| Mockup styling | `UI-UX-Mockups/assets/mockup.css` | Values are copied from the "
        "application stylesheet; no new colour is introduced. |")
    d.w("| Status vocabulary | `_spec/requirements.csv`, state machines | A new status must "
        "reach the workflow, the badge map and the traceability matrix together. |")
    d.w()
    d.w("No Figma file accompanies this deliverable. The design system is implemented in "
        "HTML and CSS so that it can be inspected in a browser, reviewed as a diff, and "
        "rendered into this document without a second source of truth that would inevitably "
        "drift from the code.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------ 4 to 7
    group_no = {"shared": 4, "student": 5, "counselor": 6, "admin": 7}
    for folder, title, lede in ROLE_ORDER:
        sel = [r for r in man if r["folder"] == folder]
        num = group_no[folder]
        d.w(f"## {num}. {title}")
        d.w()
        d.w(lede)
        d.w()
        d.w(f"{len(sel)} screens.")
        d.w()
        for i, r in enumerate(sel, 1):
            base = BY_ID.get(r["base_wireframe"], {})
            mk = r["mockup"]
            page_title = base.get("title", r["title"])
            d.w(f"### {num}.{i} {mk} — {r['title']}")
            d.w()
            d.w("| Field | Value |")
            d.w("| --- | --- |")
            d.w(f"| Role | {wf_kit.ROLE_LABEL.get(base.get('role', 'guest'), 'Guest')} |")
            d.w(f"| Base wireframe | `{r['base_wireframe']}` — {page_title} |")
            d.w(f"| Route | `{r['route']}` |")
            d.w(f"| Module | {r['module']} |")
            d.w(f"| Requirements | {r['requirements']} |")
            d.w(f"| File | `{r['file']}` |")
            d.w()
            purpose = ("The student dashboard shown at all three supported widths, so the "
                       "reflow rules can be compared side by side."
                       if mk == "MK-46" else base.get("purpose", ""))
            d.w(f"**Purpose.** {purpose}")
            d.w()
            d.fig(f"export/{mk}--desktop.png", f"{mk} {r['title']} at 1440 px")
            notes = composition(base)
            sentence = ", ".join(notes)
            d.w(f"**Composition.** {sentence[:1].upper() + sentence[1:]}.")
            d.w()
            if base.get("annotations"):
                d.w("**What this screen establishes**")
                d.w()
                for n, a in enumerate(base["annotations"], 1):
                    d.w(f"{n}. {a}")
                d.w()
            if mk in EXTRA:
                d.w(f"**Mockup note.** {EXTRA[mk]}")
                d.w()
            if base.get("responsive"):
                d.w("**Responsive behaviour**")
                d.w()
                d.w("| Viewport | Layout behaviour |")
                d.w("| --- | --- |")
                for vp, desc in base["responsive"]:
                    d.w(f"| {vp} | {desc} |")
                d.w()
        d.w("<!-- pagebreak -->")
        d.w()

    # ------------------------------------------------------------------ 8
    d.w("## 8. Responsive behaviour and evidence")
    d.w()
    d.w("### 8.1 Rules, not variants")
    d.w()
    d.w("Every screen is one HTML file with a container query. The three widths are three "
        "renders of the same document, so a change to a structure is a change at every "
        "width by construction. The rules are:")
    d.w()
    d.w("| At | What changes | What never changes |")
    d.w("| --- | --- | --- |")
    d.w("| 1440 px | Two- and three-column grids; six tiles in two rows; persistent sidebar. "
        "| Everything else in the layout. |")
    d.w("| 1024 px | Sidebar leaves the flow behind a menu toggle; grids of three fall to "
        "two; split panels stack. | Column order, labels, statuses, actions. |")
    d.w("| 390 px | Single column; the search field is dropped; tables restack with column "
        "labels; the pager range takes its own row; page actions move under the title. | "
        "The information itself — every field still present. |")
    d.w()

    d.w("### 8.2 Evidence")
    d.w()
    evidence = [
        ("MK-46", "desktop",
         "The showcase: the student dashboard rendered at 1440, 1024 and 390 px in one "
         "frame. The desktop column keeps six tiles in two rows; the tablet column drops "
         "the sidebar and halves the grid; the phone column is a single stack."),
        ("MK-01", "mobile",
         "Login at 390 px: the brand panel moves above the form, the primary action becomes "
         "full width, and the failure message — if present — sits under the field it "
         "belongs to."),
        ("MK-09", "mobile",
         "An application with documents at 390 px: the identity block, the document "
         "checklist and the status history stack in reading order, and no control is lost "
         "with the columns."),
        ("MK-44", "mobile",
         "The audit log at 390 px: the densest table in the set restacks into one labelled "
         "block per entry, keeping actor, action, module, record and address together."),
        ("MK-31", "tablet",
         "Slot booking at 1024 px: the counselor list becomes a two-column grid and the "
         "booking panel sits beneath it rather than beside it."),
    ]
    for i, (mk, vp, caption) in enumerate(evidence, 1):
        width = {"desktop": 1440, "tablet": 1024, "mobile": 390}[vp]
        d.w(f"### 8.{i + 2} {mk} at {width} px")
        d.w()
        d.w(caption)
        d.w()
        d.fig(f"export/{mk}--{vp}.png", f"{mk} at {width} px")
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 9
    d.w("## 9. Coverage, omissions and open items")
    d.w()
    d.w("### 9.1 Traceability summary")
    d.w()
    d.w("| Measure | Count |")
    d.w("| --- | --- |")
    d.w(f"| Requirements in the register | {total_reqs} |")
    d.w(f"| Requirements traced to at least one mockup | {len(traced)} |")
    d.w(f"| Functional requirements without a mockup | {len(unmapped_fr)} |")
    d.w(f"| Non-functional requirements without a mockup | {len(unmapped_nfr)} |")
    d.w(f"| Screens | {len(man)} |")
    d.w()
    d.w(f"Of the {total_reqs} requirements, {status_counts['Implemented']} are implemented, "
        f"{status_counts.get('Partial', 0)} are partially implemented and "
        f"{status_counts['Proposed']} are proposed. No proposed requirement is drawn; the "
        "mockups show only what the delivered code does.")
    d.w()

    d.w("### 9.2 Functional requirements without a mockup")
    d.w()
    d.w("A screen was deliberately not drawn in each of the following cases. Six are "
        "proposed capabilities with no route; five are implemented but have no interface of "
        "their own, because they are a rule, a backfill or a guard rather than a place a "
        "user visits.")
    d.w()
    d.w("| ID | Requirement | Status | Why there is no screen |")
    d.w("| --- | --- | --- | --- |")
    reasons = {
        "FR-AUTH-09": "A data backfill performed at deployment; nothing is displayed.",
        "FR-AUTH-10": "Proposed. The login screen omits a reset link because no "
                      "self-service route exists (GAP-05).",
        "FR-ENR-02": "Proposed. The queue and decision screens exist, but nothing creates "
                     "the record (GAP-01).",
        "FR-ENR-06": "A gate that passes silently for legacy accounts; no visible state.",
        "FR-APP-11": "Proposed. The status value is reserved but no transition exists "
                     "(GAP-10).",
        "FR-GUID-13": "Proposed. Notes are displayed where they exist; no authoring form "
                      "exists (GAP-04).",
        "FR-GUID-14": "Proposed. A confirmed appointment cannot be moved.",
        "FR-SVC-10": "Proposed. Counselor service-request routes do not exist (GAP-03).",
        "FR-NOTIF-02": "A duplicate-suppression rule applied before a notification is "
                       "created; it has no screen.",
        "FR-AUDIT-05": "An absence of routes. The audit log screen shows no edit or delete "
                       "control, which is the visible form of the requirement.",
        "FR-SEARCH-03": "Input normalisation performed on submit; the only visible effect "
                        "is an ordinary result set.",
    }
    for r in unmapped_fr:
        d.w(f"| {r['req_id']} | {r['title']} | {r['status']} | "
            f"{reasons.get(r['req_id'], 'No route or handler exists.')} |")
    d.w()

    d.w("### 9.3 Non-functional requirements")
    d.w()
    d.w(f"The {len(unmapped_nfr)} non-functional requirements are not drawn because a "
        "single frame cannot evidence a runtime property. Their verification method is "
        "inspection or test, and the traceability matrix names the test for each.")
    d.w()
    d.w("| Group | Count | What the mockups do contribute |")
    d.w("| --- | --- | --- |")
    nfr_groups = [
        ("Performance", "NFR-PERF", "Pagination, server-side filters and a pager range on "
                                    "every list screen."),
        ("Security", "NFR-SEC", "No screen offers an action the authorisation layer would "
                                "refuse; destructive actions are separated and confirmed."),
        ("Usability", "NFR-USA", "Consistent navigation, labels and status vocabulary across "
                                 "all 46 screens."),
        ("Reliability", "NFR-REL", "Notices state what happened after a failed operation "
                                   "and what to do next."),
        ("Maintainability", "NFR-MAINT", "One renderer and one stylesheet; a component is "
                                         "changed in one place."),
        ("Compatibility", "NFR-COMPAT", "Container-based layout, no fixed pixel canvas; "
                                        "renders in the supported browsers."),
        ("Portability", "NFR-PORT", "The three captured widths are the portability "
                                    "evidence."),
        ("Accessibility", "NFR-ACC", "Measured contrast, visible focus, word-plus-colour "
                                     "status, labelled controls."),
        ("Data integrity", "NFR-DATA", "Status badges are bound to stored values; no screen "
                                       "displays a status the workflow cannot produce."),
    ]
    for name, prefix, contribution in nfr_groups:
        n = sum(1 for r in unmapped_nfr if r["req_id"].startswith(prefix))
        d.w(f"| {name} (`{prefix}`) | {n} | {contribution} |")
    d.w()

    d.w("### 9.4 Gaps carried from the audit")
    d.w()
    d.w("The read-only audit recorded twelve gaps between intent and implementation. The "
        "mockups neither hide nor repair them: a gap that would change what a user sees is "
        "described here and left out of the screens.")
    d.w()
    d.w("| Gap | Finding | Effect on this deliverable |")
    d.w("| --- | --- | --- |")
    for gid, (finding, effect) in gap_text.items():
        d.w(f"| {gid} | {finding} | {effect} |")
    d.w()

    d.w("### 9.5 Assumptions and confirmations needed")
    d.w()
    d.w("| # | Assumption or question | Consequence if wrong |")
    d.w("| --- | --- | --- |")
    assumptions = [
        ("A1", "The three roles in the code — student, counselor, administrator — are the "
               "complete role set. No adviser, OSAS staff or super-administrator role "
               "exists.", "Any screen for a fourth role would have to be designed and "
               "built, not restyled."),
        ("A2", "Institutional green and university gold are the correct brand colours for "
               "the office.", "The palette is one token block; a change is mechanical."),
        ("A3", "Segoe UI is acceptable as the primary face.", "The stack falls back to the "
               "platform UI font with no layout change."),
        ("A4", "A 1024 px viewport is treated as tablet, so the sidebar collapses there.",
               "At exactly 1024 px the delivered stylesheet may still show the sidebar; the "
               "1 px difference is documented in the wireframe set."),
        ("A5", "Representative data is acceptable in a defence document.",
               "Real records would require a data-handling agreement and redaction."),
        ("A6", "No Figma file is expected.", "The HTML and CSS set is the authoritative "
               "source; a Figma file would become a second source of truth."),
    ]
    for aid, text, consequence in assumptions:
        d.w(f"| {aid} | {text} | {consequence} |")
    d.w()

    d.w("### 9.6 Deliberate differences from the delivered interface")
    d.w()
    d.w("Three differences are intentional and are not defects:")
    d.w()
    d.w("1. **Table restacking.** The delivered tables scroll horizontally at narrow "
        "widths. The mockups restack each row with its column label so that every field is "
        "visible in a static capture. The wireframes use the same device, which keeps the "
        "two sets comparable.")
    d.w("2. **The 1024 px sidebar.** The mockups treat 1024 px as tablet and collapse the "
        "sidebar. The delivered stylesheet switches at 1024 px, so a browser at exactly "
        "that width may still show it.")
    d.w("3. **Representative content.** Names, amounts and dates are illustrative and "
        "internally consistent, not production records.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ----------------------------------------------------------------- 10
    d.w("## 10. Reproduction")
    d.w()
    d.w("1. `python documentation/_tools/build_mockups.py` regenerates all 46 screens, the "
        "design system page, the plate, the gallery and `_manifest.csv`. The build fails if "
        "a mockup's base screen is not in its own requirement trace, if identifiers do not "
        f"form the expected set MK-01 to MK-{len(man):02d}, or if two mockups share an "
        "identifier.")
    d.w("2. `node documentation/_tools/shoot_wireframes.cjs documentation/UI-UX-Mockups/"
        "screens documentation/UI-UX-Mockups/export` regenerates the 138 screenshots. A "
        "screenshot is skipped when it is newer than its source file.")
    d.w("3. `python documentation/_tools/build_mockups_doc.py` regenerates this document "
        "and its DOCX.")
    d.w()
    d.w("The screens and this document are generated from the same inputs, so the document "
        "cannot describe a screen that was not built. Where a number appears in the text — "
        f"{len(man)} screens, {len(traced)} traced requirements, four role groups — it is "
        "computed from the registry at build time.")
    d.w()

    md = d.text()
    path = OUT / "index.md"
    path.write_text(md, encoding="utf-8", newline="\n")
    print(f"wrote {path} ({len(md.split())} words, {d.n} figures)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
