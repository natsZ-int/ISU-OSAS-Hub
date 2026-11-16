#!/usr/bin/env python3
"""build_portfolio.py - assemble the System Development Portfolio.

The portfolio is written as Markdown by three content modules, then rendered to
DOCX with md2docx.py (the same renderer used for the SRS, UML, wireframe and
mockup deliverables), and exported to PDF with Word when it is available.

Outputs
  documentation/Portfolio/ISU_OSAS_Hub_System_Development_Portfolio.md
  documentation/Portfolio/ISU_OSAS_Hub_System_Development_Portfolio.docx

Numbering
  Figures and tables are numbered per chapter (Figure 3.4, Table 2.1) and the
  counters are advanced by the helper methods, so inserting a figure never
  leaves a hole in the sequence.
"""

from __future__ import annotations

import csv
import os
import re
import subprocess
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)                      # documentation/
PORTFOLIO = os.path.join(ROOT, "Portfolio")
SPEC = os.path.join(ROOT, "_spec")

sys.path.insert(0, HERE)
import md2docx  # noqa: E402

TITLE = "ISU OSAS Hub"
SUBTITLE = "Integrated Student Affairs and Services Management System"
DOC_NAME = "System Development Portfolio"


# --------------------------------------------------------------------------
# Requirement registry
# --------------------------------------------------------------------------

def load_requirements():
    path = os.path.join(SPEC, "requirements.csv")
    with open(path, encoding="utf-8-sig", newline="") as handle:
        return list(csv.DictReader(handle))


# --------------------------------------------------------------------------
# Document writer
# --------------------------------------------------------------------------

class Doc:
    def __init__(self) -> None:
        self.parts: list[str] = []
        self.chapter = 0
        self.figures = 0
        self.tables = 0

    # -- bookkeeping -------------------------------------------------------
    def start_chapter(self, number: int) -> None:
        self.chapter = number
        self.figures = 0
        self.tables = 0

    def next_figure(self) -> str:
        self.figures += 1
        return f"Figure {self.chapter}.{self.figures}"

    def next_table(self) -> str:
        self.tables += 1
        return f"Table {self.chapter}.{self.tables}"

    # -- blocks ------------------------------------------------------------
    def raw(self, text: str) -> None:
        self.parts.append(text)

    def h1(self, text: str) -> None:
        self.parts.append(f"## {text}")

    def h2(self, text: str) -> None:
        self.parts.append(f"### {text}")

    def h3(self, text: str) -> None:
        self.parts.append(f"#### {text}")

    def h4(self, text: str) -> None:
        self.parts.append(f"##### {text}")

    def p(self, text: str) -> None:
        self.parts.append(text)

    def note(self, text: str) -> None:
        self.parts.append(f"> {text}")

    def bullets(self, items) -> None:
        self.parts.append("\n".join(f"- {item}" for item in items))

    def steps(self, items) -> None:
        self.parts.append("\n".join(f"{n}. {item}" for n, item in enumerate(items, 1)))

    def page_break(self) -> None:
        self.parts.append("<!-- pagebreak -->")

    def toc(self) -> None:
        self.parts.append("<!-- toc -->")

    def toc_static(self, entries) -> None:
        lines = [f"{level}|{title}|{page}" for level, title, page in entries]
        self.parts.append("::: toc\n" + "\n".join(lines) + "\n:::")

    def cover(self, fields: str) -> None:
        self.parts.append("::: cover\n" + fields.strip() + "\n:::")

    def table(self, headers, rows, widths=None, caption: str | None = None,
              compact: bool = False) -> str:
        label = None
        if caption:
            label = self.next_table()
            self.parts.append(f"<!-- caption: {label} — {caption} -->")
        if widths:
            self.parts.append(f"<!-- widths: {','.join(str(w) for w in widths)} -->")
        clean = [[str(cell).replace("|", "/").replace("\n", " ") for cell in row]
                 for row in rows]
        out = ["| " + " | ".join(str(h) for h in headers) + " |",
               "| " + " | ".join("---" for _ in headers) + " |"]
        for row in clean:
            out.append("| " + " | ".join(row) + " |")
        self.parts.append("\n".join(out))
        if compact:
            self.parts.append("")
        return label or ""

    def figure(self, path: str, caption: str) -> str:
        label = self.next_figure()
        self.parts.append(f"![{label} — {caption}]({path})")
        return label

    # -- output ------------------------------------------------------------
    def markdown(self) -> str:
        return "\n\n".join(self.parts) + "\n"

    def write(self, path: str) -> None:
        with open(path, "w", encoding="utf-8", newline="\n") as handle:
            handle.write(self.markdown())


