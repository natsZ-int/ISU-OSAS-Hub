#!/usr/bin/env python3
"""make_sdlc_svg.py - draw the SDLC figure for the portfolio (SDLC-01).

Mermaid cannot lay out a serpentine (wrapped) process, and a single vertical
chain renders as a very tall image that loses legibility once it is scaled to
the width of a printed page. This generator writes a two-row serpentine layout
instead, which keeps the text large relative to the canvas.

Writes documentation/Portfolio/diagrams/SDLC-01-iterative.svg
"""

from __future__ import annotations

import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "Portfolio", "diagrams", "SDLC-01-iterative.svg")

GREEN_DEEP = "#062e1d"
GREEN = "#0b5d3b"
GREEN_FILL = "#eaf3ee"
GOLD = "#c9a227"
GOLD_FILL = "#fbf5e2"
INK = "#1f2937"
MUTED = "#4b5563"

BOX_W, BOX_H = 300, 132
GAP_X, GAP_Y = 36, 64
X0, Y0 = 84, 26
COLS = [X0, X0 + BOX_W + GAP_X, X0 + 2 * (BOX_W + GAP_X)]
ROW_Y = [Y0, Y0 + BOX_H + GAP_Y]

CANVAS_W = COLS[2] + BOX_W + 20
CANVAS_H = ROW_Y[1] + BOX_H + 74

PHASES = [
    ("1", "PLAN", ["Confirm scope, actors and", "the technology stack"]),
    ("2", "SPECIFY", ["113 functional and 40", "non-functional requirements"]),
    ("3", "DESIGN", ["UML, wireframes, mockups", "and the data model"]),
    ("4", "IMPLEMENT", ["Laravel modules M01 to M16,", "one increment at a time"]),
    ("5", "VERIFY", ["Walk through every screen,", "record defects, fix them"]),
    ("6", "REVIEW", ["Update the documentation,", "then the next module group"]),
]
# serpentine order: 0,1,2 across the top; 3,4,5 back across the bottom
POS = [(COLS[0], ROW_Y[0]), (COLS[1], ROW_Y[0]), (COLS[2], ROW_Y[0]),
       (COLS[2], ROW_Y[1]), (COLS[1], ROW_Y[1]), (COLS[0], ROW_Y[1])]


def box(x: float, y: float, number: str, title: str, lines: list[str],
        accent: bool = False) -> str:
    fill = GOLD_FILL if accent else GREEN_FILL
    stroke = GOLD if accent else GREEN
    cx = x + BOX_W / 2
    out = [f'<rect x="{x}" y="{y}" width="{BOX_W}" height="{BOX_H}" rx="12" '
           f'fill="{fill}" stroke="{stroke}" stroke-width="1.6"/>']
    out.append(f'<circle cx="{x + 30}" cy="{y + 30}" r="15" '
               f'fill="{"#ffffff" if not accent else "#ffffff"}" stroke="{stroke}" '
               f'stroke-width="1.4"/>')
    out.append(f'<text x="{x + 30}" y="{y + 35}" text-anchor="middle" '
               f'font-size="17" font-weight="700" fill="{stroke}">{number}</text>')
    out.append(f'<text x="{x + 54}" y="{y + 37}" font-size="20" font-weight="700" '
               f'letter-spacing="1.4" fill="{GREEN_DEEP}">{title}</text>')
    for index, line in enumerate(lines):
        out.append(f'<text x="{cx}" y="{y + 82 + index * 24}" text-anchor="middle" '
                   f'font-size="17.5" fill="{INK}">{line}</text>')
    return "".join(out)


def arrow(x1: float, y1: float, x2: float, y2: float) -> str:
    return (f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{MUTED}" '
            'stroke-width="1.8" stroke-linecap="round" marker-end="url(#head)"/>')


def main() -> int:
    parts: list[str] = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{CANVAS_W}" height="{CANVAS_H}" '
        f'viewBox="0 0 {CANVAS_W} {CANVAS_H}" font-family="Segoe UI, Calibri, Arial, sans-serif">',
        '<defs><marker id="head" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" '
        'markerHeight="7" orient="auto-start-reverse">'
        f'<path d="M 0 0 L 10 5 L 0 10 z" fill="{MUTED}"/></marker>'
        '<marker id="headGold" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" '
        'markerHeight="7" orient="auto-start-reverse">'
        f'<path d="M 0 0 L 10 5 L 0 10 z" fill="{GOLD}"/></marker></defs>',
        f'<rect x="0" y="0" width="{CANVAS_W}" height="{CANVAS_H}" fill="#ffffff"/>',
    ]

    for index, (number, title, lines) in enumerate(PHASES):
        x, y = POS[index]
        parts.append(box(x, y, number, title, lines, accent=(index == 5)))

    top_mid = ROW_Y[0] + BOX_H / 2
    bottom_mid = ROW_Y[1] + BOX_H / 2

    # forward arrows of the serpentine
    parts.append(arrow(COLS[0] + BOX_W + 4, top_mid, COLS[1] - 4, top_mid))
    parts.append(arrow(COLS[1] + BOX_W + 4, top_mid, COLS[2] - 4, top_mid))
    parts.append(arrow(COLS[2] + BOX_W / 2, ROW_Y[0] + BOX_H + 4,
                       COLS[2] + BOX_W / 2, ROW_Y[1] - 4))
    parts.append(arrow(COLS[2] - 4, bottom_mid, COLS[1] + BOX_W + 4, bottom_mid))
    parts.append(arrow(COLS[1] - 4, bottom_mid, COLS[0] + BOX_W + 4, bottom_mid))

    # loop back from review to plan, drawn in the left gutter
    gutter_x = 46
    parts.append(
        f'<path d="M {X0 - 4} {bottom_mid} H {gutter_x} V {top_mid} H {X0 - 6}" '
        f'fill="none" stroke="{GOLD}" stroke-width="1.8" stroke-dasharray="6 5" '
        'marker-end="url(#headGold)"/>')
    parts.append(
        f'<text transform="rotate(-90 {gutter_x - 20} {(top_mid + bottom_mid) / 2})" '
        f'x="{gutter_x - 20}" y="{(top_mid + bottom_mid) / 2}" text-anchor="middle" '
        f'font-size="15" font-weight="600" letter-spacing="0.8" fill="{GOLD}">'
        'next increment</text>')

    note_y = ROW_Y[1] + BOX_H + 44
    parts.append(
        f'<text x="{X0}" y="{note_y}" font-size="15.5" fill="{MUTED}">'
        'The solid arrows run once per increment; the dashed arrow starts the next one.</text>')
    parts.append(
        f'<text x="{X0}" y="{note_y + 26}" font-size="15.5" fill="{MUTED}">'
        'Repository activity runs from 6 October to 8 November 2026 (Table 3.2).</text>')

    parts.append("</svg>")
    svg = "\n".join(parts)
    with open(OUT, "w", encoding="utf-8", newline="\n") as handle:
        handle.write(svg)
    print(f"wrote {OUT} ({CANVAS_W}x{CANVAS_H})")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
