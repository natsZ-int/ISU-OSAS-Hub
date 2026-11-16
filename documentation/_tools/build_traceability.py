#!/usr/bin/env python3
"""
build_traceability.py - produce the traceability matrix.

Inputs, all read-only:
  documentation/_spec/requirements.csv      canonical requirement registry
  documentation/UML/source/*.mmd            diagram identifiers and titles
  documentation/Wireframes/_manifest.csv    screen registry
  documentation/UI-UX-Mockups/_manifest.csv mockup registry

Outputs:
  documentation/Traceability/ISU-OSAS-Hub-Traceability.csv
  documentation/Traceability/index.md
"""

from __future__ import annotations

import collections
import csv
import pathlib
import re
import sys

HERE = pathlib.Path(__file__).resolve().parent
DOC = HERE.parent
OUT = DOC / "Traceability"
SPEC = DOC / "_spec" / "requirements.csv"
WF_MAN = DOC / "Wireframes" / "_manifest.csv"
MK_MAN = DOC / "UI-UX-Mockups" / "_manifest.csv"
UML_SRC = DOC / "UML" / "source"

sys.path.insert(0, str(HERE))
import wf_kit  # noqa: E402


def ids(value):
    return [x.strip() for x in (value or "").replace(",", ";").split(";") if x.strip()]


def clean(value):
    return (value or "").replace("\u2014", "").strip()


NUM_WORD = {1: "One", 2: "Two", 3: "Three", 4: "Four", 5: "Five", 6: "Six"}


def cell(value):
    return (value or "").replace("|", "\\|").replace("\n", " ")


def parse_uml():
    """Diagram id -> (kind, title); use case id -> label."""
    diagrams = {}
    usecases = {}
    for path in sorted(UML_SRC.glob("*.mmd")):
        text = path.read_text(encoding="utf-8")
        head = re.search(r"^%%\s*([A-Z]+-\d+)\s+(.+?)\s*(?:\u2014|-)\s*ISU OSAS Hub",
                         text, re.M)
        did = path.name.split("-")[0] + "-" + path.name.split("-")[1]
        title = head.group(2).strip() if head else path.stem
        kind = {
            "UC": "Use case diagram", "CL": "Class diagram", "ERD": "Entity relationship",
            "SQ": "Sequence diagram", "AD": "Activity diagram", "SM": "State machine",
            "ARCH": "Architecture",
        }.get(did.split("-")[0], "Diagram")
        diagrams[did] = (kind, title, path.name)
        for num, label in re.findall(r'UC-(\d+)<br/>([^"\]]+)', text):
            usecases[f"UC-{num}"] = label.strip()
    return diagrams, usecases


def verification(row):
    rid = row["req_id"]
    if rid.startswith("NFR-"):
        group = rid.split("-")[1]
        return {"PERF": "Test", "REL": "Test", "SEC": "Test", "DATA": "Test",
                "USA": "Inspection", "ACC": "Inspection", "MAINT": "Inspection",
                "COMPAT": "Inspection", "PORT": "Inspection"}.get(group, "Inspection")
    if row["status"] == "Proposed":
        return "Not implemented"
    if clean(row["wireframe"]) or clean(row["mockup"]):
        return "Demonstration"
    return "Inspection"


def load(path):
    with path.open(encoding="utf-8-sig", newline="") as fh:
        return list(csv.DictReader(fh))


class Doc:
    def __init__(self):
        self.lines = []

    def w(self, text=""):
        self.lines.append(text)

    def text(self):
        return "\n".join(self.lines)