# --------------------------------------------------------------------------
# Main
# --------------------------------------------------------------------------

MD_PATH = os.path.join(PORTFOLIO, "ISU_OSAS_Hub_System_Development_Portfolio.md")
DOCX_PATH = os.path.join(PORTFOLIO, "ISU_OSAS_Hub_System_Development_Portfolio.docx")


def collect_headings(markdown_text: str) -> list[tuple[int, str]]:
    """Return (level, title) for every heading that belongs in the contents.

    Chapters are written with '##', sections with '###' and sub-sections with
    '####', which the renderer maps to Heading 1-3 — exactly the range the
    former TOC field covered.
    """
    headings = []
    for line in markdown_text.splitlines():
        match = re.match(r"^(#{2,4})\s+(.+?)\s*$", line)
        if match:
            headings.append((len(match.group(1)) - 1, match.group(2)))
    return headings


def render_document(doc, md_path: str, docx_path: str) -> None:
    os.makedirs(PORTFOLIO, exist_ok=True)
    doc.write(md_path)
    renderer = os.path.join(HERE, "md2docx.py")
    result = subprocess.run(
        [sys.executable, renderer, md_path, docx_path,
         "--title", f"{TITLE} — {DOC_NAME}",
         "--subtitle", SUBTITLE,
         "--author", "Macarilay, Alindayu, Lucas"],
        capture_output=True, text=True)
    if result.returncode != 0:
        raise RuntimeError(result.stdout + result.stderr)
    print(result.stdout.strip())


def build_doc(toc_entries):
    import portfolio_front
    import portfolio_design
    import portfolio_impl

    doc = Doc()
    requirements = load_requirements()
    portfolio_front.build(doc, requirements, toc_entries)
    portfolio_design.build(doc, requirements)
    portfolio_impl.build(doc, requirements)
    return doc


def measure_pages(docx_path: str) -> list[int]:
    """Ask Word for the printed page number of each Heading 1-3."""
    import json
    import tempfile

    out = os.path.join(tempfile.gettempdir(), "portfolio_heading_pages.json")
    if os.path.exists(out):
        os.remove(out)
    subprocess.run(
        ["powershell", "-NoProfile", "-ExecutionPolicy", "Bypass",
         "-File", os.path.join(HERE, "measure_pages.ps1"),
         "-Docx", docx_path, "-Out", out],
        check=True, capture_output=True, text=True)
    with open(out, encoding="utf-8-sig") as handle:
        rows = json.load(handle)
    if isinstance(rows, dict):
        rows = [rows]
    return [int(row["page"]) for row in rows]


def main() -> int:
    headings = []
    if os.path.exists(MD_PATH):
        with open(MD_PATH, encoding="utf-8") as handle:
            headings = collect_headings(handle.read())

    if headings:
        entries = [(level, title, "") for level, title in headings]
        doc = build_doc(entries)
    else:
        # first ever build: a field TOC gives us the document, then the heading
        # list is read back from the Markdown and replaced by static entries
        doc = build_doc(None)
    render_document(doc, MD_PATH, DOCX_PATH)

    headings = collect_headings(doc.markdown())
    pages = measure_pages(DOCX_PATH)
    if len(pages) != len(headings):
        raise RuntimeError(f"measured {len(pages)} headings, expected {len(headings)}")

    # the contents must not change the height of the page it is laid out on,
    # so one rebuild normally suffices; the second pass only confirms that the
    # printed numbers are stable
    for _ in range(3):
        entries = [(level, title, str(page))
                   for (level, title), page in zip(headings, pages)]
        doc = build_doc(entries)
        render_document(doc, MD_PATH, DOCX_PATH)
        again = measure_pages(DOCX_PATH)
        if again == pages:
            break
        pages = again

    print(f"table of contents: {len(entries)} entries, "
          f"pages {entries[0][2]}-{entries[-1][2]}")

    try:
        from PIL import Image  # noqa: F401
    except ImportError:
        return 0

    # report the figure inventory so a missing file cannot slip through
    used = []
    missing = []
    for line in doc.markdown().splitlines():
        if line.startswith("!["):
            rel = line[line.rindex("](") + 2:].rstrip(")")
            used.append(rel)
            if not os.path.exists(os.path.join(PORTFOLIO, rel)):
                missing.append(rel)
    duplicates = sorted({rel for rel in used if used.count(rel) > 1})
    print(f"figures referenced: {len(used)} ({len(set(used))} distinct) ; "
          f"duplicates: {duplicates or 'none'} ; missing files: {missing or 'none'}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
