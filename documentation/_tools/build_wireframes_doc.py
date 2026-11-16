#!/usr/bin/env python3
"""
build_wireframes_doc.py - assemble Wireframes/index.md from the same page specs
used to render the HTML, then let md2docx.py produce the DOCX.
"""

from __future__ import annotations

import csv
import pathlib
import sys

HERE = pathlib.Path(__file__).resolve().parent
DOC = HERE.parent
OUT = DOC / "Wireframes"
SPEC = DOC / "_spec" / "requirements.csv"
sys.path.insert(0, str(HERE))

import wf_kit  # noqa: E402
import wf_pages_shared  # noqa: E402
import wf_pages_student  # noqa: E402
import wf_pages_counselor  # noqa: E402
import wf_pages_admin_a  # noqa: E402
import wf_pages_admin_b  # noqa: E402

PAGES = (wf_pages_shared.PAGES + wf_pages_student.PAGES +
         wf_pages_counselor.PAGES + wf_pages_admin_a.PAGES +
         wf_pages_admin_b.PAGES)


def req_map():
    if not SPEC.exists():
        return {}
    out = {}
    with SPEC.open(encoding="utf-8-sig", newline="") as fh:
        for row in csv.DictReader(fh):
            wf = (row.get("wireframe") or "").strip()
            if not wf:
                continue
            for wid in [w.strip() for w in wf.split(";") if w.strip()]:
                out.setdefault(wid, []).append(row["req_id"].strip())
    return out


RMAP = req_map()
for p in PAGES:
    if RMAP.get(p["id"]):
        p["reqs"] = ", ".join(RMAP[p["id"]])


def module_map():
    """module -> screens linked to at least one requirement of that module."""
    if not SPEC.exists():
        return {}
    out = {}
    with SPEC.open(encoding="utf-8-sig", newline="") as fh:
        for row in csv.DictReader(fh):
            mods = [m.strip() for m in (row.get("module") or "").split(",") if m.strip()]
            wfs = [w.strip() for w in (row.get("wireframe") or "").split(";")
                   if w.strip()]
            for m in mods:
                out.setdefault(m, set()).update(wfs)
    return out


MODMAP = module_map()

GROUPS = [
    ("3", "Shared and guest screens", "shared",
     "Screens available before authentication or common to more than one role."),
    ("4", "Student screens", "student",
     "Screens reachable by an account holding the student role."),
    ("5", "Counselor screens", "counselor",
     "Screens reachable by an account holding the counselor role."),
    ("6", "Administrator screens", "admin",
     "Screens reachable by an account holding the admin role."),
]

RESPONSIVE_EVIDENCE = [
    ("WF-G-01", "tablet", "The registration and login layout at 1024 px: the brand panel "
                          "narrows and the form keeps a readable measure."),
    ("WF-G-01", "mobile", "The same screen at 390 px: single column, brand banner above the "
                          "form, full-width primary action."),
    ("WF-S-01", "mobile", "The student dashboard at 390 px: tiles stack, cards become rows, "
                          "and the applications table becomes one card per application."),
    ("WF-S-13", "mobile", "Slot booking at 390 px: two slots per row, with the booking form "
                          "stacked beneath the grid."),
    ("WF-A-12", "mobile", "The review queue at 390 px: the table restacks with each column "
                          "labelled, so no field is lost."),
    ("WF-C-04", "tablet", "Appointment review at 1024 px: the sidebar collapses to a menu "
                          "toggle and the decision panel sits beside the request."),
    ("WF-A-09", "tablet", "Scholarship authoring at 1024 px: the two-column field grid is "
                          "retained inside the reclaimed width."),
]


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


