# ISU OSAS Hub — Documentation Set

This folder holds every design and analysis artifact produced for the ISU OSAS Hub
capstone. Nothing outside `documentation/` was touched: the Laravel application, its
routes, models, migrations, configuration, dependencies and git history are exactly as
they were delivered. `documentation/SRS-MACARILAY_ALINDAYU_LUCAS.docx` is the reference
document that set the required structure; it was read only and is unchanged.

---

## 1. The deliverables

| # | Deliverable | Document | Editable source | Evidence |
| --- | --- | --- | --- | --- |
| 1 | Final SRS | `SRS/ISU-OSAS-Hub-SRS.docx` (41 pages, 44 tables) | `SRS/ISU-OSAS-Hub-SRS.md` | — |
| 2 | Final UML diagrams | `UML/ISU-OSAS-Hub-UML.docx` (54 pages, 26 figures) | `UML/source/*.mmd` (26 files), `UML/index.md` | `UML/export/` — 26 SVG + 26 PNG |
| 3 | Final wireframes | `Wireframes/ISU-OSAS-Hub-Wireframes.docx` (92 pages, 122 tables, 65 figures) | `Wireframes/index.md`, page specs in `_tools/wf_pages_*.py` | 59 HTML files, 174 PNG captures |
| 4 | Final UI/UX mockups | `UI-UX-Mockups/ISU-OSAS-Hub-Mockups.docx` (129 pages, 109 tables, 52 figures) | `UI-UX-Mockups/index.md`, `_tools/mk_*.py` | 49 HTML files, 141 PNG captures |
| 5 | Requirements traceability | `Traceability/ISU-OSAS-Hub-Traceability.docx` (27 pages, 19 tables) | `Traceability/index.md`, `Traceability/ISU-OSAS-Hub-Traceability.csv` | CSV is the machine-readable matrix |
| 6 | Design system | `UI-UX-Mockups/design-system.html` (14 sections) | `UI-UX-Mockups/assets/mockup.css` | `UI-UX-Mockups/export/design-plate--*.png` |

Open the `.docx` files for submission. The `.md`, `.csv`, `.mmd`, `.html` and `.py`
files are the sources those documents were generated from, kept so that a reviewer can
verify or regenerate anything.

A summary of what was produced, what is still open and what needs a decision before
submission is in `documentation/FINAL-REPORT.docx` (source `FINAL-REPORT.md`).

### Supporting material

| Path | What it is |
| --- | --- |
| `_spec/requirements.csv` | The single registry: 153 requirements with id, title, actor, priority, status, module, and the UML / wireframe / mockup artifacts that evidence each one. Every document is generated from this file, so the five deliverables cannot disagree about a requirement. |
| `_spec/INVENTORY.md` | The audit behind the registry: verified actors and routes, the 58-screen registry, 5 finite-state machines, design tokens, and gaps `GAP-01`…`GAP-12`. |
| `_spec/srs/` | Section content for the SRS, assembled by `_tools/build_srs.py`. |
| `_tools/` | Every build script (section 4). |

---

## 2. How the pieces relate

```
                     _spec/requirements.csv  (153 requirements, 16 modules)
                                 |
        +------------------------+-------------------------+
        |                        |                         |
   build_srs.py          build_wireframes.py         build_mockups.py
        |                        |                         |
   SRS (.md -> .docx)    58 HTML screens            46 HTML screens
                                 |                         |
                     shoot_wireframes.cjs          mk_render.py (puppeteer)
                                 |                         |
                        174 PNG captures              141 PNG captures
                                 |                         |
                  build_wireframes_doc.py        build_mockups_doc.py
                                 |                         |
                     Wireframes.docx               Mockups.docx + design system
                                 |
                    build_traceability.py  <---+  UML/source/*.mmd
                                 |
                    Traceability.docx + .csv
```

The SRS states *what* the system must do, the UML states *how it is built*, the
wireframes state *where the work happens*, the mockups state *how it looks and behaves*,
and the traceability matrix is the proof that all four describe the same system.

---

## 3. Conventions used everywhere

**Requirement IDs.** `FR-<GROUP>-nn` for functional, `NFR-<GROUP>-nn` for non-functional.
IDs are unique across the whole set and never recycled.

**Status vocabulary.** Exactly three values, applied the same way in all documents:

| Status | Meaning | Count |
| --- | --- | --- |
| Implemented | The behaviour exists in the delivered code and is reachable. | 146 |
| Partial | Some of the behaviour exists; the missing part is named. | 1 |
| Proposed | Not built. Recorded so the gap is explicit, never drawn as a screen. | 6 |

**Actors.** Only three roles exist in the code — `student`, `counselor`, `admin`. The
documentation uses those three names. The requested actors *Adviser*, *OSAS Staff* and
*Super Admin* do not appear anywhere in the source; the SRS records the mapping in a
table and states that OSAS staff work is split between the administrator and counselor
functions rather than being a fourth role.

**Requirement counts.** 153 = 113 functional (16 groups) + 40 non-functional (9 groups).

