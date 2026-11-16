#!/usr/bin/env python3
"""
md2docx.py - minimal, dependency-free Markdown -> DOCX converter.

Why this exists: the build environment has no python-docx, no pandoc and no
LibreOffice. This module writes WordprocessingML directly with the standard
library so the documentation deliverables can be produced reproducibly.

Supported Markdown subset (deliberately small):
  # .. #####            headings (Title, H1..H4)
  paragraph text        inline **bold**, *italic*, `code`
  - item / * item       bullet list
  1. item               numbered list
  | a | b |             tables (second row may be |---|---|)
  ```lang ... ```       fenced code block
  > note                callout / note block
  ![Caption](path)      image, centred, with caption below
  <!-- pagebreak -->    page break
  <!-- toc -->          table-of-contents field (Word refreshes it on open)
  ::: toc ... :::      static table of contents: level|title|page lines
  <!-- caption: text -->table caption, placed above the following table
  ::: cover ... :::    typeset cover page (see DocxBuilder.cover)
  ---                   horizontal rule (ignored)

Usage:
  python md2docx.py input.md output.docx --title "..." --subtitle "..." \
      [--landscape] [--author "..."]

Exit code 0 on success.
"""

from __future__ import annotations

import argparse
import os
import re
import struct
import sys
import zipfile
from datetime import datetime, timezone

EMU_PER_INCH = 914400
TWIPS_PER_INCH = 1440


# --------------------------------------------------------------------------
# XML helpers
# --------------------------------------------------------------------------

def esc(text: str) -> str:
    return (text.replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace('"', "&quot;"))


INLINE_RE = re.compile(r"(\*\*.+?\*\*|`.+?`|\*[^*]+?\*)")


