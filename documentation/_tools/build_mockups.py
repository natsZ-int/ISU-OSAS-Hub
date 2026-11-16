#!/usr/bin/env python3
"""
build_mockups.py - emit the 46 high-fidelity mockup screens, the design system
page and the gallery index.

Mockup identifiers and their requirement links are read from the canonical
registry (_spec/requirements.csv); the structural source of each mockup is the
wireframe screen that carries the most requirements for that mockup. That rule
is checked at build time: a mockup whose base screen does not appear in its own
requirement trace is reported and fails the build.
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
import mk_render  # noqa: E402
import wf_pages_shared  # noqa: E402
import wf_pages_student  # noqa: E402
import wf_pages_counselor  # noqa: E402
import wf_pages_admin_a  # noqa: E402
import wf_pages_admin_b  # noqa: E402

PAGES = (wf_pages_shared.PAGES + wf_pages_student.PAGES +
         wf_pages_counselor.PAGES + wf_pages_admin_a.PAGES +
         wf_pages_admin_b.PAGES)
BY_ID = {p["id"]: p for p in PAGES}

# MK-46 is the responsive showcase, built on the student dashboard.
SHOWCASE = {"MK-46": "WF-S-01"}
FOLDERS = ("shared", "student", "counselor", "admin")


def load_rows():
    with SPEC.open(encoding="utf-8-sig", newline="") as fh:
        return list(csv.DictReader(fh))


def mockup_registry(rows):
    """MK id -> ordered requirement rows."""
    reg = collections.OrderedDict()
    for row in rows:
        for mk in [x.strip() for x in (row.get("mockup") or "").split(";") if x.strip()]:
            reg.setdefault(mk, []).append(row)
    return reg


def base_screen(mk, rows):
    if mk in SHOWCASE:
        return SHOWCASE[mk]
    votes = collections.Counter()
    for row in rows:
        for wf in [x.strip() for x in (row.get("wireframe") or "").split(";") if x.strip()]:
            votes[wf] += 1
    if not votes:
        raise SystemExit(f"{mk}: no wireframe trace, cannot choose a base screen")
    return votes.most_common(1)[0][0]


# --------------------------------------------------------------------------
# Design system page
# --------------------------------------------------------------------------

from mk_ds import build_design_system, build_plate  # noqa: E402


# --------------------------------------------------------------------------
# Gallery index
# --------------------------------------------------------------------------

def build_gallery(entries):
    groups = [("Shared and public", "shared",
               "Screens a signed-out visitor can reach, plus the shell itself."),
              ("Student", "student", "The student portal, from profile to service request."),
              ("Counselor", "counselor", "The guidance caseload and enrollment decisions."),
              ("Administrator", "admin", "Configuration, review queues, analytics and audit.")]
    blocks = []
    for name, folder, lede in groups:
        sel = [e for e in entries if e["folder"] == folder]
        cards = []
        for e in sel:
            cards.append(f"""<a class="g-card" href="screens/{folder}/{e['id']}.html">
  <div class="g-shot"><img loading="lazy" alt="{e['id']} {e['title']}" 
       src="export/{e['id']}--desktop.png"></div>
  <div class="g-body"><div class="g-top"><span class="g-id">{e['id']}</span>
    <span class="g-wf">{e['base']}</span></div>
    <b>{e['title']}</b>
    <span class="g-reqs">{len(e['req_list'])} requirements</span></div>