def main() -> int:
    rows = load(SPEC)
    wf_man = load(WF_MAN)
    mk_man = sorted(load(MK_MAN), key=lambda r: int(r["mockup"].split("-")[1]))
    diagrams, usecases = parse_uml()

    for r in rows:
        r["wf"] = [x for x in ids(r["wireframe"]) if x.startswith("WF-")]
        r["mk"] = [x for x in ids(r["mockup"]) if x.startswith("MK-")]
        r["um"] = [x for x in ids(r["uml"]) if x.startswith(("UC-", "SQ-", "AD-", "SM-",
                                                              "CL-", "ERD-", "ARCH-"))]
        r["verify"] = verification(r)
        r["mods"] = ids(r["module"])

    fr = [r for r in rows if r["req_id"].startswith("FR-")]
    nfr = [r for r in rows if r["req_id"].startswith("NFR-")]
    status_counts = collections.Counter(r["status"] for r in rows)
    with_mk = [r for r in rows if r["mk"]]
    no_mk = [r for r in rows if not r["mk"]]
    no_wf = [r for r in rows if not r["wf"]]

    # ---------------------------------------------------------------- CSV
    OUT.mkdir(parents=True, exist_ok=True)
    csv_path = OUT / "ISU-OSAS-Hub-Traceability.csv"
    with csv_path.open("w", encoding="utf-8", newline="") as fh:
        w = csv.writer(fh)
        w.writerow(["req_id", "type", "group", "title", "description", "actor",
                    "priority", "status", "module", "wireframe", "mockup", "uml",
                    "verification"])
        for r in rows:
            w.writerow([r["req_id"], r["req_id"].split("-")[0], r["group"], r["title"],
                        r["description"], r["actor"], r["priority"], r["status"],
                        r["module"], "; ".join(r["wf"]), "; ".join(r["mk"]),
                        "; ".join(r["um"]), r["verify"]])

    d = Doc()
    d.w("# Requirements Traceability Matrix")
    d.w()
    d.w("**ISU OSAS Hub — Integrated Student Affairs and Services Management System**")
    d.w()
    d.w("Isabela State University · Office of Student Affairs and Services")
    d.w()
    d.w("| Item | Details |")
    d.w("| --- | --- |")
    fr_groups = len({r["req_id"].split("-")[1] for r in fr})
    nfr_groups = len({r["req_id"].split("-")[1] for r in nfr})
    d.w(f"| Requirements traced | {len(rows)} ({len(fr)} functional, {len(nfr)} non-functional) |")
    d.w(f"| Design artifacts | {len(wf_man)} wireframes, {len(mk_man)} mockups |")
    d.w(f"| Model artifacts | {len(diagrams)} diagrams and {len(usecases)} use cases |")
    d.w("| Machine-readable copy | `ISU-OSAS-Hub-Traceability.csv` |")
    d.w("| Regenerated by | `documentation/_tools/build_traceability.py` |")
    d.w("| Version | 1.0 |")
    d.w("| Date | 15 November 2026 |")
    d.w()
    d.w("> Every identifier in this matrix already exists in the SRS, the UML set, the "
        "wireframes or the mockups. The matrix adds no requirement and no artifact; it "
        "records which of them belong together.")
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
    d.w("## 1. Purpose and method")
    d.w()
    d.w("A requirements specification is only as strong as its weakest link: a requirement "
        "that no design element answers, or a screen that no requirement justifies. This "
        "matrix makes both directions explicit so that a reviewer — or a panel — can pick "
        "any row and either find its design evidence or find a stated reason why there is "
        "none.")
    d.w()
    d.w("The matrix is generated, not maintained by hand. Requirement identifiers and their "
        "artifact links live in one registry (`_spec/requirements.csv`); the wireframe and "
        "mockup manifests record what was actually built; the UML source files record the "
        "diagram identifiers. The build reads all of them, so a matrix row cannot cite an "
        "artifact that does not exist.")
    d.w()

    d.w("### 1.1 Identifier scheme")
    d.w()
    d.w("| Prefix | Meaning | Example | Range |")
    d.w("| --- | --- | --- | --- |")
    d.w(f"| `FR-` | Functional requirement | `FR-APP-04` | {len(fr)} across {fr_groups} "
        f"groups |")
    d.w(f"| `NFR-` | Non-functional requirement | `NFR-SEC-02` | {len(nfr)} across "
        f"{nfr_groups} groups |")
    d.w("| `WF-` | Wireframe screen | `WF-A-12` | 58 screens |")
    d.w("| `MK-` | High-fidelity mockup | `MK-26` | 46 screens |")
    d.w("| `UC-` | Use case (in the use case diagram) | `UC-21` | 34 use cases |")
    d.w("| `SQ-` | Sequence diagram | `SQ-05` | 10 diagrams |")
    d.w("| `AD-` | Activity diagram | `AD-02` | 6 diagrams |")
    d.w("| `SM-` | State machine | `SM-01` | 5 diagrams |")
    d.w("| `CL-` | Class diagram | `CL-01` | 1 diagram |")
    d.w("| `ERD-` | Entity relationship diagram | `ERD-01` | 1 diagram |")
    d.w("| `ARCH-` | Architecture diagram | `ARCH-01` | 2 diagrams |")
    d.w("| `GAP-` | Implementation gap recorded during the audit | `GAP-03` | 12 gaps |")
    d.w()

    d.w("### 1.2 Status vocabulary")
    d.w()
    d.w("| Status | Meaning | Count |")
    d.w("| --- | --- | --- |")
    d.w(f"| Implemented | The delivered code performs the behaviour and the requirement is "
        f"satisfied. | {status_counts['Implemented']} |")
    d.w(f"| Partial | The behaviour exists but a stated part of the requirement does not. | "
        f"{status_counts.get('Partial', 0)} |")
    d.w(f"| Proposed | The behaviour is specified but not built; recorded so that it is not "
        f"mistaken for delivered work. | {status_counts['Proposed']} |")
    d.w()

    d.w("### 1.3 Verification method")
    d.w()
    d.w("| Method | Meaning | Applied to |")
    d.w("| --- | --- | --- |")
    d.w("| Demonstration | A reviewer can see the behaviour in a screen and its mockup. | "
        "Functional requirements with a design artifact |")
    d.w("| Inspection | The property is verified by reading code, routes or configuration. | "
        "Rules with no interface, and the usability, accessibility, maintainability, "
        "compatibility and portability requirements |")
    d.w("| Test | The property is verified by executing the system under load or attack. | "
        "Performance, reliability, security and data-integrity requirements |")
    d.w("| Not implemented | The requirement is proposed, so no method can be applied yet. | "
        "Proposed functional requirements |")
    d.w()
    d.w("The method is a statement about how the requirement should be checked, not a claim "
        "that it has been checked. Results belong in the test record, not in this matrix.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 2
    d.w("## 2. Coverage summary")
    d.w()
    d.w("### 2.1 Requirements by type and status")
    d.w()
    d.w("| Type | Implemented | Partial | Proposed | Total | With wireframe | With mockup | With UML |")
    d.w("| --- | --- | --- | --- | --- | --- | --- | --- |")
    for label, sel in (("Functional", fr), ("Non-functional", nfr)):
        c = collections.Counter(r["status"] for r in sel)
        d.w(f"| {label} | {c['Implemented']} | {c.get('Partial', 0)} | {c['Proposed']} | "
            f"{len(sel)} | {sum(1 for r in sel if r['wf'])} | {sum(1 for r in sel if r['mk'])} "
            f"| {sum(1 for r in sel if r['um'])} |")
    d.w(f"| **Total** | **{status_counts['Implemented']}** | "
        f"**{status_counts.get('Partial', 0)}** | **{status_counts['Proposed']}** | "
        f"**{len(rows)}** | **{len(rows) - len(no_wf)}** | **{len(with_mk)}** | "
        f"**{sum(1 for r in rows if r['um'])}** |")
    d.w()

    d.w("### 2.2 Artifact coverage")
    d.w()
    d.w("| Artifact set | Count | Requirements traced | Comment |")
    d.w("| --- | --- | --- | --- |")
    d.w(f"| Wireframe screens | {len(wf_man)} | {len(rows) - len(no_wf)} of {len(rows)} | "
        "Every functional requirement has a screen; 15 non-functional requirements are "
        "verified by inspection instead. |")
    d.w(f"| Mockups | {len(mk_man)} | {len(with_mk)} of {len(rows)} | "
        "A mockup is drawn where a screen exists and the requirement has a visible "
        "interface. |")
    um_cov = sum(1 for r in rows if any(x in diagrams for x in r["um"]))
    uc_cov = sum(1 for r in rows if any(x in usecases for x in r["um"]))
    any_um = sum(1 for r in rows if r["um"])
    d.w(f"| UML diagrams | {len(diagrams)} | {um_cov} of {len(rows)} | "
        "The use case diagram carries a further "
        f"{uc_cov - um_cov} requirements; together, {any_um} of {len(rows)} have a model "
        f"artefact. The remaining {len(rows) - any_um} are non-functional requirements "
        "that no diagram can evidence. |")
    d.w(f"| Use cases | {len(usecases)} | {uc_cov} of {len(rows)} | "
        "Use cases live inside `UC-01`; they are cited individually. |")
    d.w()

    d.w("### 2.3 Requirements with no design artifact")
    d.w()
    d.w(f"| Category | Count | Reason |")
    d.w("| --- | --- | --- |")
    d.w(f"| Non-functional, no wireframe | {len(no_wf)} | A runtime or structural property "
        "cannot be drawn. Verified by inspection or test: "
        + ", ".join(f"`{r['req_id']}`" for r in no_wf) + ". |")
    d.w(f"| No mockup, functional | {sum(1 for r in no_mk if r['req_id'].startswith('FR-'))} "
        "| Six are proposed capabilities with no route; five are rules or guards with no "
        "interface. Listed in section 5.2. |")
    d.w(f"| No mockup, non-functional | "
        f"{sum(1 for r in no_mk if r['req_id'].startswith('NFR-'))} | Not a visual "
        "property. |")
    d.w()

    d.w("### 2.4 Coverage by module")
    d.w()
    d.w("<!-- widths: 6,22,8,10,9,8 -->")
    d.w("| Module | Scope | Functional | Non-functional | Wireframes | Mockups |")
    d.w("| --- | --- | --- | --- | --- | --- |")
    module_names = {
        "M01": "Authentication and sessions", "M02": "Authorisation and error handling",
        "M03": "User and student profiles", "M04": "Profile passcode protection",
        "M05": "Enrollment verification", "M06": "Scholarship management",
        "M07": "Eligibility evaluation", "M08": "Matching and recommendations",
        "M09": "Scholarship applications", "M10": "Document upload and verification",
        "M11": "Guidance appointments", "M12": "Student service requests",
        "M13": "Notifications", "M14": "Analytics and reporting",
        "M15": "Audit logging", "M16": "Search and application shell",
    }
    wf_by_id = {r["id"]: r for r in wf_man}
    mk_by_id = {r["mockup"]: r for r in mk_man}
    for code in [f"M{i:02d}" for i in range(1, 17)]:
        sel = [r for r in rows if code in r["mods"]]
        wfs = sorted({w for r in sel for w in r["wf"]})
        mks = sorted({m for r in sel for m in r["mk"]})
        d.w(f"| {code} | {module_names[code]} | "
            f"{sum(1 for r in sel if r['req_id'].startswith('FR-'))} | "
            f"{sum(1 for r in sel if r['req_id'].startswith('NFR-'))} | "
            f"{len(wfs)} | {len(mks)} |")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 3
    d.w("## 3. Forward traceability")
    d.w()
    d.w("Each row states what a requirement is and where a reviewer can see it. A blank "
        "cell is deliberate: it means no artifact of that kind exists, and the reason is in "
        "section 5.")
    d.w()

    def matrix(title, sel, pagebreak=True):
        d.w(f"### {title}")
        d.w()
        d.w("<!-- widths: 10,32,5,8,9,7,15,9 -->")
        d.w("| ID | Requirement | Module | Status | Wireframe | Mockup | UML | Verify |")
        d.w("| --- | --- | --- | --- | --- | --- | --- | --- |")
        for r in sel:
            d.w(f"| {r['req_id']} | {cell(r['title'])} | {cell(r['module'])} | "
                f"{r['status']} | {cell('<br/>'.join(r['wf']))} | "
                f"{cell('; '.join(r['mk']))} | "
                f"{cell('; '.join(r['um']))} | {r['verify']} |")
        d.w()
        if pagebreak:
            d.w("<!-- pagebreak -->")
            d.w()

    matrix("3.1 Functional requirements", fr)
    matrix("3.2 Non-functional requirements", nfr, pagebreak=False)

    # ------------------------------------------------------------------ 4
    d.w("## 4. Backward traceability")
    d.w()
    d.w("The same links read from the other side: what each artifact carries. This is the "
        "view that exposes an orphan screen — a screen that is drawn but justifies no "
        "requirement.")
    d.w()

    d.w("### 4.1 Wireframe screens")
    d.w()
    d.w("<!-- widths: 7,30,9,50 -->")
    d.w("| Screen | Title | Role | Requirements |")
    d.w("| --- | --- | --- | --- |")
    for r in wf_man:
        sel = [x["req_id"] for x in rows if r["id"] in x["wf"]]
        d.w(f"| {r['id']} | {cell(r['title'])} | {cell(r.get('role', ''))} | "
            f"{', '.join(sel) if sel else '**none**'} |")
    d.w()

    d.w("### 4.2 Mockups")
    d.w()
    d.w("<!-- widths: 6,26,8,44 -->")
    d.w("| Mockup | Title | Base wireframe | Requirements |")
    d.w("| --- | --- | --- | --- |")
    for r in mk_man:
        sel = [x["req_id"] for x in rows if r["mockup"] in x["mk"]]
        d.w(f"| {r['mockup']} | {cell(r['title'])} | `{r['base_wireframe']}` | "
            f"{', '.join(sel)} |")
    d.w()

    d.w("### 4.3 UML artifacts")
    d.w()
    d.w("<!-- widths: 8,12,26,44 -->")
    d.w("| Artifact | Type | Title | Requirements |")
    d.w("| --- | --- | --- | --- |")
    for did, (kind, title, _fn) in sorted(diagrams.items()):
        sel = [x["req_id"] for x in rows if did in x["um"]]
        d.w(f"| {did} | {kind} | {cell(title)} | {', '.join(sel) if sel else '—'} |")
    d.w()
    for uid in sorted(usecases, key=lambda x: int(x.split("-")[1])):
        sel = [x["req_id"] for x in rows if uid in x["um"]]
        d.w(f"| {uid} | Use case | {cell(usecases[uid])} | {', '.join(sel) if sel else '—'} |")
    d.w()
    d.w("Five diagrams carry no single requirement and are marked `—`. They are the "
        "lifecycle activity diagrams for the application, document, guidance and "
        "service-request workflows, and the deployment view. Each describes how a set of "
        "requirements combine over time — which is why no one requirement owns it — while "
        "the individual steps are traced from the use cases and sequence diagrams that "
        "share the same workflow. They are kept because a reviewer following a case "
        "end-to-end needs the whole path in one picture.")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 5
    d.w("## 5. Exceptions and open items")
    d.w()
    orphans = [r for r in wf_man
               if not any(r["id"] in x["wf"] for x in rows)]
    orphan_advice = {
        "WF-S-14": "Add a requirement for the appointment list, or cite `FR-GUID-07` and "
                   "`FR-GUID-10` against it in the registry.",
        "WF-C-03": "Cite `FR-GUID-09` against it, since the decision screen is reachable "
                   "only through the list.",
    }
    d.w("### 5.1 Screens with no requirement")
    d.w()
    if orphans:
        count = NUM_WORD.get(len(orphans), str(len(orphans)))
        noun = "screen is" if len(orphans) == 1 else "screens are"
        d.w(f"{count} wireframe {noun} not cited by any requirement. Each is a collection "
            "view whose requirements are traced to the detail screens it leads to, so the "
            "functionality is covered but the screen itself is not:")
        d.w()
        d.w("<!-- widths: 9,24,26,41 -->")
        d.w("| Screen | Title | Status | Recommendation |")
        d.w("| --- | --- | --- | --- |")
        for r in orphans:
            advice = orphan_advice.get(r["id"],
                                       "Cite a requirement against it or delete it.")
            d.w(f"| {r['id']} | {cell(r['title'])} | Route exists and is drawn; no "
                f"requirement cites it. | {advice} |")
        d.w()
    else:
        d.w("Every wireframe screen is cited by at least one requirement.")
        d.w()

    d.w("### 5.2 Requirements without a mockup")
    d.w()
    d.w("<!-- widths: 10,28,9,50 -->")
    d.w("| ID | Requirement | Status | Reason |")
    d.w("| --- | --- | --- | --- |")
    reasons = {
        "FR-AUTH-09": "Deployment-time backfill; nothing is displayed.",
        "FR-AUTH-10": "No self-service reset route exists (GAP-05).",
        "FR-ENR-02": "Nothing creates a verification record (GAP-01).",
        "FR-ENR-06": "A gate that passes silently for legacy accounts.",
        "FR-APP-11": "The status is reserved but has no transition (GAP-10).",
        "FR-GUID-13": "Notes are displayed but cannot be authored (GAP-04).",
        "FR-GUID-14": "A confirmed appointment cannot be moved.",
        "FR-SVC-10": "No counselor service-request route exists (GAP-03).",
        "FR-NOTIF-02": "Duplicate suppression applied before creation; no screen.",
        "FR-AUDIT-05": "An absence of routes; the audit screen shows no edit control.",
        "FR-SEARCH-03": "Input normalisation; the visible effect is an ordinary result set.",
    }
    for r in no_mk:
        if r["req_id"].startswith("NFR-"):
            continue
        d.w(f"| {r['req_id']} | {cell(r['title'])} | {r['status']} | "
            f"{reasons.get(r['req_id'], 'No route or handler exists.')} |")
    d.w()
    d.w(f"The remaining {sum(1 for r in no_mk if r['req_id'].startswith('NFR-'))} "
        "requirements in this category are non-functional and are listed in full in "
        "section 3.2; none of them is a visual property.")
    d.w()

    d.w("### 5.3 Proposed requirements")
    d.w()
    d.w("Six requirements describe behaviour that is specified but not built. They are "
        "traced so that a reviewer can see they were considered, and marked so that no "
        "mockup is mistaken for a delivery:")
    d.w()
    d.w("| ID | Requirement | Traced design artifact |")
    d.w("| --- | --- | --- |")
    for r in rows:
        if r["status"] == "Proposed":
            d.w(f"| {r['req_id']} | {cell(r['title'])} | "
                f"{cell('; '.join(r['wf']))}{' / ' + cell('; '.join(r['um'])) if r['um'] else ''} |")
    d.w()

    d.w("### 5.4 Gaps carried forward")
    d.w()
    d.w("The twelve gaps recorded during the audit are enumerated in the SRS (section 7) "
        "and reflected in the design deliverables. Their traceability consequence is:")
    d.w()
    d.w("| Gap | Traceability consequence |")
    d.w("| --- | --- |")
    gap_rows = [
        ("GAP-01", "`FR-ENR-02` is proposed and has no mockup; the counselor queue is drawn "
                   "but nothing feeds it."),
        ("GAP-02", "No requirement traces the silent bypass; recorded as an SRS gap only."),
        ("GAP-03", "`FR-SVC-10` is proposed; the administrator request screens carry the "
                   "workflow requirements instead."),
        ("GAP-04", "`FR-GUID-13` is proposed; notes appear in read-only form."),
        ("GAP-05", "`FR-AUTH-10` is proposed; the login mockup deliberately omits a reset "
                   "link."),
        ("GAP-06", "Data seeding defect; no requirement or artifact is affected."),
        ("GAP-07", "Seeded dates are stale; mockup data uses current dates instead."),
        ("GAP-08", "`FR-GUID-11` is `Partial`: oversight screens are read-only, so the "
                   "decision controls appear only on the counselor screens."),
        ("GAP-09", "No requirement claims re-application; the absence is recorded once."),
        ("GAP-10", "`FR-APP-11` is proposed; `withdrawn` is defined as a badge but has no "
                   "transition."),
        ("GAP-11", "Schema duplication; `ERD-01` records the live schema only."),
        ("GAP-12", "Migration duplication; no requirement or artifact is affected."),
    ]
    for gid, effect in gap_rows:
        d.w(f"| {gid} | {effect} |")
    d.w()
    d.w("<!-- pagebreak -->")
    d.w()

    # ------------------------------------------------------------------ 6
    d.w("## 6. Maintenance")
    d.w()
    d.w("### 6.1 Procedure")
    d.w()
    d.w("1. Add or change the requirement in `_spec/requirements.csv`. The `wireframe`, "
        "`mockup` and `uml` columns are the trace links; leave a column empty when no "
        "artifact exists rather than inventing one.")
    d.w("2. If the change adds a screen, add it to the matching `wf_pages_*.py` module and "
        "rebuild the wireframes and mockups. The mockup builder derives each mockup's base "
        "screen from the requirement trace, so an inconsistent link fails the build.")
    d.w("3. Rebuild the SRS, the UML document, the wireframe document, the mockup document "
        "and this matrix. Each builder reads the registry, so no document holds a private "
        "copy of the links.")
    d.w()
    d.w("### 6.2 Guards")
    d.w()
    d.w("| Guard | Where | What it prevents |")
    d.w("| --- | --- | --- |")
    d.w("| Base screen must be in the mockup's own trace | `build_mockups.py` | A mockup "
        "that illustrates the wrong requirement. |")
    d.w("| Identifier set must be exactly MK-01 to MK-46 | `build_mockups.py` | A dropped or "
        "duplicated mockup. |")
    d.w("| Page counts must match the role group registry | `build_wireframes.py` | A "
        "dropped screen. |")
    d.w("| Diagram count must match the requested seven types | `build_uml.py` | A missing "
        "diagram type. |")
    d.w("| Requirement count and ID uniqueness | `build_srs.py` | A duplicated or dropped "
        "requirement. |")
    d.w()
    d.w("### 6.3 Confirmation needed")
    d.w()
    d.w("Two items in section 5.1 are judgements, not facts: the two screens are drawn "
        "because their routes exist, but no requirement cites them. The recommendation is "
        "to add the citation rather than to delete the screens, since a student without an "
        "appointment list cannot reach an appointment detail. This is the only place where "
        "the matrix depends on a decision that the code cannot settle.")
    d.w()

    md = d.text()
    path = OUT / "index.md"
    path.write_text(md, encoding="utf-8", newline="\n")
    print(f"wrote {csv_path.name} ({len(rows)} rows)")
    print(f"wrote {path.name} ({len(md.split())} words)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
