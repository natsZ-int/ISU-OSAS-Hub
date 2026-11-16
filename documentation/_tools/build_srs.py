#!/usr/bin/env python3
"""
build_srs.py - assemble the SRS from modular section sources.

Reads   documentation/_spec/srs/*.md      (authored sections, in filename order)
Reads   documentation/_spec/requirements.csv (canonical requirement registry)
Writes  documentation/SRS/ISU-OSAS-Hub-SRS.md
Builds  documentation/SRS/ISU-OSAS-Hub-SRS.docx  via md2docx.py

Directives understood in the section sources:
  {{REQ_TABLE:PREFIX}}   table of requirements whose id starts with PREFIX-
  {{REQ_STATS}}          status/priority summary for functional requirements
  {{NFR_STATS}}          status/priority summary for non-functional requirements
"""

from __future__ import annotations

import csv
import os
import subprocess
import sys
from collections import Counter, OrderedDict

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SPEC = os.path.join(ROOT, "_spec")
SRS_DIR = os.path.join(SPEC, "srs")
OUT_DIR = os.path.join(ROOT, "SRS")


def load_requirements():
    path = os.path.join(SPEC, "requirements.csv")
    with open(path, encoding="utf-8", newline="") as fh:
        return list(csv.DictReader(fh))


def md_table(headers, rows, widths):
    """Render a markdown table; widths only inform the converter via padding."""
    out = ["| " + " | ".join(headers) + " |"]
    out.append("|" + "|".join("---" for _ in headers) + "|")
    for row in rows:
        cells = [str(c).replace("|", "/") for c in row]
        out.append("| " + " | ".join(cells) + " |")
    return "\n".join(out)


def req_table(rows, prefix):
    sel = [r for r in rows if r["req_id"].startswith(prefix + "-")]
    if not sel:
        return f"_No requirements recorded under {prefix}._"
    body = [(r["req_id"], r["description"], r["actor"], r["priority"], r["status"])
            for r in sel]
    return md_table(
        ["ID", "Requirement", "Actor", "Priority", "Status"],
        body,
        widths=[14, 60, 14, 8, 12],
    ) + f"\n\n*{len(sel)} requirement(s) in this group.*"


def stats_table(rows, kind):
    sel = [r for r in rows if r["req_id"].startswith(kind)]
    status = Counter(r["status"] for r in sel)
    priority = Counter(r["priority"] for r in sel)
    total = len(sel)
    label = "Functional" if kind == "FR" else "Non-functional"
    head = f"**{label} requirement summary.** {total} requirements in total."
    by_status = ", ".join(f"{k}: {v}" for k, v in sorted(status.items()))
    by_priority = ", ".join(f"{k}: {v}" for k, v in sorted(priority.items()))
    rows_out = [("Total", str(total)), ("By status", by_status), ("By priority", by_priority)]
    return head + "\n\n" + md_table(["Measure", "Value"], rows_out, widths=[16, 70])


def main() -> int:
    rows = load_requirements()
    parts = sorted(f for f in os.listdir(SRS_DIR) if f.endswith(".md"))

    chunks = []
    for part in parts:
        with open(os.path.join(SRS_DIR, part), encoding="utf-8") as fh:
            text = fh.read()

        # expand directives line by line so tables keep their structure
        out_lines = []
        for line in text.split("\n"):
            stripped = line.strip()
            if stripped.startswith("{{REQ_TABLE:") and stripped.endswith("}}"):
                prefix = stripped[len("{{REQ_TABLE:"):-2].strip()
                out_lines.append(req_table(rows, prefix))
            elif stripped == "{{REQ_STATS}}":
                out_lines.append(stats_table(rows, "FR"))
            elif stripped == "{{NFR_STATS}}":
                out_lines.append(stats_table(rows, "NFR"))
            else:
                out_lines.append(line)
        chunks.append("\n".join(out_lines))

    document = "\n\n".join(chunks)

    os.makedirs(OUT_DIR, exist_ok=True)
    md_path = os.path.join(OUT_DIR, "ISU-OSAS-Hub-SRS.md")
    with open(md_path, "w", encoding="utf-8", newline="\n") as fh:
        fh.write(document)

    docx_path = os.path.join(OUT_DIR, "ISU-OSAS-Hub-SRS.docx")
    tool = os.path.join(ROOT, "_tools", "md2docx.py")
    cmd = [sys.executable, tool, md_path, docx_path,
           "--title", "Software Requirements Specification",
           "--subtitle", "ISU OSAS Hub — Integrated Student Affairs and Services Management System"]
    subprocess.run(cmd, check=True)

    words = len(document.split())
    print(f"SRS assembled: {len(parts)} sections, {len(rows)} requirements, {words} words")
    return 0


if __name__ == "__main__":
    sys.exit(main())