def runs(text: str, base_bold: bool = False, base_italic: bool = False,
         size: int | None = None, color: str | None = None,
         font: str | None = None) -> str:
    """Convert inline markdown into a sequence of <w:r> runs."""
    out = []
    for token in INLINE_RE.split(text):
        if not token:
            continue
        bold, italic, code = base_bold, base_italic, False
        body = token
        if token.startswith("**") and token.endswith("**") and len(token) > 4:
            body, bold = token[2:-2], True
        elif token.startswith("`") and token.endswith("`") and len(token) > 2:
            body, code = token[1:-1], True
        elif token.startswith("*") and token.endswith("*") and len(token) > 2:
            body, italic = token[1:-1], True

        props = []
        if font or code:
            props.append(f'<w:rFonts w:ascii="{esc(font or "Consolas")}" '
                         f'w:hAnsi="{esc(font or "Consolas")}"/>')
        if bold:
            props.append("<w:b/><w:bCs/>")
        if italic:
            props.append("<w:i/><w:iCs/>")
        if color:
            props.append(f'<w:color w:val="{color}"/>')
        if code:
            props.append('<w:shd w:val="clear" w:color="auto" w:fill="F2F2F2"/>')
        if size:
            props.append(f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>')
        rpr = f"<w:rPr>{''.join(props)}</w:rPr>" if props else ""
        out.append(f'<w:r>{rpr}<w:t xml:space="preserve">{esc(body)}</w:t></w:r>')
    return "".join(out)


def para(content: str, style: str | None = None, align: str | None = None,
         indent: int | None = None, num_id: int | None = None,
         spacing_after: int | None = None, keep_next: bool = False) -> str:
    ppr = []
    if style:
        ppr.append(f'<w:pStyle w:val="{style}"/>')
    if num_id is not None:
        ppr.append(f'<w:numPr><w:ilvl w:val="0"/><w:numId w:val="{num_id}"/></w:numPr>')
    if align:
        ppr.append(f'<w:jc w:val="{align}"/>')
    if indent:
        ppr.append(f'<w:ind w:left="{indent}"/>')
    if spacing_after is not None:
        ppr.append(f'<w:spacing w:after="{spacing_after}"/>')
    if keep_next:
        ppr.append("<w:keepNext/>")
    ppr_xml = f"<w:pPr>{''.join(ppr)}</w:pPr>" if ppr else ""
    return f"<w:p>{ppr_xml}{content}</w:p>"


# --------------------------------------------------------------------------
# Image support
# --------------------------------------------------------------------------

def image_size(path: str) -> tuple[int, int]:
    with open(path, "rb") as fh:
        head = fh.read(32)
    if head[:8] == b"\x89PNG\r\n\x1a\n":
        w, h = struct.unpack(">II", head[16:24])
        return w, h
    if head[:2] == b"\xff\xd8":  # JPEG: walk segments
        with open(path, "rb") as fh:
            fh.read(2)
            while True:
                marker = fh.read(2)
                if len(marker) < 2:
                    break
                if marker[0] != 0xFF:
                    break
                length = struct.unpack(">H", fh.read(2))[0]
                if 0xC0 <= marker[1] <= 0xCF and marker[1] not in (0xC4, 0xC8, 0xCC):
                    data = fh.read(5)
                    h, w = struct.unpack(">HH", data[1:5])
                    return w, h
                fh.seek(length - 2, 1)
    return 1200, 800


def image_paragraph(rel_id: str, doc_pr_id: int, path: str, max_width_in: float,
                    caption: str | None, max_height_in: float = 8.8) -> str:
    px_w, px_h = image_size(path)
    width_in = px_w / 96.0
    height_in = px_h / 96.0
    # fit inside BOTH the available width and height so nothing is clipped
    ratio = min(max_width_in / width_in, max_height_in / height_in)
    cx = int(width_in * ratio * EMU_PER_INCH)
    cy = int(height_in * ratio * EMU_PER_INCH)

    drawing = f"""
<w:r><w:drawing>
 <wp:inline distT="0" distB="0" distL="0" distR="0">
  <wp:extent cx="{cx}" cy="{cy}"/>
  <wp:effectExtent l="0" t="0" r="0" b="0"/>
  <wp:docPr id="{doc_pr_id}" name="Picture {doc_pr_id}"/>
  <wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>
  <a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
   <pic:pic>
    <pic:nvPicPr><pic:cNvPr id="{doc_pr_id}" name="{esc(os.path.basename(path))}"/>
     <pic:cNvPicPr/></pic:nvPicPr>
    <pic:blipFill><a:blip r:embed="{rel_id}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
    <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm>
     <a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>
   </pic:pic>
  </a:graphicData></a:graphic>
 </wp:inline>
</w:drawing></w:r>"""
    xml = para(drawing, align="center", spacing_after=60)
    if caption:
        xml += para(runs(caption, size=18, color="4B5563"), style="Caption", align="center")
    return xml


# --------------------------------------------------------------------------
# Document builder
# --------------------------------------------------------------------------

class DocxBuilder:
    def __init__(self, title: str, subtitle: str = "", author: str = "ISU OSAS Hub Documentation",
                 landscape: bool = False):
        self.title = title
        self.subtitle = subtitle
        self.author = author
        self.landscape = landscape
        self.body: list[str] = []
        self.media: list[tuple[str, str]] = []   # (rel_id, path)
        self.rels: list[str] = []
        self.doc_pr = 100
        self.max_width_in = 9.6 if landscape else 6.5
        self.max_height_in = 6.15 if landscape else 8.75
        self.ordered_lists = 0
        self.cover_used = False
        self.field_toc_used = False

    # -- low level ---------------------------------------------------------
    def add(self, xml: str) -> None:
        self.body.append(xml)

    def page_break(self) -> None:
        # A zero-height paragraph that starts a new page. Putting the break on a
        # paragraph of its own (rather than a manual break character) means that
        # if the previous block happens to end exactly at a page boundary the
        # paragraph simply begins the next page instead of leaving a blank one.
        self.add('<w:p><w:pPr><w:pageBreakBefore/>'
                 '<w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/>'
                 '<w:rPr><w:sz w:val="2"/></w:rPr></w:pPr></w:p>')

    def toc(self) -> None:
        self.field_toc_used = True
        self.add(
            '<w:p><w:pPr><w:pStyle w:val="TOCHeading"/></w:pPr></w:p>'
            '<w:p><w:r><w:fldChar w:fldCharType="begin"/></w:r>'
            '<w:r><w:instrText xml:space="preserve">TOC \\o "1-3" \\h \\z \\u</w:instrText></w:r>'
            '<w:r><w:fldChar w:fldCharType="separate"/></w:r>'
            '<w:r><w:t>Table of contents - open in Word and press F9 to refresh.</w:t></w:r>'
            '<w:r><w:fldChar w:fldCharType="end"/></w:r></w:p>'
        )

    def toc_static(self, entries: list[tuple[int, str, str]]) -> None:
        """Typeset a table of contents from pre-computed page numbers.

        A TOC *field* is convenient while drafting, but Word reflows it on
        open and again on every save/export; on a document with this many
        headings and figures that refresh does not terminate.  Static entries
        keep the DOCX and the PDF identical and export deterministically.
        """
        right = int(self.max_width_in * TWIPS_PER_INCH)
        out = []
        for level, title, page in entries:
            indent = {1: 0, 2: 340, 3: 680}.get(level, 680)
            before = 140 if level == 1 else 0
            bold = "<w:b/><w:bCs/>" if level == 1 else ""
            out.append(
                '<w:p><w:pPr>'
                f'<w:tabs><w:tab w:val="right" w:leader="dot" w:pos="{right}"/></w:tabs>'
                f'<w:ind w:left="{indent}" w:right="0" w:firstLine="0"/>'
                f'<w:spacing w:before="{before}" w:after="30" '
                'w:line="240" w:lineRule="auto"/>'
                f'<w:rPr>{bold}<w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
                '</w:pPr>'
                f'<w:r><w:rPr>{bold}<w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
                f'<w:t xml:space="preserve">{esc(title)}</w:t></w:r>'
                '<w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
                f'<w:tab/><w:t>{esc(str(page))}</w:t></w:r></w:p>'
            )
        self.add("".join(out))

    def caption(self, text: str) -> None:
        """Table caption, set above the table it belongs to."""
        self.add(para(runs(text, base_bold=True, size=18, color="083F28"),
                      style="TableCaption", keep_next=True))

    def cover(self, fields: dict) -> None:
        """Typeset a submission cover page from a ::: cover block.

        The cover becomes its own document section whose header and footer are
        empty, so the institutional running head and the page number start on
        the first content page instead of the title page.
        """
        def scalar(key: str, default: str = "") -> str:
            values = fields.get(key) or []
            return values[-1] if values else default

        def line(text: str, size: int, color: str = "1F2937", bold: bool = False,
                 italic: bool = False, caps: bool = False, spacing: int | None = None,
                 before: int = 0, after: int = 0, font: str = "Calibri") -> str:
            props = [f'<w:rFonts w:ascii="{font}" w:hAnsi="{font}" w:cs="{font}"/>']
            if bold:
                props.append("<w:b/><w:bCs/>")
            if italic:
                props.append("<w:i/><w:iCs/>")
            if caps:
                props.append("<w:caps/>")
            props.append(f'<w:color w:val="{color}"/>')
            if spacing:
                props.append(f'<w:spacing w:val="{spacing}"/>')
            props.append(f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>')
            content = (f'<w:r><w:rPr>{"".join(props)}</w:rPr>'
                       f'<w:t xml:space="preserve">{esc(text)}</w:t></w:r>') if text else ""
            ppr = (f'<w:pPr><w:spacing w:before="{before}" w:after="{after}" '
                   'w:line="240" w:lineRule="auto"/><w:jc w:val="center"/></w:pPr>')
            return f"<w:p>{ppr}{content}</w:p>"

        def rule(color: str, before: int, after: int, size: int = 8) -> str:
            return ('<w:p><w:pPr>'
                    f'<w:pBdr><w:bottom w:val="single" w:sz="{size}" w:space="1" '
                    f'w:color="{color}"/></w:pBdr>'
                    f'<w:spacing w:before="{before}" w:after="{after}" '
                    'w:line="20" w:lineRule="exact"/>'
                    '<w:rPr><w:sz w:val="2"/></w:rPr></w:pPr></w:p>')

        out: list[str] = []
        out.append(line("", 2, before=0, after=2700))                      # top air

        institution = scalar("institution", "ISABELA STATE UNIVERSITY")
        out.append(line(institution, 25, color="0B5D3B", bold=True,
                        caps=True, spacing=36, after=120))
        office = scalar("office")
        if office:
            out.append(line(office, 20, color="374151", after=60))
        address = scalar("address")
        if address:
            out.append(line(address, 20, color="6B7280", after=60))
        out.append(rule("C9A227", 240, 0, size=12))

        doc_type = scalar("doc_type")
        if doc_type:
            out.append(line(doc_type, 19, color="8A6A1F", bold=True,
                            caps=True, spacing=44, before=560, after=220))

        out.append(line(scalar("title"), 72, color="062E1D", bold=True, after=160))
        subtitle = scalar("subtitle")
        if subtitle:
            out.append(line(subtitle, 26, color="1F2937", after=140))
        out.append(rule("D5E7DD", 120, 0, size=6))

        program = scalar("program")
        if program:
            out.append(line(f"In partial fulfillment of the requirements in {program}",
                            19, color="4B5563", italic=True, before=460, after=220))
        out.append(line("Submitted by", 17, color="6B7280", bold=True,
                        caps=True, spacing=40, before=360, after=150))

        authors = fields.get("author") or []
        for entry in authors:
            parts = [p.strip() for p in entry.split("|")]
            name = parts[0] if parts else entry
            meta = "  ·  ".join(p for p in parts[1:] if p)
            out.append(line(name, 23, color="111827", bold=True, after=20))
            if meta:
                out.append(line(meta, 17, color="6B7280", italic=True, after=90))
            else:
                out.append(line("", 2, after=90))

        out.append(line(scalar("date"), 21, color="083F28", bold=True,
                        before=2600, after=0))

        # close the cover section so the running head and the page counter begin
        # on the first content page
        out.append(
            '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/>'
            '<w:sectPr>'
            '<w:headerReference w:type="default" r:id="rIdHeaderCover"/>'
            '<w:footerReference w:type="default" r:id="rIdFooterCover"/>'
            f'<w:pgSz w:w="{int(8.27 * TWIPS_PER_INCH)}" '
            f'w:h="{int(11.69 * TWIPS_PER_INCH)}"/>'
            '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" '
            'w:header="567" w:footer="567" w:gutter="0"/>'
            '</w:sectPr></w:pPr></w:p>'
        )
        self.add("".join(out))
        self.cover_used = True

    def table(self, headers: list[str], rows: list[list[str]],
              widths: list[float] | None = None, header_fill: str = "0B5D3B") -> str:
        n = len(headers)
        if widths is None:
            widths = [self.max_width_in / n] * n
        total = sum(widths)
        widths = [w * self.max_width_in / total for w in widths]

        def cell(text: str, w: float, bold: bool = False, fill: str | None = None,
                 header: bool = False) -> str:
            shd = f'<w:shd w:val="clear" w:color="auto" w:fill="{fill}"/>' if fill else ""
            color = "FFFFFF" if header else None
            parts = text.split("<br/>") if text else []
            body = "<w:r><w:br/></w:r>".join(
                runs(p, base_bold=bold, size=17, color=color) for p in parts if p)
            return (f'<w:tc><w:tcPr><w:tcW w:w="{int(w * TWIPS_PER_INCH)}" w:type="dxa"/>'
                    f'{shd}<w:vAlign w:val="top"/></w:tcPr>'
                    f'<w:p><w:pPr><w:spacing w:before="20" w:after="20"/></w:pPr>{body}</w:p></w:tc>')

        grid = "".join(f'<w:gridCol w:w="{int(w * TWIPS_PER_INCH)}"/>' for w in widths)
        out = ['<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/>'
               f'<w:tblW w:w="{int(sum(widths) * TWIPS_PER_INCH)}" w:type="dxa"/>'
               '<w:tblLayout w:type="fixed"/></w:tblPr>']
        out.append(f"<w:tblGrid>{grid}</w:tblGrid>")
        out.append("<w:tr><w:trPr><w:tblHeader/></w:trPr>" +
                   "".join(cell(h, widths[i], bold=True, fill=header_fill, header=True)
                           for i, h in enumerate(headers)) + "</w:tr>")
        for r_i, row in enumerate(rows):
            fill = "F6F8F7" if r_i % 2 == 1 else None
            cells = []
            for i in range(n):
                value = row[i] if i < len(row) else ""
                cells.append(cell(value, widths[i], fill=fill))
            out.append("<w:tr>" + "".join(cells) + "</w:tr>")
        out.append("</w:tbl>")
        out.append(para("", spacing_after=120))
        return "".join(out)

    def image(self, path: str, caption: str | None = None) -> None:
        if not os.path.exists(path):
            self.add(para(runs(f"[missing image: {path}]", base_italic=True, size=18, color="B91C1C")))
            return
        self.doc_pr += 1
        rel_id = f"rIdImg{self.doc_pr}"
        self.media.append((rel_id, path))
        self.add(image_paragraph(rel_id, self.doc_pr, path, self.max_width_in,
                                 caption, self.max_height_in))

    # -- markdown ----------------------------------------------------------
    def collect_list(self, lines: list[str], i: int, pattern: str):
        """Group a list, folding wrapped continuation lines into their item."""
        items: list[list[str]] = []
        while i < len(lines):
            s = lines[i].strip()
            if re.match(pattern, s):
                items.append([re.sub(pattern, "", s)])
                i += 1
                continue
            if items and s and not (
                    s.startswith(("#", "|", ">", "```", "![", "<!--"))
                    or re.match(r"^[-*]\s+", s)
                    or re.match(r"^\d+\.\s+", s)
                    or s in ("---", "***", "___")):
                items[-1].append(s)
                i += 1
                continue
            break
        return [" ".join(parts) for parts in items], i

    def add_markdown(self, text: str, base_dir: str) -> None:
        # strip any byte-order mark so the first line still parses as a heading
        text = text.replace("\ufeff", "")
        lines = text.replace("\r\n", "\n").split("\n")
        i = 0
        pending = None
        while i < len(lines):
            line = lines[i]
            stripped = line.strip()

            # fenced code
            if stripped.startswith("```"):
                i += 1
                block = []
                while i < len(lines) and not lines[i].strip().startswith("```"):
                    block.append(lines[i])
                    i += 1
                i += 1
                self.add(self.code_block("\n".join(block)))
                continue

            # page break / toc / rule
            if stripped == "<!-- pagebreak -->":
                self.page_break()
                i += 1
                continue
            if stripped == "<!-- toc -->":
                self.toc()
                i += 1
                continue
            if stripped in ("---", "***", "___"):
                i += 1
                continue

            # cover block: key: value lines, repeated author: lines allowed
            if stripped == "::: cover":
                fields: dict[str, list[str]] = {}
                i += 1
                while i < len(lines) and lines[i].strip() != ":::":
                    entry = lines[i].strip()
                    if entry and not entry.startswith("#") and ":" in entry:
                        key, _, value = entry.partition(":")
                        fields.setdefault(key.strip().lower(), []).append(value.strip())
                    i += 1
                i += 1
                self.cover(fields)
                continue

            # static table of contents: level|title|page lines
            if stripped == "::: toc":
                entries: list[tuple[int, str, str]] = []
                i += 1
                while i < len(lines) and lines[i].strip() != ":::":
                    entry = lines[i].strip()
                    if entry:
                        bits = entry.split("|")
                        if len(bits) == 3:
                            entries.append((int(bits[0]), bits[1].strip(), bits[2].strip()))
                    i += 1
                i += 1
                self.toc_static(entries)
                continue

            m = re.match(r"<!--\s*caption:\s*(.+?)\s*-->", stripped)
            if m:
                self.caption(m.group(1))
                i += 1
                continue

            # table
            if stripped.startswith("|") and stripped.endswith("|"):
                block = []
                while i < len(lines) and lines[i].strip().startswith("|"):
                    block.append(lines[i].strip())
                    i += 1
                self.add(self.parse_table(block, widths=pending))
                pending = None
                continue

            # column-width hint for the next table
            m = re.match(r"<!--\s*widths:\s*([\d.,\s]+?)\s*-->", stripped)
            if m:
                pending = [float(x) for x in m.group(1).split(",") if x.strip()]
                i += 1
                continue

            # image
            m = re.match(r"!\[(.*?)\]\((.+?)\)", stripped)
            if m:
                caption, rel = m.group(1), m.group(2)
                path = rel if os.path.isabs(rel) else os.path.normpath(os.path.join(base_dir, rel))
                self.image(path, caption or None)
                i += 1
                continue

            # heading
            if stripped.startswith("#"):
                level = len(stripped) - len(stripped.lstrip("#"))
                content = stripped[level:].strip()
                style = {1: "Title", 2: "Heading1", 3: "Heading2",
                         4: "Heading3", 5: "Heading4"}.get(level, "Heading4")
                self.add(para(runs(content), style=style))
                i += 1
                continue

            # lists
            if re.match(r"^[-*]\s+", stripped):
                items, i = self.collect_list(lines, i, r"^[-*]\s+")
                for content in items:
                    self.add(para(runs(content), style="ListParagraph", num_id=1))
                continue
            if re.match(r"^\d+\.\s+", stripped):
                # Each ordered list gets its own numbering instance so the count
                # restarts at 1 instead of continuing through the whole document.
                self.ordered_lists += 1
                num_id = 2 + self.ordered_lists
                items, i = self.collect_list(lines, i, r"^\d+\.\s+")
                for content in items:
                    self.add(para(runs(content), style="ListParagraph", num_id=num_id))
                continue

            # note / callout
            if stripped.startswith(">"):
                content = stripped.lstrip("> ").strip()
                self.add(para(runs(content, base_italic=True, size=19, color="334155"),
                              style="Callout"))
                i += 1
                continue

            # blank
            if not stripped:
                i += 1
                continue

            # paragraph (join wrapped lines)
            buf = [stripped]
            i += 1
            while i < len(lines):
                nxt = lines[i].strip()
                if (not nxt or nxt.startswith(("#", "|", "-", "*", ">", "```", "!["))
                        or re.match(r"^\d+\.\s+", nxt) or nxt in ("---", "***")
                        or nxt.startswith("<!--")):
                    break
                buf.append(nxt)
                i += 1
            self.add(para(runs(" ".join(buf))))

    def parse_table(self, block: list[str], widths: list[float] | None = None) -> str:
        def split_row(row: str) -> list[str]:
            inner = row.strip()
            if inner.startswith("|"):
                inner = inner[1:]
            if inner.endswith("|"):
                inner = inner[:-1]
            parts, cur, in_code = [], "", False
            for ch in inner:
                if ch == "`":
                    in_code = not in_code
                if ch == "|" and not in_code:
                    parts.append(cur.strip())
                    cur = ""
                else:
                    cur += ch
            parts.append(cur.strip())
            return parts

        rows = [split_row(r) for r in block]
        if len(rows) > 1 and all(re.fullmatch(r":?-{2,}:?", c or "") for c in rows[1]):
            headers, data = rows[0], rows[2:]
        else:
            headers, data = rows[0], rows[1:]

        n = len(headers)
        data = [(r + [""] * n)[:n] for r in data]

        # width heuristic from content length
        if widths and len(widths) == n:
            weights = widths
        else:
            weights = []
            for c in range(n):
                longest = max([len(headers[c])] + [len(r[c]) for r in data] or [8])
                weights.append(max(6, min(longest, 60)))
        return self.table(headers, data, widths=weights)

    def code_block(self, text: str) -> str:
        lines = text.split("\n")
        out = []
        for idx, ln in enumerate(lines):
            br = '<w:br/>' if idx else ""
            out.append(f'<w:r>{br}<w:t xml:space="preserve">{esc(ln)}</w:t></w:r>')
        return ('<w:p><w:pPr><w:pStyle w:val="CodeBlock"/></w:pPr>'
                + "".join(out) + "</w:p>")

    # -- packaging ---------------------------------------------------------
    def build(self, out_path: str) -> None:
        body_xml = "".join(self.body)
        sect = (
            '<w:sectPr>'
            f'<w:pgSz w:w="{ int((11.69 if self.landscape else 8.27) * TWIPS_PER_INCH) }" '
            f'w:h="{ int((8.27 if self.landscape else 11.69) * TWIPS_PER_INCH) }"'
            + (' w:orient="landscape"' if self.landscape else "") +
            f'/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" '
            'w:header="567" w:footer="567" w:gutter="0"/>'
            + ('<w:pgNumType w:start="1"/>' if self.cover_used else "") +
            '<w:headerReference w:type="default" r:id="rIdHeader"/>'
            '<w:footerReference w:type="default" r:id="rIdFooter"/>'
            '</w:sectPr>'
        )

        document = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            f"<w:body>{body_xml}{sect}</w:body></w:document>"
        )

        image_rels = "".join(
            f'<Relationship Id="{rid}" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            f'relationships/image" Target="media/img{n}{os.path.splitext(p)[1].lower()}"/>'
            for n, (rid, p) in enumerate(self.media, start=1)
        )
        image_overrides = "".join(
            f'<Default Extension="{os.path.splitext(p)[1].lower().lstrip(".")}" '
            f'ContentType="{"image/png" if p.lower().endswith(".png") else "image/jpeg"}"/>'
            for _, p in self.media
        )
        # de-duplicate Default entries
        seen, defaults = set(), []
        for entry in re.findall(r"<Default [^>]+/>", image_overrides):
            ext = re.search(r'Extension="([^"]+)"', entry).group(1)
            if ext not in seen:
                seen.add(ext)
                defaults.append(entry)

        content_types = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            '<Default Extension="xml" ContentType="application/xml"/>'
            + "".join(defaults) +
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.wordprocessingml.document.main+xml"/>'
            '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.wordprocessingml.styles+xml"/>'
            '<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.wordprocessingml.settings+xml"/>'
            '<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.wordprocessingml.numbering+xml"/>'
            '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.wordprocessingml.header+xml"/>'
            '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.wordprocessingml.footer+xml"/>'
            + (('<Override PartName="/word/header2.xml" ContentType="application/vnd.openxmlformats-'
                'officedocument.wordprocessingml.header+xml"/>'
                '<Override PartName="/word/footer2.xml" ContentType="application/vnd.openxmlformats-'
                'officedocument.wordprocessingml.footer+xml"/>') if self.cover_used else "") +
            '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-'
            'package.core-properties+xml"/>'
            '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-'
            'officedocument.extended-properties+xml"/>'
            "</Types>"
        )

        package_rels = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/officeDocument" Target="word/document.xml"/>'
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/'
            'relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/extended-properties" Target="docProps/app.xml"/>'
            "</Relationships>"
        )

        doc_rels = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/styles" Target="styles.xml"/>'
            '<Relationship Id="rIdSettings" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/settings" Target="settings.xml"/>'
            '<Relationship Id="rIdNumbering" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/numbering" Target="numbering.xml"/>'
            '<Relationship Id="rIdHeader" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/header" Target="header1.xml"/>'
            '<Relationship Id="rIdFooter" Type="http://schemas.openxmlformats.org/officeDocument/2006/'
            'relationships/footer" Target="footer1.xml"/>'
            + (('<Relationship Id="rIdHeaderCover" Type="http://schemas.openxmlformats.org/'
                'officeDocument/2006/relationships/header" Target="header2.xml"/>'
                '<Relationship Id="rIdFooterCover" Type="http://schemas.openxmlformats.org/'
                'officeDocument/2006/relationships/footer" Target="footer2.xml"/>')
               if self.cover_used else "") +
            image_rels +
            "</Relationships>"
        )

        settings = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            '<w:zoom w:percent="100"/>'
            + ('<w:updateFields w:val="true"/>' if self.field_toc_used else "") +
            '<w:defaultTabStop w:val="720"/>'
            '<w:compat><w:compatSetting w:name="compatibilityMode" '
            'w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>'
            "</w:settings>"
        )

        header = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            '<w:p><w:pPr><w:jc w:val="right"/><w:pBdr><w:bottom w:val="single" w:sz="4" '
            'w:space="1" w:color="0B5D3B"/></w:pBdr></w:pPr>'
            f'<w:r><w:rPr><w:color w:val="6B7280"/><w:sz w:val="16"/></w:rPr>'
            f'<w:t xml:space="preserve">{esc(self.title)}</w:t></w:r></w:p></w:hdr>'
        )

        def empty_hf(kind: str) -> str:
            return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    f'<w:{kind} xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
                    '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr></w:p></w:{}>'.format(kind))
        footer = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="6B7280"/></w:rPr>'
            '<w:t xml:space="preserve">Page </w:t></w:r>'
            '<w:r><w:fldChar w:fldCharType="begin"/></w:r>'
            '<w:r><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>'
            '<w:r><w:fldChar w:fldCharType="separate"/></w:r>'
            '<w:r><w:t>1</w:t></w:r>'
            '<w:r><w:fldChar w:fldCharType="end"/></w:r></w:p></w:ftr>'
        )

        numbering = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            '<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0">'
            '<w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="&#8226;"/>'
            '<w:lvlJc w:val="left"/><w:pPr><w:ind w:left="425" w:hanging="284"/></w:pPr>'
            '</w:lvl></w:abstractNum>'
            '<w:abstractNum w:abstractNumId="1"><w:lvl w:ilvl="0">'
            '<w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/>'
            '<w:lvlJc w:val="left"/><w:pPr><w:ind w:left="425" w:hanging="284"/></w:pPr>'
            '</w:lvl></w:abstractNum>'
            '<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>'
            + "".join(
                f'<w:num w:numId="{2 + n}"><w:abstractNumId w:val="1"/>'
                '<w:lvlOverride w:ilvl="0"><w:startOverride w:val="1"/></w:lvlOverride>'
                "</w:num>"
                for n in range(1, self.ordered_lists + 1))
            + "</w:numbering>"
        )

        styles = self.styles_xml()
        now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
        core = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/'
            'metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" '
            'xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/'
            'XMLSchema-instance">'
            f"<dc:title>{esc(self.title)}</dc:title>"
            f"<dc:creator>{esc(self.author)}</dc:creator>"
            f"<cp:lastModifiedBy>{esc(self.author)}</cp:lastModifiedBy>"
            f'<dcterms:created xsi:type="dcterms:W3CDTF">{now}</dcterms:created>'
            f'<dcterms:modified xsi:type="dcterms:W3CDTF">{now}</dcterms:modified>'
            "</cp:coreProperties>"
        )
        app = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/'
            'extended-properties"><Application>ISU OSAS Hub Documentation Builder</Application>'
            "</Properties>"
        )

        with zipfile.ZipFile(out_path, "w", zipfile.ZIP_DEFLATED) as z:
            z.writestr("[Content_Types].xml", content_types)
            z.writestr("_rels/.rels", package_rels)
            z.writestr("word/document.xml", document)
            z.writestr("word/_rels/document.xml.rels", doc_rels)
            z.writestr("word/styles.xml", styles)
            z.writestr("word/settings.xml", settings)
            z.writestr("word/numbering.xml", numbering)
            z.writestr("word/header1.xml", header)
            z.writestr("word/footer1.xml", footer)
            if self.cover_used:
                z.writestr("word/header2.xml", empty_hf("hdr"))
                z.writestr("word/footer2.xml", empty_hf("ftr"))
            z.writestr("docProps/core.xml", core)
            z.writestr("docProps/app.xml", app)
            for n, (_, path) in enumerate(self.media, start=1):
                z.write(path, f"word/media/img{n}{os.path.splitext(path)[1].lower()}")

    def styles_xml(self) -> str:
        def style(sid, name, based, size=None, bold=False, color=None,
                  before=0, after=120, line=276, outline=None, extra=""):
            ppr = (f'<w:spacing w:before="{before}" w:after="{after}" w:line="{line}" '
                   'w:lineRule="auto"/>')
            if outline is not None:
                ppr += f'<w:outlineLvl w:val="{outline}"/>'
            ppr += extra
            rpr = ""
            if size or bold or color:
                rpr = "<w:rPr>"
                if bold:
                    rpr += "<w:b/><w:bCs/>"
                if color:
                    rpr += f'<w:color w:val="{color}"/>'
                if size:
                    rpr += f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>'
                rpr += "</w:rPr>"
            return (f'<w:style w:type="paragraph" w:styleId="{sid}">'
                    f'<w:name w:val="{name}"/><w:basedOn w:val="{based}"/>'
                    f'<w:next w:val="Normal"/><w:qFormat/>'
                    f"<w:pPr>{ppr}</w:pPr>{rpr}</w:style>")

        head_font = '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>'
        return (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            "<w:docDefaults><w:rPrDefault><w:rPr>"
            '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            '<w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr></w:rPrDefault>'
            '<w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/>'
            "</w:pPr></w:pPrDefault></w:docDefaults>"
            '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
            "<w:name w:val=\"Normal\"/><w:qFormat/></w:style>"
            + style("Title", "Title", "Normal", size=56, bold=True, color="0B5D3B",
                    before=0, after=240)
            + style("Heading1", "heading 1", "Normal", size=32, bold=True, color="0B5D3B",
                    before=360, after=160, outline=0)
            + style("Heading2", "heading 2", "Normal", size=26, bold=True, color="083F28",
                    before=300, after=120, outline=1)
            + style("Heading3", "heading 3", "Normal", size=22, bold=True, color="1F2937",
                    before=240, after=100, outline=2)
            + style("Heading4", "heading 4", "Normal", size=20, bold=True, color="334155",
                    before=200, after=80, outline=3)
            + style("Caption", "caption", "Normal", size=18, color="4B5563", before=40, after=200)
            + style("TableCaption", "Table Caption", "Normal", size=18, bold=True,
                    color="083F28", before=200, after=60, extra='<w:keepNext/>')
            + style("ListParagraph", "List Paragraph", "Normal", before=0, after=60)
            + style("Callout", "Callout", "Normal", size=19, before=80, after=120,
                    extra='<w:ind w:left="284"/><w:shd w:val="clear" w:color="auto" '
                          'w:fill="F1F5F9"/>')
            + '<w:style w:type="paragraph" w:styleId="CodeBlock"><w:name w:val="Code Block"/>'
              '<w:basedOn w:val="Normal"/><w:qFormat/>'
              '<w:pPr><w:shd w:val="clear" w:color="auto" w:fill="F4F6F8"/>'
              '<w:spacing w:before="60" w:after="140" w:line="240" w:lineRule="auto"/>'
              '<w:ind w:left="142" w:right="142"/></w:pPr>'
              '<w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/>'
              '<w:sz w:val="17"/><w:szCs w:val="17"/><w:color w:val="1F2937"/></w:rPr>'
              "</w:style>"
            + '<w:style w:type="paragraph" w:styleId="TOCHeading"><w:name w:val="TOC Heading"/>'
              '<w:basedOn w:val="Heading1"/><w:qFormat/>'
              '<w:pPr><w:spacing w:before="0" w:after="200"/></w:pPr></w:style>'
            + '<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/>'
              "<w:tblPr><w:tblBorders>"
              '<w:top w:val="single" w:sz="4" w:color="C9D2CD"/>'
              '<w:left w:val="single" w:sz="4" w:color="C9D2CD"/>'
              '<w:bottom w:val="single" w:sz="4" w:color="C9D2CD"/>'
              '<w:right w:val="single" w:sz="4" w:color="C9D2CD"/>'
              '<w:insideH w:val="single" w:sz="4" w:color="C9D2CD"/>'
              '<w:insideV w:val="single" w:sz="4" w:color="C9D2CD"/>'
              "</w:tblBorders><w:tblCellMar>"
              '<w:top w:w="40" w:type="dxa"/><w:left w:w="80" w:type="dxa"/>'
              '<w:bottom w:w="40" w:type="dxa"/><w:right w:w="80" w:type="dxa"/>'
              "</w:tblCellMar></w:tblPr></w:style>"
            "</w:styles>"
        )


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("input")
    ap.add_argument("output")
    ap.add_argument("--title", default="")
    ap.add_argument("--subtitle", default="")
    ap.add_argument("--author", default="ISU OSAS Hub Documentation")
    ap.add_argument("--landscape", action="store_true")
    args = ap.parse_args()

    with open(args.input, encoding="utf-8") as fh:
        text = fh.read()

    title = args.title or os.path.splitext(os.path.basename(args.input))[0]
    doc = DocxBuilder(title, args.subtitle, args.author, args.landscape)
    doc.add_markdown(text, os.path.dirname(os.path.abspath(args.input)))
    doc.build(args.output)
    print(f"wrote {args.output} ({os.path.getsize(args.output)} bytes, "
          f"{len(doc.media)} images)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