</a>""")
        blocks.append(f'<h2>{name} <span>({len(sel)})</span></h2>'
                      f'<p class="g-lede">{lede}</p>'
                      f'<div class="g-grid">{"".join(cards)}</div>')

    doc = f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ISU OSAS Hub &middot; Mockup gallery</title>
<link rel="stylesheet" href="assets/mockup.css">
<style>
  body {{ background: #eef2f0; padding: 34px 28px 80px; }}
  .wrap {{ max-width: 1180px; margin: 0 auto; }}
  .hero {{ background: linear-gradient(150deg,#062e1d,#0d6642 72%); color:#fff;
    border-radius:18px; padding:34px 36px; }}
  .hero .eyebrow {{ font-size:10.5px; text-transform:uppercase; letter-spacing:.12em;
    color:#c9a227; font-weight:700; }}
  .hero h1 {{ margin:8px 0 10px; font-size:30px; letter-spacing:-.03em; }}
  .hero p {{ margin:0; max-width:70ch; color:#cfe3d9; font-size:13.5px; line-height:1.65; }}
  .hero .facts {{ display:flex; gap:26px; margin-top:22px; flex-wrap:wrap; }}
  .hero .facts div {{ font-size:11px; text-transform:uppercase; letter-spacing:.08em;
    color:#93b7a5; }}
  .hero .facts b {{ display:block; font-size:22px; letter-spacing:-.02em; color:#fff;
    text-transform:none; margin-bottom:2px; }}
  .toolbar {{ display:flex; gap:10px; margin:22px 0 0; flex-wrap:wrap; }}
  h2 {{ font-size:17px; margin:40px 0 4px; padding-bottom:8px;
    border-bottom:1px solid #dfe5e1; }}
  h2 span {{ color:#6b7280; font-weight:500; font-size:13px; }}
  .g-lede {{ color:#6b7280; font-size:13px; margin:8px 0 18px; }}
  .g-grid {{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }}
  @media (max-width:1000px) {{ .g-grid {{ grid-template-columns:repeat(2,minmax(0,1fr)); }} }}
  @media (max-width:640px) {{ .g-grid {{ grid-template-columns:1fr; }} }}
  .g-card {{ display:block; text-decoration:none; color:inherit; background:#fff;
    border:1px solid #e5e7eb; border-radius:14px; overflow:hidden;
    box-shadow:0 1px 2px rgba(16,24,40,.05); transition:.18s; }}
  .g-card:hover {{ box-shadow:0 12px 28px rgba(6,46,29,.16); transform:translateY(-2px); }}
  .g-shot {{ height:170px; overflow:hidden; background:#f4f7f5;
    border-bottom:1px solid #eef2f0; }}
  .g-shot img {{ width:100%; display:block; }}
  .g-body {{ padding:13px 15px 15px; }}
  .g-top {{ display:flex; justify-content:space-between; align-items:center;
    margin-bottom:6px; }}
  .g-id {{ font:700 10px/1 var(--mono); letter-spacing:.05em; color:#fff;
    background:#0b5d3b; border-radius:5px; padding:5px 7px; }}
  .g-wf {{ font:600 10px/1 var(--mono); color:#6b7280; }}
  .g-body b {{ display:block; font-size:13.5px; line-height:1.35; }}
  .g-reqs {{ display:block; margin-top:6px; font-size:11px; color:#6b7280; }}
</style></head>
<body><div class="wrap">
<div class="hero">
  <div class="eyebrow">High-fidelity mockups &middot; version 1.0</div>
  <h1>ISU OSAS Hub &mdash; interface gallery</h1>
  <p>Forty-six screens rendered in the institutional palette, each built from the same
  declarative structure as its wireframe. Open any card for the full screen at desktop,
  tablet and phone widths, with the design intent recorded underneath.</p>
  <div class="facts">
    <div><b>46</b>screens</div>
    <div><b>3</b>viewports each</div>
    <div><b>109</b>requirements traced</div>
    <div><b>1</b>design system</div>
  </div>
</div>
<div class="toolbar">
  <a class="wf-btn primary" href="design-system.html">Open the design system</a>
  <a class="wf-btn" href="index.md">Document source (Markdown)</a>
</div>
{''.join(blocks)}
</div></body></html>"""
    path = OUT / "index.html"
    path.write_text(doc, encoding="utf-8", newline="\n")
    return path


def main() -> int:
    rows = load_rows()
    reg = mockup_registry(rows)

    entries = []
    problems = []
    for mk, mrows in reg.items():
        base = base_screen(mk, mrows)
        if base not in BY_ID:
            problems.append(f"{mk}: base screen {base} is not in the page registry")
            continue
        p = dict(BY_ID[base])
        if mk in SHOWCASE:
            p["title"] = "Responsive behaviour across three widths"
        req_list = [r["req_id"] for r in mrows]
        wf_trace = {w.strip() for r in mrows
                    for w in (r.get("wireframe") or "").split(";") if w.strip()}
        if base not in wf_trace and mk not in SHOWCASE:
            problems.append(f"{mk}: base {base} not among its own wireframe traces "
                            f"({', '.join(sorted(wf_trace))})")
        p["reqs"] = ", ".join(req_list)
        entries.append({"id": mk, "title": p["title"], "folder": p["folder"],
                        "base": base, "route": p.get("route", ""),
                        "module": p.get("module", ""), "req_list": req_list,
                        "page": p, "showcase": mk in SHOWCASE})

    ids = [e["id"] for e in entries]
    if len(set(ids)) != len(ids):
        problems.append("duplicate mockup identifiers")
    expected = [f"MK-{i:02d}" for i in range(1, 47)]
    if sorted(ids) != expected:
        missing = sorted(set(expected) - set(ids))
        extra = sorted(set(ids) - set(expected))
        problems.append(f"identifier set mismatch; missing={missing} extra={extra}")

    if problems:
        print("BUILD FAILED")
        for p in problems:
            print("  -", p)
        return 1

    for folder in FOLDERS:
        (OUT / "screens" / folder).mkdir(parents=True, exist_ok=True)

    for e in entries:
        html = mk_render.render_mockup(e["page"], e["id"], e["page"]["reqs"],
                                       e["base"], showcase=e["showcase"])
        (OUT / "screens" / e["folder"] / f"{e['id']}.html").write_text(
            html, encoding="utf-8", newline="\n")

    ds = build_design_system(OUT)
    plate = build_plate(OUT)
    gallery = build_gallery(entries)

    with (OUT / "_manifest.csv").open("w", encoding="utf-8", newline="") as fh:
        w = csv.writer(fh)
        w.writerow(["mockup", "title", "folder", "base_wireframe", "route", "module",
                    "requirements", "file", "screenshot"])
        for e in entries:
            w.writerow([e["id"], e["title"], e["folder"], e["base"], e["route"],
                        e["module"], e["page"]["reqs"],
                        f"screens/{e['folder']}/{e['id']}.html",
                        f"export/{e['id']}--desktop.png"])

    counts = collections.Counter(e["folder"] for e in entries)
    print(f"mockups    : {len(entries)}")
    for f in FOLDERS:
        print(f"  {f:<9}: {counts[f]}")
    print(f"design sys : {ds}")
    print(f"plate      : {plate}")
    print(f"gallery    : {gallery}")
    print(f"manifest   : {OUT / '_manifest.csv'}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
