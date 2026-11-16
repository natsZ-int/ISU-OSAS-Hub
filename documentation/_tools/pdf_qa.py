#!/usr/bin/env python3
"""pdf_qa.py - inspect a generated PDF and render pages for visual review.

Usage:
  python pdf_qa.py file.pdf [--pages 1,2,5] [--render DIR] [--dpi 110]
                            [--json]

Reports page count, page size, and for every page the number of text
characters and images, so that blank or nearly blank pages can be found.
"""

from __future__ import annotations

import argparse
import json
import os
import sys

import pymupdf


def inspect(path: str) -> dict:
    doc = pymupdf.open(path)
    pages = []
    for index, page in enumerate(doc, start=1):
        text = page.get_text("text").strip()
        images = page.get_images(full=True)
        drawings = page.get_drawings()
        pages.append({
            "page": index,
            "width_pt": round(page.rect.width, 1),
            "height_pt": round(page.rect.height, 1),
            "chars": len(text),
            "images": len(images),
            "drawings": len(drawings),
            "first_line": text.splitlines()[0][:90] if text else "",
        })
    return {
        "file": path,
        "pages": len(doc),
        "size_in": (round(doc[0].rect.width / 72, 2), round(doc[0].rect.height / 72, 2)),
        "blank": [p["page"] for p in pages
                  if p["chars"] < 40 and p["images"] == 0],
        "detail": pages,
    }


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("pdf")
    ap.add_argument("--pages", default="")
    ap.add_argument("--render", default="")
    ap.add_argument("--dpi", type=int, default=110)
    ap.add_argument("--json", action="store_true")
    args = ap.parse_args()

    report = inspect(args.pdf)
    if args.json:
        print(json.dumps(report, indent=2))
    else:
        print(f"{os.path.basename(args.pdf)}: {report['pages']} pages, "
              f"{report['size_in'][0]} x {report['size_in'][1]} in")
        print(f"blank or near-blank pages: {report['blank'] or 'none'}")
        for p in report["detail"]:
            flag = "  <- CHECK" if p["page"] in report["blank"] else ""
            print(f"  p{p['page']:>3}  chars={p['chars']:>5}  img={p['images']:>2}  "
                  f"draw={p['drawings']:>4}  {p['first_line']}{flag}")

    if args.pages and args.render:
        os.makedirs(args.render, exist_ok=True)
        wanted = [int(x) for x in args.pages.split(",") if x.strip()]
        doc = pymupdf.open(args.pdf)
        for number in wanted:
            if 1 <= number <= len(doc):
                pix = doc[number - 1].get_pixmap(dpi=args.dpi)
                out = os.path.join(args.render, f"page-{number:03d}.png")
                pix.save(out)
                print("rendered", out)
    return 0


if __name__ == "__main__":
    sys.exit(main())
