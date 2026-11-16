# Completion report — ISU OSAS Hub System Development Portfolio

Prepared 15 November 2026. This report records what was generated, how it was
verified, what the group still has to supply, and which known interface defects
were left untouched.

---

## 1. Deliverables

| File | Size | Contents |
|---|---|---|
| `documentation/Portfolio/ISU_OSAS_Hub_System_Development_Portfolio.docx` | 11.4 MB | 90 pages, 18,517 words, 70 figures, 46 tables, static table of contents |
| `documentation/Portfolio/ISU_OSAS_Hub_System_Development_Portfolio.pdf` | 8.9 MB | PDF export of the same document, 90 pages |
| `documentation/Portfolio/ISU_OSAS_Hub_System_Development_Portfolio.md` | 138 KB | Markdown source the DOCX is rendered from |
| `documentation/Portfolio/figures/` | — | 70 images in four folders (see below) |

Figures are organised as:

| Folder | Files | Used for |
|---|---|---|
| `figures/screens/` | 57 PNG | 53 distinct application screens (four are full-page variants of screens already represented) |
| `figures/diagrams/` | 5 PNG | SDLC-01, ARCH-01, ARCH-03, ARCH-04 and the planned ARCH-02 |
| `figures/wireframes/` | 6 PNG | the wireframe set reproduced in Section 3 |
| `figures/mockups/` | 6 PNG | the high-fidelity mockup set reproduced in Section 3 |

Document structure follows `documentation/portfolio instruction.docx` exactly:
1 System Proposal (1.1–1.4.1), 2 Software Requirements Specification
(2.1–2.3), 3 Methodology (3.1–3.5), 4 System Implementation (4.1.1–4.1.4) and
5 Project Resources and Repository Links (5.1–5.3).

## 2. How the document was verified

- Every figure and every numbered table is captioned and referred to in the
  running text; figure numbers run Figure 3.1–4.53, tables Table 1.1–5.4. The
  only uncaptioned table is the "Document control" metadata block on page 1,
  which carries its own heading.
- Requirement tables in Section 2 reproduce all 113 functional requirements
  (grouped M01–M16) and all 40 non-functional requirements (nine categories)
  from `documentation/_spec/requirements.csv`; no identifier, priority or
  status was altered.
- Section 4 uses only screenshots captured from the running application. The
  capture session is described in Table 4.1 and the module coverage in
  Table 4.2. The app was run from a temporary copy on `127.0.0.1:8123` against a
  cloned database; the repository, its `.env` and the original database were not
  modified.
- The PDF was checked page by page: 90 pages, no blank pages, and all 59 table
  of contents entries were matched against the page each heading actually
  appears on.
- `git status --porcelain` shows only `documentation/`: no Laravel source,
  route, view, migration, configuration or dependency file was touched.

## 3. What the group must supply before submission

1. **Author details.** The cover carries the three names taken from the group's
   SRS (`Macarilay, Jake Ace`; `Alindayu, Fortunato Jr.`; `Lucas, John
   Lawrence`). Each still shows `Address: [to be supplied]` and
   `Email: [to be supplied]`.
2. **Figma link (Section 5.1).** No Figma project exists. Either create one and
   replace the entries in Table 5.2, or submit the HTML mockup gallery and
   design-system page as the design record, as Section 5.1 already states.
3. **Deployed system link and credentials (Sections 5.2 and 5.3).** No public
   deployment exists; the document states this and describes the verified
   single-host environment instead. If a campus deployment is provisioned,
   supply the address, the deployment commit, a non-personal demonstration
   account and the statement of which services (database, queue worker,
   scheduler, SMTP) are running.
4. **Repository access.** Confirm that
   `https://github.com/superjynx/ISU-OSAS-Hub.git` is visible to the instructor
   and that the branch to read is `main` at commit `8270c54` (8 November 2026).
   The documentation set itself is untracked, so it is submitted as files
   alongside the repository.
5. **Three optional screenshots.** Section 4 explains why these are absent; if
   the group wants them included, they must be captured from the running app:
   the six-digit verification screen shown after registration, the document
   upload control on a draft application, and the administrator user-edit form.
6. **Cover confirmation.** Check the course title (`Software Engineering I`) and
   the developer order on the cover against the official class list.

## 4. Known interface defects left unmodified

These were observed in the running application and are reported, not fixed,
because the task was limited to `documentation/`:

- The student dashboard prints the literal strings `Submitted &amp; under
  review` and `Requires upload &amp; submission` (double-escaped in the Blade
  view). Section 4.1.2 notes the rendering; the source is unchanged.
- The administrator appointment oversight table uses columns narrow enough to
  wrap status text heavily.
- M05 enrollment verification has no student-facing intake route, so only the
  staff queue and decision screens could be captured.
- M12 service requests have no counselor handler screen; the student side and
  the administrator catalogue are captured instead.
- One seeded scholarship is stored with the title `schooarships for all`. The
  typo is data, not interface, and was deliberately not corrected.

## 5. Rebuilding the document

The document is generated, not hand-edited:

```powershell
python documentation\_tools\build_portfolio.py
powershell -NoProfile -File documentation\_tools\export_pdf.ps1 `
    -Docx documentation\Portfolio\ISU_OSAS_Hub_System_Development_Portfolio.docx
```

`build_portfolio.py` renders the Markdown with `md2docx.py`, then asks Word for
the printed page of every heading (`measure_pages.ps1`) and writes those numbers
into a **static** table of contents. It rebuilds until the numbers stop moving.
The static contents is deliberate: a live Word TOC field makes Word reflow the
document on every export, and on a document of this size that refresh does not
terminate (the earlier field-based build never finished exporting). Edit the
content modules, not the DOCX or the PDF, and rerun both commands.
