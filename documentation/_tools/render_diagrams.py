#!/usr/bin/env python3
"""render_diagrams.py - render the portfolio Mermaid sources to PNG.

Reads   documentation/Portfolio/diagrams/*.mmd
Writes  documentation/Portfolio/figures/diagrams/<name>.png

Uses @mermaid-js/mermaid-cli through npx and points Puppeteer at the locally
installed Chrome so no browser download is required.
"""

from __future__ import annotations

import glob
import os
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, "Portfolio", "diagrams")
OUT = os.path.join(ROOT, "Portfolio", "figures", "diagrams")
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
CONFIG = os.path.join(SRC, "mermaid.json")


def main() -> int:
    os.makedirs(OUT, exist_ok=True)
    files = sorted(f for f in glob.glob(os.path.join(SRC, "*.mmd"))
                   if not os.path.basename(f).startswith("_"))
    if not files:
        print("no .mmd files found")
        return 1

    env = dict(os.environ)
    env["PUPPETEER_EXECUTABLE_PATH"] = CHROME

    failures = 0
    for path in files:
        name = os.path.splitext(os.path.basename(path))[0]
        target = os.path.join(OUT, name + ".png")
        cmd = ["npx.cmd", "-y", "@mermaid-js/mermaid-cli",
               "-i", path, "-o", target, "-c", CONFIG,
               "-b", "white", "-s", "2"]
        result = subprocess.run(cmd, capture_output=True, text=True, env=env, shell=False)
        ok = result.returncode == 0 and os.path.exists(target)
        size = os.path.getsize(target) if ok else 0
        print(f"{'ok  ' if ok else 'FAIL'} {name:34s} {size / 1024:8.1f} KB")
        if not ok:
            failures += 1
            tail = (result.stderr or result.stdout or "").strip().splitlines()[-6:]
            for line in tail:
                print("      " + line)
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
