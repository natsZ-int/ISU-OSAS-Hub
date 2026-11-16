# Final Report — Documentation Deliverables

**Project:** ISU OSAS Hub — Integrated Student Affairs and Services Management System
**Institution:** Isabela State University, Office of Student Affairs and Services
**Scope of this work:** documentation and design artifacts only
**Date:** 15 November 2026

---

## 1. Summary

Five submission documents and one design system were produced from the delivered Laravel
application. They are internally consistent because every one of them is generated from a
single registry of 153 requirements, not written independently.

<!-- widths: 4,25,37,9,25 -->
| # | Deliverable | File | Size | Extent |
| --- | --- | --- | --- | --- |
| 1 | Final Software Requirements Specification | `documentation/SRS/ISU-OSAS-Hub-SRS.docx` | 49 KB | 41 pages, 44 tables, 153 requirements |
| 2 | Final UML Diagrams | `documentation/UML/ISU-OSAS-Hub-UML.docx` | 4,255 KB | 54 pages, 26 diagrams |
| 3 | Final Wireframes | `documentation/Wireframes/ISU-OSAS-Hub-Wireframes.docx` | 4,452 KB | 92 pages, 58 screens, 65 figures |
| 4 | Final UI/UX Mockups | `documentation/UI-UX-Mockups/ISU-OSAS-Hub-Mockups.docx` | 4,619 KB | 129 pages, 46 screens, 52 figures |
| 5 | Requirements Traceability Matrix | `documentation/Traceability/ISU-OSAS-Hub-Traceability.docx` | 28 KB | 27 pages, 19 tables, 153 rows |
| 6 | Design System | `documentation/UI-UX-Mockups/design-system.html` | 39 KB | 14 sections, printable plate |

A map of every folder, the build order and the reproduction commands is in
`documentation/README.md`.

---

## 2. What each deliverable contains

### 2.1 Final SRS

`documentation/SRS/ISU-OSAS-Hub-SRS.docx` (editable source `ISU-OSAS-Hub-SRS.md`)

Follows the structure and section numbering of the supplied reference document
(`SRS-MACARILAY_ALINDAYU_LUCAS.docx`), with the same academic register. Contents:

- Front matter: document control, revision history, approvals, glossary and references
  to ISO/IEC/IEEE 29148 and IEEE 830.
- Objectives with measurable targets, scope, stakeholders and the actor model.
- 113 functional requirements in 16 groups, each with a unique id, a testable statement,
  a priority, a verification method and its status.
- 40 non-functional requirements in 9 groups, quantified wherever the code allows it.
- Business rules, data requirements, external interfaces, assumptions and constraints,
  and an appendix holding every requirement that is not fully implemented.

Status is stated for every requirement: **146 implemented, 1 partial, 6 proposed**. Each
one traces to the code that satisfies it, or to a named gap where it does not.

### 2.2 Final UML Diagrams

`documentation/UML/ISU-OSAS-Hub-UML.docx` (sources `documentation/UML/source/*.mmd`)

26 diagrams, all rendered from editable Mermaid source into both SVG and PNG
(`documentation/UML/export/`):

<!-- widths: 18,8,74 -->
| Kind | Count | Coverage |
| --- | --- | --- |
| Use case diagram | 1 | 3 actors, 34 use cases, include/extend relationships |
| Class diagram | 1 | 22 domain classes with multiplicities |
| Entity relationship diagram | 1 | 22 business tables, keys and cardinalities |
| Sequence diagrams | 10 | Registration and OTP, login and role enforcement, enrollment verification, scholarship submission, review and approval, eligibility screening and recommendations, guidance booking, service request, notification dispatch, analytics export |
| Activity diagrams | 6 | Student onboarding, application lifecycle, document verification, guidance lifecycle, service request lifecycle, scholarship authoring |
| State machine diagrams | 5 | Enrollment verification, scholarship application, appointment, service request, account |
| Architecture diagrams | 2 | Layered architecture, deployment |

The document is landscape, every image is fitted inside both the printable width and the
printable height, and each figure carries a caption that states what it shows.

### 2.3 Final Wireframes