---

## 4. Building from source

All commands run from the repository root. Python 3.11 and Node 18+ are required;
`pymupdf` and `puppeteer-core` are only needed for QA and capture.

### 4.1 SRS

```powershell
python documentation/_tools/build_srs.py
```

Assembles the sections in `_spec/srs/` into `SRS/ISU-OSAS-Hub-SRS.md` and renders the
DOCX in one step.

### 4.2 UML

```powershell
powershell -File documentation/_tools/render_uml.ps1
python documentation/_tools/md2docx.py documentation/UML/index.md `
  documentation/UML/ISU-OSAS-Hub-UML.docx `
  --title "Unified Modeling Language Diagrams" `
  --subtitle "ISU OSAS Hub - Integrated Student Affairs and Services Management System" `
  --landscape
```

The render script calls `npx -y @mermaid-js/mermaid-cli` with
`PUPPETEER_EXECUTABLE_PATH` pointed at Chrome and writes both SVG and PNG into
`UML/export/`.

### 4.3 Wireframes

```powershell
python documentation/_tools/build_wireframes.py
node documentation/_tools/shoot_wireframes.cjs documentation/Wireframes documentation/Wireframes/export
python documentation/_tools/build_wireframes_doc.py
```

### 4.4 Mockups and the design system

```powershell
python documentation/_tools/build_mockups.py
python documentation/_tools/mk_ds.py
python documentation/_tools/shoot_wireframes.cjs documentation/UI-UX-Mockups documentation/UI-UX-Mockups/export
python documentation/_tools/build_mockups_doc.py
```

### 4.5 Traceability

```powershell
python documentation/_tools/build_traceability.py
python documentation/_tools/md2docx.py documentation/Traceability/index.md `
  documentation/Traceability/ISU-OSAS-Hub-Traceability.docx `
  --title "ISU OSAS Hub - Requirements Traceability Matrix" `
  --subtitle "Isabela State University - Office of Student Affairs and Services" `
  --landscape
```

`build_traceability.py` writes both the CSV and the Markdown; the DOCX follows from the
Markdown with no hand editing.

### 4.6 Full rebuild

Run 4.1 → 4.5 in order. Nothing depends on a manual step, and no document contains a
number that was typed by hand — counts, coverage figures and cross-references are all
computed from `_spec/requirements.csv` at build time.

---

## 5. Quality gates

The build scripts fail loudly rather than produce a quietly wrong document:

| Gate | Where | What it catches |
| --- | --- | --- |
| Duplicate or missing page id | `build_wireframes.py`, `build_mockups.py` | A dropped or duplicated screen. |
| Role-group count drift | `build_wireframes.py` | A screen silently moving between roles. |
| Base screen must trace back | `build_traceability.py` | A mockup whose base wireframe is not in its own requirement trace. |
| Module coverage truth | `build_wireframes_doc.py` | A screen listed under a module that no requirement of that module cites. |
| Orphan screen and orphan diagram lists | `build_traceability.py` | Screens or diagrams that nothing justifies — reported, never hidden. |

Last full QA pass:

- 153 requirement ids, all unique, all well formed.
- 58 wireframe ids unique; 46 mockup ids unique; 26 UML diagrams and 34 use cases resolve to a source file.
- Every wireframe, mockup, diagram and use case cited by a requirement exists; every image path in every `index.md` exists.
- Figure numbers run 1…n in order in each document (26 chapter-numbered figures in UML; 65 and 52 flat-numbered in the wireframe and mockup documents).
- Zero blank pages in all five DOCX files (41 / 54 / 92 / 129 / 27).
- No image exceeds the printable area: largest is 691.2 × 442.8 pt in landscape, 468.0 × 630.0 pt in portrait.

---

## 6. Known limitations and open items

These are documented in the deliverables themselves rather than papered over.

1. **Seven requirements are not fully built** — six proposed (`FR-AUTH-10`, `FR-ENR-02`, `FR-APP-11`, `FR-GUID-13`, `FR-GUID-14`, `FR-SVC-10`) and one partial (`FR-ENR-01`). They appear in the SRS with their status and in the traceability matrix, and are deliberately absent from the screen designs where no interface exists. The underlying gaps are recorded as `GAP-01`…`GAP-12`.
2. **Two wireframe screens are cited by no requirement** — `WF-S-14` and `WF-C-03`. Both routes exist and the screens are reachable; their requirements are traced to the detail screens they lead to. §5.1 of the traceability matrix records the missing citation with a recommendation.
3. **Five UML diagrams carry no single requirement** — the four lifecycle activity diagrams and the deployment view. They describe how requirements combine over time, so no one requirement owns them; §4.3 of the traceability matrix explains this.
4. **No Figma file exists.** The design system is a real, viewable artifact (HTML/CSS plus a printable plate), and the documents say so. No claim of a Figma deliverable is made anywhere.
5. **Screenshots are static captures**, not a running prototype. The HTML sources can be opened in a browser and resized, which is how the responsive evidence in each document was produced.