def main() -> int:
    d = Doc()

    d.w("# Low-Fidelity Wireframes")
    d.w()
    d.w("**ISU OSAS Hub — Integrated Student Affairs and Services Management System**")
    d.w()
    d.w("Isabela State University · Office of Student Affairs and Services")
    d.w()
    d.w("| Item | Details |")
    d.w("| --- | --- |")
    d.w("| Deliverable | Final wireframe set |")
    d.w("| Screens | 58 screens across four role groups |")
    d.w("| Format | Standalone HTML, one file per screen |")
    d.w("| Source | `documentation/_tools/wf_pages_*.py` (declarative page specs) |")
    d.w("| Rendered | `documentation/Wireframes/{shared,student,counselor,admin}/` |")
    d.w("| Screenshots | `documentation/Wireframes/export/` at 1440, 1024 and 390 px |")
    d.w("| Basis | Read-only audit of `routes/web.php`, `resources/views/` and "
        "`public/css/app.css` |")
    d.w("| Version | 1.0 |")
    d.w("| Date | 15 November 2026 |")
    d.w()
    d.w("> These are structural specifications, not visual designs. Colour is deliberately "
        "absent so that hierarchy, labelling and control placement can be reviewed without "
        "the distraction of brand styling. Screens are arranged for the three roles that "
        "actually exist in the delivered system: student, counselor and administrator.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()
    d.w("## Contents")
    d.w()
    d.w("<!-- toc -->")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # 1
    d.w("## 1. Purpose and method")
    d.w()
    d.w("A wireframe answers a narrower question than a mockup: what is on the screen, in "
        "what order, and what can the user do next. Every screen in this set corresponds to a "
        "route that exists in the delivered application, and every control corresponds to an "
        "action the code actually performs. Nothing here is aspirational.")
    d.w()
    d.w("The set was generated from declarative page specifications rather than hand-drawn. "
        "Each specification names the route, the module, the requirement identifiers, the "
        "content blocks and the responsive behaviour. A single renderer turns the "
        "specification into an HTML file that shares one shell, one type scale and one "
        "component vocabulary with every other screen. Three consequences follow:")
    d.w()
    d.w("1. **Consistency is structural, not manual.** The sidebar, top bar and content "
        "frame cannot drift between screens because they are emitted from one function.")
    d.w("2. **Traceability is mechanical.** Requirement identifiers are read from the same "
        "registry that produces the traceability matrix, so a screen cannot cite a "
        "requirement that does not exist.")
    d.w("3. **Responsiveness is demonstrated, not asserted.** The frame is a CSS container, "
        "so the desktop, tablet and phone layouts are produced by the same file and can be "
        "inspected by changing the viewport rather than by trusting a description.")
    d.w()

    d.w("### 1.1 Reading a screen")
    d.w()
    d.w("Each screen carries a header strip with its identifier, its title, the role that can "
        "open it, the route name and the requirements it satisfies. Below the frame, an "
        "annotation panel lists the design decisions that a reviewer would otherwise have to "
        "infer, and a table states how the layout reflows at each viewport.")
    d.w()
    d.w("The frame itself uses a fixed 264 px sidebar and a 60 px top bar, mirroring the real "
        "`components/navigation/sidebar` and `topbar` components. Navigation labels are "
        "reproduced verbatim from those components, so a link that appears in the wireframe is "
        "a link that exists.")
    d.w()

    d.w("### 1.2 Fidelity rules")
    d.w()
    d.w("| Rule | Applied as |")
    d.w("| --- | --- |")
    d.w("| No colour | Greyscale only; the darkest fill means primary action or active state. |")
    d.w("| Real labels | Controls are labelled with the words the system uses, never with "
        "placeholder lorem ipsum. |")
    d.w("| Real data shapes | Identifiers such as `SCH-2026-000184` and `SR-2026-000042` "
        "follow the real numbering formats. |")
    d.w("| Real states | Status badges use the vocabulary of the state machines, not invented "
        "labels. |")
    d.w("| No invented features | A control appears only where a route and a handler exist; "
        "proposed features are described in section 8, not drawn. |")
    d.w("| Readable at three widths | Every screen is exported at 1440, 1024 and 390 px. |")
    d.w()

    d.w("### 1.3 Screen inventory")
    d.w()
    d.w("| ID | Screen | Role | Route | Requirements |")
    d.w("| --- | --- | --- | --- | --- |")
    for p in PAGES:
        d.w(f"| {p['id']} | {p['title']} | {wf_kit.ROLE_LABEL.get(p['role'], 'Guest')} | "
            f"`{p.get('route', '—')}` | {p.get('reqs', '—')} |")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # 2
    d.w("## 2. Shared frame and component vocabulary")
    d.w()
    d.w("The application shell is the same on every authenticated screen: a 264 px sidebar "
        "carrying role-appropriate sections, a 60 px top bar carrying navigation, search, "
        "notifications and the account chip, and a single main region. Below 1025 px the "
        "sidebar leaves the flow and the menu toggle reveals it as an overlay; the top bar "
        "drops the search field at phone width.")
    d.w()
    d.w("The blocks below are the only ones used in the set. A reviewer who learns them can "
        "read every screen without a legend.")
    d.w()
    d.w("| Block | Used for | Notable rule |")
    d.w("| --- | --- | --- |")
    d.w("| Page header | Title, one-line purpose, primary actions | One primary action per "
        "screen; secondary actions are outlined. |")
    d.w("| Summary tiles | Counts that answer \"what needs attention\" | Always four or six; "
        "each states the count and its qualifier. |")
    d.w("| Tabs | Mutually exclusive subsets of one collection | Reflect the underlying status "
        "vocabulary, never a marketing grouping. |")
    d.w("| Filter bar | Search plus facet controls | Labels sit above controls; a reset is "
        "always available. |")
    d.w("| Table | Comparable records | One row per record; the last column is reserved for "
        "actions. Below 768 px rows restack with column labels. |")
    d.w("| Pager | Server-side pagination | States the visible range and the total. |")
    d.w("| Form | Creation and editing | Required fields marked; help text under the field; "
        "errors inline. |")
    d.w("| Detail list | Read-only record attributes | Two columns on desktop, stacked on "
        "phones. |")
    d.w("| Timeline | Status history | Append-only, actor and timestamp on every entry. |")
    d.w("| Stepper | Workflow position | Derived from the state machine, not a generic bar. |")
    d.w("| Card list | Browsable collections | One card per record; the whole card is the "
        "target where the card is a link. |")
    d.w("| Slot grid | Bookable times | Taken slots are struck through rather than hidden. |")
    d.w("| Availability grid | Recurring weekly blocks | Filled cells are available; hatched "
        "cells are not. |")
    d.w("| Chart | Aggregate counts | Descriptive only; no forecasting. |")
    d.w("| Notice | Consequences and guards | Information, warning and success variants; "
        "never used for decoration. |")
    d.w("| Empty state | A collection with no records | States why it is empty and what to "
        "do next. |")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # 3-6
    for num, title, folder, lede in GROUPS:
        sel = [p for p in PAGES if p.get("folder") == folder]
        d.w(f"## {num}. {title}")
        d.w()
        d.w(lede)
        d.w()
        d.w(f"{len(sel)} screens." if len(sel) != 1 else "1 screen.")
        d.w()
        for i, p in enumerate(sel, 1):
            d.w(f"### {num}.{i} {p['id']} — {p['title']}")
            d.w()
            d.w("| Field | Value |")
            d.w("| --- | --- |")
            d.w(f"| Role | {wf_kit.ROLE_LABEL.get(p['role'], 'Guest')} |")
            d.w(f"| Route | `{p.get('route', '—')}` |")
            d.w(f"| Module | {p.get('module', '—')} |")
            d.w(f"| Requirements | {p.get('reqs', '—')} |")
            d.w(f"| Source file | `{folder}/{p['id']}.html` |")
            d.w()
            d.w(f"**Purpose.** {p.get('purpose', '')}")
            d.w()
            d.fig(f"export/{p['id']}--desktop.png",
                  f"{p['id']} {p['title']} at 1440 px")
            d.w("**Annotations**")
            d.w()
            for n, a in enumerate(p.get("annotations", []), 1):
                d.w(f"{n}. {a}")
            d.w()
            if p.get("responsive"):
                d.w("**Responsive behaviour**")
                d.w()
                d.w("| Viewport | Layout behaviour |")
                d.w("| --- | --- |")
                for vp, desc in p["responsive"]:
                    d.w(f"| {vp} | {desc} |")
                d.w()
        d.w("<!-- pagebreak -->")
        d.w()

    # 7
    d.w("## 7. Responsive evidence")
    d.w()
    d.w("The annotations above describe the intended reflow. The captures below are the "
        "evidence that the same HTML file produces it, without a second implementation. Each "
        "image is the full page at the stated width.")
    d.w()
    idx = {p["id"]: p for p in PAGES}
    for i, (pid, vp, caption) in enumerate(RESPONSIVE_EVIDENCE, 1):
        title = idx[pid]["title"]
        width = {"desktop": 1440, "tablet": 1024, "mobile": 390}[vp]
        d.w(f"### 7.{i} {pid} at {width} px")
        d.w()
        d.w(caption)
        d.w()
        d.fig(f"export/{pid}--{vp}.png",
              f"{pid} {title} at {width} px")
    d.w("<!-- pagebreak -->")
    d.w()

    # 8
    d.w("## 8. Coverage, omissions and open items")
    d.w()
    d.w("### 8.1 Module coverage")
    d.w()
    d.w("Each row names the screens where that module's work is primarily performed. A "
        "module's requirements can also surface on shared screens — a search result page "
        "carries records from several modules, for example — so this table is the map of "
        "purpose, not the complete trace. The traceability matrix holds the complete "
        "requirement-to-screen links, and the build checks that every screen named here is "
        "linked to at least one requirement of the module it is listed under.")
    d.w()
    d.w("<!-- widths: 10,28,62 -->")
    d.w("| Module | Scope | Screens |")
    d.w("| --- | --- | --- |")
    modules = [
        ("M01", "Authentication and sessions", "WF-G-01, WF-G-02, WF-G-03, WF-A-04"),
        ("M02", "Authorisation and error handling", "WF-G-04"),
        ("M03", "User and student profile management",
         "WF-A-02, WF-A-03, WF-A-05, WF-A-06, WF-A-07, WF-S-02, WF-S-03"),
        ("M04", "Profile passcode protection", "WF-S-04, WF-A-06"),
        ("M05", "Enrollment verification", "WF-C-05, WF-C-06, WF-S-02"),
        ("M06", "Scholarship management",
         "WF-A-08, WF-A-09, WF-A-10, WF-S-05, WF-S-06"),
        ("M07", "Eligibility evaluation", "WF-S-07"),
        ("M08", "Weighted matching and recommendations",
         "WF-A-11, WF-S-08, WF-S-09"),
        ("M09", "Scholarship applications",
         "WF-S-10, WF-S-11, WF-A-12, WF-A-13"),
        ("M10", "Document upload and verification",
         "WF-S-11, WF-A-13"),
        ("M11", "Guidance appointments",
         "WF-S-12, WF-S-13, WF-S-15, WF-C-02, WF-C-04, WF-A-14, WF-A-15, WF-A-16, "
         "WF-A-17"),
        ("M12", "Student service requests",
         "WF-S-16, WF-S-17, WF-S-18, WF-S-19, WF-C-01, WF-A-18, WF-A-19, WF-A-20, "
         "WF-A-21"),
        ("M13", "Notifications", "WF-G-05"),
        ("M14", "Analytics and reporting",
         "WF-A-01, WF-A-24, WF-A-25, WF-A-26, WF-A-27"),
        ("M15", "Audit logging", "WF-A-22, WF-A-23"),
        ("M16", "Search and application shell", "WF-G-06, WF-S-05"),
    ]
    for code, scope, screens in modules:
        listed = [s.strip() for s in screens.split(",") if s.strip()]
        unknown = [s for s in listed if s not in MODMAP.get(code, set())]
        if unknown:
            raise SystemExit(
                f"module {code}: {', '.join(unknown)} is not linked to a {code} requirement")
        d.w(f"| {code} | {scope} | {screens} |")
    d.w()
    d.w("Two further screens belong to the guidance module but are cited by no requirement: "
        "`WF-S-14` (a student's own appointments) and `WF-C-03` (the counselor's appointment "
        "queue). They are drawn because their routes exist and because the detail screens "
        "they lead to cannot be reached otherwise. Section 5.1 of the traceability matrix "
        "records the missing citation as an open item rather than a defect in the screen.")
    d.w()
    d.w("### 8.2 Features with no screen, and why")
    d.w()
    d.w("A wireframe was **not** drawn for the following capabilities because the delivered "
        "application has no route or handler for them. Drawing a screen would imply a "
        "capability that a reviewer could not then find in the code.")
    d.w()
    d.w("| Missing capability | Evidence | Where it is recorded |")
    d.w("| --- | --- | --- |")
    d.w("| Self-service password reset | No reset-request or reset-password route; only an "
        "administrator-initiated reset exists | GAP-05, FR-AUTH-10, WF-A-04 |")
    d.w("| Creating an enrollment verification record | No route writes the table; a queue "
        "and a decision screen exist but no intake | GAP-01, SM-05, WF-C-05 |")
    d.w("| Counselor processing of service requests | Service-request routes are "
        "administrator-only | GAP-03, UC-28 |")
    d.w("| Administrator confirming or completing appointments | Oversight is read-only | "
        "GAP-08, WF-A-16 |")
    d.w("| Adviser endorsement | No adviser role, gate or transition | CF-01 in the SRS |")
    d.w("| Super administrator tier | One flat administrator role, no hierarchy | CF-03 in "
        "the SRS |")
    d.w()
    d.w("### 8.3 Where the wireframes and the code intentionally differ")
    d.w()
    d.w("Two differences are deliberate and are not defects:")
    d.w()
    d.w("1. **The sidebar breakpoint is drawn at 1024 px inclusive.** The application's "
        "stylesheet switches at 1024 px, so a 1024 px browser may still show the sidebar "
        "depending on the browser's box model. The wireframe treats 1024 px as tablet and "
        "collapses the sidebar, which is the intended reading of the tablet capture.")
    d.w("2. **Table restacking is a wireframe device.** The delivered tables scroll "
        "horizontally on narrow screens. The wireframe restacks rows with column labels so "
        "that every field remains visible in a static review document. The high-fidelity "
        "mockups reuse the same device, so the two design sets stay comparable; both are "
        "drawn at 1024 px and 390 px from the same page specification.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # 9
    d.w("## 9. Reproduction")
    d.w()
    d.w("1. `python documentation/_tools/build_wireframes.py` regenerates all 58 HTML "
        "files, the index page and `_manifest.csv`.")
    d.w("2. `node documentation/_tools/shoot_wireframes.cjs documentation/Wireframes "
        "documentation/Wireframes/export` regenerates every screenshot at 1440, 1024 and "
        "390 px.")
    d.w("3. `python documentation/_tools/build_wireframes_doc.py` regenerates this document "
        "and its DOCX.")
    d.w()
    d.w("The build fails loudly if a page identifier is duplicated or if the count for any "
        "role group differs from the registry, so a dropped screen cannot pass silently.")
    d.w()

    md = d.text()
    path = OUT / "index.md"
    path.write_text(md, encoding="utf-8", newline="\n")
    print(f"wrote {path} ({len(md.split())} words)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