`documentation/Wireframes/ISU-OSAS-Hub-Wireframes.docx`
(HTML in `shared/`, `student/`, `counselor/`, `admin/`; captures in `export/`)

58 low-fidelity screens, one per verified route, grouped by role: 4 guest,
21 student, 6 counselor, 27 administrator. Every screen is annotated with:

- its purpose and the requirement ids it satisfies;
- region-by-region hierarchy and the component inventory;
- the actions available, with the primary action named;
- form, table, filter and empty-state behaviour;
- a responsive note, evidenced by three captures at 1440, 1024 and 390 px.

Fidelity is deliberately low: grey boxes, no colour, structure and behaviour only. The
document also records what was *not* drawn and why, and where the wireframes differ from
the delivered interface.

### 2.4 Final UI/UX Mockups

`documentation/UI-UX-Mockups/ISU-OSAS-Hub-Mockups.docx`
(HTML in `screens/`; captures in `export/`)

46 high-fidelity screens — the same page specifications as the wireframes, restyled with
the institutional design system so the two sets stay directly comparable. Each screen is
shown at desktop width, with tablet and mobile behaviour documented and captured.
The visual language is a university student-affairs interface: institutional green for
structure and primary action, gold reserved for emphasis, a restrained neutral surface,
tabular figures for data, and status expressed as a word as well as a colour.

The mockups include the full interaction vocabulary — tables with row actions, filters,
multi-step forms with inline errors, decision panels with required reasons, confirmation
dialogs, empty states, and the notification and history patterns.

### 2.5 Design System

`documentation/UI-UX-Mockups/design-system.html`, backed by
`UI-UX-Mockups/assets/mockup.css`

Fourteen sections: colour, typography, space/radius/elevation, buttons, form controls,
status vocabulary, tables, metrics and notices, navigation, charts, icons, accessibility,
content rules and governance. Every token is read from the stylesheet the application
actually loads, so the mockups and the delivered system describe the same interface;
values that are not in the application are marked as proposed and never presented as
implemented.

Contrast ratios are measured and listed against WCAG 2.2 AA. A compact printable plate
(`export/design-plate--desktop.png`) condenses the system onto one page for review.

### 2.6 Requirements Traceability Matrix

`documentation/Traceability/ISU-OSAS-Hub-Traceability.docx`, with the machine-readable
matrix in `ISU-OSAS-Hub-Traceability.csv` (153 rows × 13 columns).

Forward traceability from each requirement to its wireframe, mockup, UML artifacts and
verification method; backward traceability from each artifact to the requirements it
evidences; coverage summaries by module and by status; and an explicit register of the
items that do not trace cleanly.

Headline coverage:

<!-- widths: 30,14,56 -->
| Artifact | Count | Requirements traced |
| --- | --- | --- |
| Wireframe screens | 58 | 138 of 153 |
| Mockups | 46 | 109 of 153 |
| UML diagrams | 26 | 89 of 153 |
| Use cases | 34 | 100 of 153 |
| Any UML artifact | — | 139 of 153 |

---

## 3. Incomplete items and the reason for each

Nothing was omitted silently. Every item below is recorded in the deliverable it affects.

<!-- widths: 30,48,22 -->
| Item | Reason | Where it is recorded |
| --- | --- | --- |
| Six proposed requirements are not built: self-service password reset, verification-request intake, appointment rescheduling, counselor notes authoring, counselor service-request handling, saved searches | The delivered application has no route or handler for them. They are specified so the gap is explicit, and no screen was drawn that would imply they exist. | SRS appendix; traceability §5.2; `GAP-01`…`GAP-12` in `_spec/INVENTORY.md` |
| One partial requirement: enrollment verification submission | The verification table is read and decided, but only one provider path writes it. | SRS requirement entry; traceability row |
| Two wireframe screens are cited by no requirement (`WF-S-14`, `WF-C-03`) | Both routes exist and both screens are reachable; their requirements are traced to the detail screens they lead to, so the function is covered but the screen itself has no direct citation. Deleting them would remove the only path to the decision screens, so they are kept and flagged. | Traceability §5.1, with a recommendation to add the citation |
| Five UML diagrams trace to no single requirement (four lifecycle activity diagrams, deployment view) | They show how several requirements combine over time, so no one requirement owns the diagram. The steps are traced through the use cases and sequence diagrams instead. | Traceability §4.3 |
| No Figma file | No Figma file exists and none was produced. The design system is delivered as real HTML/CSS plus a printable plate, and no document claims a Figma deliverable. | `README.md` §6; design system §14 |
| Screenshots are static captures, not a running prototype | The Laravel application was not to be modified or run as part of this work, so the captures come from the HTML design sources. The HTML can be opened and resized to demonstrate the responsive behaviour. | `README.md` §6 |

