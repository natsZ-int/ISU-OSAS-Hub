#!/usr/bin/env python3
"""
build_wireframes.py - emit every wireframe HTML file and the index.

Page specs live in wf_pages_shared.py, wf_pages_student.py,
wf_pages_counselor.py, wf_pages_admin_a.py and wf_pages_admin_b.py.
Requirement identifiers are reconciled against _spec/requirements.csv so the
wireframe set and the traceability matrix can never drift apart.
"""

from __future__ import annotations

import csv
import os
import sys
import pathlib

HERE = pathlib.Path(__file__).resolve().parent
DOC = HERE.parent
REPO = DOC.parent
OUT = DOC / "Wireframes"
SPEC = DOC / "_spec" / "requirements.csv"

sys.path.insert(0, str(HERE))

import wf_kit  # noqa: E402
import wf_pages_shared  # noqa: E402
import wf_pages_student  # noqa: E402
import wf_pages_counselor  # noqa: E402
import wf_pages_admin_a  # noqa: E402
import wf_pages_admin_b  # noqa: E402

FOLDERS = ("shared", "student", "counselor", "admin")


def req_map():
    """wireframe id -> ordered requirement ids, from the canonical CSV."""
    if not SPEC.exists():
        return {}
    out: dict[str, list[str]] = {}
    with SPEC.open(encoding="utf-8-sig", newline="") as fh:
        for row in csv.DictReader(fh):
            wf = (row.get("wireframe") or "").strip()
            if not wf:
                continue
            for wid in [w.strip() for w in wf.split(";") if w.strip()]:
                out.setdefault(wid, []).append(row["req_id"].strip())
    return out


def all_pages():
    pages = []
    for module in (wf_pages_shared, wf_pages_student, wf_pages_counselor,
                   wf_pages_admin_a, wf_pages_admin_b):
        pages.extend(module.PAGES)
    return pages


def main() -> int:
    pages = all_pages()
    rmap = req_map()

    ids = [p["id"] for p in pages]
    dupes = sorted({i for i in ids if ids.count(i) > 1})
    if dupes:
        print("DUPLICATE IDS:", ", ".join(dupes))
        return 1

    for folder in FOLDERS:
        (OUT / folder).mkdir(parents=True, exist_ok=True)

    manifest = []
    for p in pages:
        if p.get("role") == "guest":
            p["folder"] = "shared"
        if rmap.get(p["id"]):
            p["reqs"] = ", ".join(rmap[p["id"]])
        p.setdefault("folder", "shared")
        path = OUT / p["folder"] / f"{p['id']}.html"
        path.write_text(wf_kit.render_page(p), encoding="utf-8", newline="\n")
        manifest.append({
            "id": p["id"],
            "title": p["title"],
            "role": wf_kit.ROLE_LABEL.get(p["role"], "Guest"),
            "route": p.get("route", ""),
            "module": p.get("module", ""),
            "requirements": p.get("reqs", ""),
            "file": f"{p['folder']}/{p['id']}.html",
        })

    index = wf_kit.build_index(pages, str(OUT))

    with (OUT / "_manifest.csv").open("w", encoding="utf-8", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=list(manifest[0].keys()))
        w.writeheader()
        w.writerows(manifest)

    counts = {f: sum(1 for p in pages if p.get("folder") == f) for f in FOLDERS}
    print(f"pages      : {len(pages)}")
    for f in FOLDERS:
        print(f"  {f:<9}: {counts[f]}")
    print(f"index      : {index}")
    print(f"manifest   : {OUT / '_manifest.csv'}")

    expected = {"shared": 6, "student": 19, "counselor": 6, "admin": 27}
    if counts != expected:
        print("COUNT MISMATCH, expected", expected)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