---

## 4. Assumptions and items needing confirmation

**Assumptions made from the code, not from briefs.** Each is stated in the SRS:

1. **Three roles only.** The code defines `student`, `counselor` and `admin`. The
   requested *Adviser*, *OSAS Staff* and *Super Admin* do not exist in any route,
   middleware, policy or model. The documents use the three verified names and map the
   requested names to them in a table.
2. **"OSAS Staff" is a function, not a role.** Staff work is split between the
   administrator role (records, verification, scholarships, audit) and the counselor role
   (guidance, appointments). It is documented that way rather than invented as a fourth
   actor.
3. **One role per account.** The schema and middleware enforce a single role, so
   requirements are written against one actor at a time.
4. **The application is the source of truth.** Where a brief and the code disagreed, the
   code won and the difference was recorded as a gap rather than smoothed over.
5. **Requirement granularity.** "One requirement" means one testable statement with one
   verification outcome. A screen therefore satisfies several requirements, which is why
   the counts in the matrix differ from the screen counts.

**Needs confirmation before final submission:**

1. **The actor mapping.** If the university requires *Adviser* or *OSAS Staff* to appear
   as named actors in the SRS, that is a scope change to the system, not a documentation
   change — the roles must first exist in the code. Please confirm the three-role model is
   acceptable, or schedule the role work.
2. **The proposed requirements.** Confirm that the six proposed items belong in the
   specification as future work, or that they should be removed entirely.
3. **The two uncited screens.** Confirm the recommendation to keep `WF-S-14` and
   `WF-C-03` and add the missing requirement citations, rather than deleting the screens.
4. **Reference-structure fidelity.** The SRS follows the reference document's structure
   and numbering. If the panel expects additional sections (for example a separate
   security plan or a test specification), those are not in this set.
5. **Submission format.** The documents are `.docx` and open correctly in Word 2016 and
   later. If PDF is required, they should be exported from Word so the table of contents
   is populated — in the `.docx` the TOC is a field that refreshes on open (F9).

---

## 5. Confirmation: no application files were modified

The Laravel application was treated as read-only throughout. No file outside
`documentation/` was created, changed or deleted.

Verified with git in the project root:

```
$ git status --porcelain
?? documentation/

$ git status --porcelain | where { $_ -notmatch 'documentation/' } | count
0
```

The only change in the working tree is the new, untracked `documentation/` folder.
Specifically, none of the following was touched: `app/`, `routes/`, `config/`,
`database/`, `public/`, `resources/`, `tests/`, `composer.json`, `composer.lock`,
`package.json`, `.env`, or any dotfile or git metadata.

The supplied reference document `documentation/SRS-MACARILAY_ALINDAYU_LUCAS.docx` was
read to match its structure and is unchanged — its last-write timestamp remains
28 September 2026, before this work began.

---

## 6. How to check the work

1. Open any `.docx` and press **F9** (or accept the prompt) to populate the table of
   contents.
2. Compare a requirement in the SRS with the same id in the traceability CSV — the
   artifacts listed there are the ones drawn in the wireframe and mockup documents.
3. Open `documentation/UI-UX-Mockups/design-system.html` in a browser to see the live
   design system.
4. Open any file in `documentation/Wireframes/` or `documentation/UI-UX-Mockups/screens/`
   and resize the window past 1024 px to see the responsive behaviour described in the
   documents.
5. Run the build scripts in the order given in `documentation/README.md` to regenerate
   every document from source.
