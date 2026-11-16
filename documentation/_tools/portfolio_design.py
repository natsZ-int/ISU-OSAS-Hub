#!/usr/bin/env python3
"""portfolio_design.py - Section 3, Methodology."""

from __future__ import annotations

COMMITS = [
    ("2026-10-06", "Initial ISU OSAS Hub system"),
    ("2026-10-11", "Update ISU OSAS Hub system"),
    ("2026-10-13", "Update ISU OSAS Hub system"),
    ("2026-10-18", "Update ISU OSAS Hub system"),
    ("2026-10-21", "Apply Pint code formatting"),
    ("2026-11-02", "feat: add student profile management features"),
    ("2026-11-08", "Fix student profile security and improve scholarship features"),
    ("2026-11-08", "Improve user management UI and functionality"),
]


def build(doc, requirements) -> None:
    doc.start_chapter(3)
    doc.h1("3. Methodology")

    # ------------------------------------------------------------------
    doc.h2("3.1 System Development Life Cycle (SDLC) Model Used")
    doc.p(
        "The project followed an **iterative and incremental** process: the system was delivered as "
        "a sequence of working slices, each one planned, specified, designed, implemented and walked "
        "through before the next slice began. The method was applied informally. It is described "
        "here from evidence in the repository and in the documentation set rather than from a "
        "methodology document, because no such document was produced.")
    doc.p("Four observations support that conclusion.")
    doc.bullets([
        "**The repository is a sequence of increments, not a single delivery.** Git records eight "
        "commits between 6 October and 8 November 2026 (Table 3.1). The messages name module-level "
        "work — student profile management, scholarship features, user management — and the "
        "application grew one module group at a time rather than arriving complete.",
        "**The interface was revised after use.** Later commits fix and improve behaviour that the "
        "earlier ones introduced, which is the signature of a cycle that returns to design and "
        "implementation rather than a single pass through the phases.",
        "**The requirement set matured with the system.** The clarification register that accompanies "
        "the SRS still carries items that were resolved while the screens were being built, which "
        "would not happen in a strict waterfall sequence.",
        "**No formal Scrum artefacts exist.** There is no product backlog, no sprint board, no "
        "velocity record and no stand-up log in the project. Calling the process Scrum would "
        "overstate it; calling it iterative describes what the evidence shows.",
    ])
    doc.p(
        "The model suited the project for three reasons. The team was small, so the cost of "
        "revisiting a decision was low and the cost of a long sign-off cycle was high. The domain "
        "rules — eligibility thresholds, appointment rules, verification steps — were clarified by "
        "building the screens that expressed them. And the course calendar required a demonstrable "
        "system early, which an increment provides and a phase-gate model does not.")
    doc.p(
        "Figure 3.1 shows the cycle that was actually run. Each pass begins by choosing the next "
        "module group, restates its requirements as testable statements, designs the screens and "
        "the data it needs, implements it in Laravel, walks through the result in the running "
        "application, updates the documentation set, and then returns to planning with the defects "
        "and the next group.")
    doc.figure("figures/diagrams/SDLC-01-iterative.png",
               "Iterative and incremental development cycle used for ISU OSAS Hub")

    doc.p("The phases and the artefacts each phase produced are as follows.")
    doc.bullets([
        "**Plan.** Scope the term's work, fix the technology stack (PHP 8.2, Laravel 12, MariaDB), "
        "name the three roles, and enumerate the modules M01 to M16 that become the unit of "
        "delivery.",
        "**Specify.** Restate the chosen module's behaviour as numbered requirements with priorities "
        "and acceptance criteria, including the business rules for eligibility thresholds, "
        "appointment windows and status transitions.",
        "**Design.** Model the module in UML (use case, class, sequence and state diagrams), draw "
        "its screens as wireframes, and restate them as high-fidelity mockups built from the same "
        "design tokens as the application.",
        "**Implement.** Build the route, middleware, form request, controller, service, model, "
        "migration and Blade views for the module, and seed the data the screens need.",
        "**Verify.** Walk through every screen in the running application, exercise the permitted "
        "and forbidden transitions, run the automated test suite, and record defects and gaps "
        "instead of hiding them.",
        "**Review and increment.** Apply the fixes, update the documentation set, and return to "
        "planning for the next module group.",
    ])

    doc.table(
        ["Date", "Commit subject as recorded by Git"],
        [[date, subject] for date, subject in COMMITS],
        widths=[1.2, 5.3],
        caption="Commit history of the delivered system")
    doc.p(
        "The history shows the rhythm of the work rather than its content: five commits in the first "
        "two weeks establish the system, one is a formatting pass with Laravel Pint, and the last "
        "three add profile management, repair profile security, improve scholarship behaviour and "
        "rework user management. The documentation deliverables were produced after the code, which "
        "is why they describe the delivered behaviour rather than an intention.")

    # ------------------------------------------------------------------
    doc.h2("3.2 Tools, Frameworks, and Technologies Used")
    doc.p(
        "The table below lists every technology that the delivered system depends on, with the role "
        "it plays and the evidence for its presence. Documentation tooling is listed separately "
        "because it produced this portfolio and not the application.")
    doc.table(
        ["Technology", "Version or identifier", "Role in the project", "Evidence in the repository"],
        [
            ["PHP", "8.2.12", "Server-side language and runtime", "`composer.json` requires `^8.2`; "
                                                              "verified with `php -v`"],
            ["Laravel", "12.x",
             "Framework: routing, middleware, validation, Eloquent ORM, sessions, queues, scheduler, "
             "Blade", "`composer.json`, `app/`, `bootstrap/`, `routes/`"],
            ["MariaDB", "10.4",
             "Relational database holding every record, with foreign keys and indexes",
             "`DB_CONNECTION=mariadb` in `.env`; 30 tables in `isu_osas_hub`"],
            ["Blade", "Laravel 12 templating",
             "Server-rendered views, layout inheritance and reusable components", "91 `.blade.php` "
                                                                                "files in "
                                                                                "`resources/views/`"],
            ["HTML5 and CSS3", "`public/css/app.css`",
             "Single stylesheet for the whole interface, including the responsive rules",
             "69 KB stylesheet linked from the layout; no CSS framework at runtime"],
            ["JavaScript", "`public/js/app.js`",
             "Progressive enhancement: password toggle, sidebar drawer, search suggestions",
             "18.8 KB script linked from the layout"],
            ["Composer", "2.x", "PHP dependency management and autoloading",
             "`composer.json`, `composer.lock`, `vendor/`"],
            ["PHPUnit", "11.x", "Automated tests of authentication, authorisation and workflows",
             "`phpunit.xml`, `tests/`"],
            ["Laravel Pint", "1.x", "Code style enforcement", "`composer.json`; commit of "
                                                             "21 October 2026"],
            ["XAMPP (Apache, MySQL, PHP)", "Local installation",
             "Development runtime that supplies Apache and MariaDB on the workstation",
             "Documented in the reference SRS; MariaDB service running locally"],
            ["PHP built-in server", "`php artisan serve`",
             "Serves the application during development and during the screenshot session",
             "Used to run the copy from which Section 4 was captured"],
            ["Git and GitHub", "Remote `superjynx/ISU-OSAS-Hub`",
             "Version control and the shared repository", "`git log`; `git remote -v`"],
            ["Visual Studio Code", "Editor",
             "Source editing for PHP, Blade, CSS and JavaScript",
             "Recorded as the development environment in the reference SRS"],
            ["Mermaid", "mermaid-cli 12.2",
             "Documentation tooling: the SDLC, context and deployment diagrams in this portfolio",
             "`documentation/Portfolio/diagrams/`"],
            ["Headless Chrome and Puppeteer", "Chrome 141, puppeteer-core",
             "Documentation tooling: renders wireframes and mockups to PNG and captures the "
             "implementation screenshots", "`documentation/_tools/*.cjs`"],
            ["Python", "3.11",
             "Documentation tooling: builders that assemble the SRS, UML, wireframe, mockup, "
             "traceability and portfolio documents", "`documentation/_tools/*.py`"],
        ],
        widths=[1.15, 1.1, 2.35, 1.9],
        caption="Technology stack and the role of each technology")

    doc.note(
        "`package.json` declares Vite 7 and Tailwind 4 as development dependencies, but the delivered "
        "interface does not use them: there is no `public/build` directory and no Blade template "
        "references `@vite`. The pages are served by the single stylesheet and the single script "
        "listed above, which is why the application runs without a JavaScript build step.")

    # ------------------------------------------------------------------
    doc.h2("3.3 System Architecture and Design")
    doc.h3("3.3.1 Overall System Architecture")
    doc.p(
        "ISU OSAS Hub is a self-contained, server-rendered web application. It does not replace the "
        "university student information system and does not consume data from it; the academic "
        "attributes a scholarship decision needs are held in the hub's own student profile. There "
        "is no single-page front end, no separate API tier and no third-party service other than the "
        "SMTP account used for outbound mail.")
    doc.p(
        "Figure 3.2 shows the components and the direction of every dependency. A request arrives "
        "from a browser over HTTP, is handled by the Laravel application, and leaves as rendered "
        "HTML. The application reads and writes MariaDB through PDO, reads and writes uploaded "
        "documents on a private disk, and dispatches one job type: the notification email that a "
        "queue worker sends through SMTP.")
    doc.figure("figures/diagrams/ARCH-03-system-context.png",
               "System context: actors, client, application, data stores and external services")

    doc.p(
        "Inside the application the same request passes through a fixed sequence of layers, shown "
        "in Figure 3.3. Authentication establishes the session; the active-account middleware "
        "rejects deactivated users; a role or gate check authorises the route; the enrollment "
        "middleware optionally blocks enrollment-dependent student actions; a form request validates "
        "input; a service class applies the business rule; an Eloquent model persists through a "
        "policy check; and a Blade view renders the response. Documents are written to the private "
        "disk and read back only through an authorisation-checked download route.")
    doc.figure("figures/diagrams/ARCH-01-layered.png",
               "Layered architecture of the application, from HTTP entry point to persistence")
    doc.p(
        "Two consequences of this design are worth stating because they explain later sections. "
        "First, authorisation is a property of the route and the record, not of the template, so "
        "hiding a menu entry cannot grant or deny access. Second, because rendering happens on the "
        "server, every screen in Section 4 is a complete HTML document produced by a controller and "
        "a Blade view — which is what makes the screenshots in that section verifiable against the "
        "routes listed in Table 4.1.")

    doc.h3("3.3.2 UML Deployment Diagram")
    doc.p(
        "No production deployment of ISU OSAS Hub exists. The system was developed and demonstrated "
        "on a single Windows workstation, and that is the only environment that has been verified. "
        "Figure 3.4 therefore shows what exists: a client browser, an application server that is the "
        "PHP built-in server running Laravel 12, and the three stores the application writes to — "
        "the MariaDB database, the private document directory and the application log. All of them "
        "reside on the same machine, which is why the figure shows one host rather than three.")
    doc.figure("figures/diagrams/ARCH-04-deployment.png",
               "Deployment of the verified environment: one development host serving all components")
    doc.table(
        ["Node", "What it is in the verified environment", "Connection"],
        [
            ["Client workstation",
             "Any desktop or tablet browser used to reach the system; the screenshots in Section 4 "
             "were taken at a 1440 by 980 viewport",
             "HTTP over loopback to 127.0.0.1:8000"],
            ["Application server",
             "PHP 8.2.12 built-in web server executing Laravel 12 from `public/index.php`",
             "Receives HTTP; opens PDO connections; reads and writes the local disk"],
            ["Database node",
             "MariaDB 10.4 holding the `isu_osas_hub` schema of 30 tables",
             "TCP 3306, PDO driver, credentials from `.env`"],
            ["File store",
             "`storage/app/private` on the server's local disk, holding uploaded documents",
             "Read and written by the application; served only through download routes"],
            ["Log file",
             "`storage/logs/laravel.log`, including mail that the log transport captures",
             "Written by the framework logger"],
        ],
        widths=[1.3, 3.2, 2.0],
        caption="Deployment nodes in the verified environment")
    doc.p(
        "The UML deliverable also contains a target deployment, reproduced in Figure 3.5, that "
        "separates the application server, the database host, private storage, background "
        "processing and an SMTP relay, and terminates client traffic with HTTPS on a campus network. "
        "It is a design intention and has not been provisioned anywhere. It is included here so "
        "that the portfolio and the UML set describe the same design, and it is labelled as planned "
        "for exactly that reason.")
    doc.figure("figures/diagrams/ARCH-02-deployment-target.png",
               "Target campus deployment from the UML deliverable — planned, not provisioned")
    doc.p(
        "Moving from Figure 3.4 to Figure 3.5 would require five things that do not exist today: an "
        "HTTPS virtual host with a certificate, a supervised queue worker, a scheduled task runner "
        "for the hourly appointment-reminder command, a backup and retention policy for the database "
        "and the document directory, and an SMTP account. Until those are in place, the honest "
        "statement is that the system runs on one workstation and that no public address serves it; "
        "Section 5.3 repeats that statement for the submission record.")

    # ------------------------------------------------------------------
    doc.h2("3.4 System Interface Design (Wireframes)")
    doc.p(
        "The wireframe set contains 58 low-fidelity screens across the four interface groups — four "
        "guest screens, twenty-one student screens, six counselor screens and twenty-seven "
        "administrator screens — each rendered at 1440, 1024 and 390 pixels. The screens are "
        "structural: they fix hierarchy, labelling and control placement without colour, so that "
        "layout can be reviewed before visual design begins. Every screen is bound to the routes "
        "that exist, and no screen was drawn for a capability the system does not have.")
    doc.p(
        "Six screens are reproduced below to show the range: an unauthenticated entry point, the two "
        "student tasks that carry the most state (booking a slot and filing a request), the "
        "counselor decision screen, and the administrator view that consolidates the three "
        "workflows.")
    doc.figure("figures/wireframes/WF-G-01--desktop.png",
               "Wireframe WF-G-01 — Login, the only entry point for all three roles")
    doc.p(
        "The login screen carries two fields, a password reveal control, a remember-me option and a "
        "single primary action, with registration offered as a secondary link. Nothing about the "
        "role is chosen here: the destination after sign-in is decided by the account's role.")
    doc.figure("figures/wireframes/WF-S-01--desktop.png",
               "Wireframe WF-S-01 — Student dashboard with the status of each active record")
    doc.p(
        "The student dashboard is a summary rather than a workspace: it repeats the state of "
        "scholarship applications, appointments and service requests so that a student can see what "
        "is waiting on them and what is waiting on the office, and links each block to its detail "
        "screen.")
    doc.figure("figures/wireframes/WF-S-13--desktop.png",
               "Wireframe WF-S-13 — Appointment slots and booking")
    doc.p(
        "Availability is presented as a date and type filter over generated slots. A slot can be "
        "booked only when the counselor's published window, the office hours, the minimum notice and "
        "the booking horizon all permit it, which is why the screen shows a slot list rather than a "
        "free-form time field.")
    doc.figure("figures/wireframes/WF-S-17--desktop.png",
               "Wireframe WF-S-17 — New service request")
    doc.p(
        "A service request begins with a type chosen from the administrator-maintained catalogue, "
        "because the type determines which supporting documents the office will ask for. The "
        "description and attachments follow, and the request number is assigned by the system on "
        "submission.")
    doc.figure("figures/wireframes/WF-C-04--desktop.png",
               "Wireframe WF-C-04 — Counselor appointment detail and decisions")
    doc.p(
        "The counselor screen places the request, the student's stated reason and the permitted "
        "decisions on one page. Only transitions that the appointment state machine allows are "
        "offered, and a rejection requires a reason that the student will see.")
    doc.figure("figures/wireframes/WF-A-24--desktop.png",
               "Wireframe WF-A-24 — Administrator analytics overview")
    doc.p(
        "The analytics overview is the administrator's consolidated view of the three workflows "
        "over a chosen period. Each figure is a count of records rather than a rate that cannot be "
        "traced, and each block links to the tab that explains it.")
    doc.table(
        ["Wireframe", "Screen", "Role", "Requirements it expresses"],
        [
            ["WF-G-01", "Login", "Guest", "FR-AUTH-01 to FR-AUTH-03"],
            ["WF-S-01", "Student dashboard", "Student", "FR-APP, FR-GUID and FR-SVC summary rows"],
            ["WF-S-13", "Appointment slots and booking", "Student", "FR-GUID-05 to FR-GUID-08"],
            ["WF-S-17", "New service request", "Student", "FR-SVC-02 to FR-SVC-05"],
            ["WF-C-04", "Appointment detail and decisions", "Counselor", "FR-GUID-09 to FR-GUID-11"],
            ["WF-A-24", "Analytics overview", "Administrator", "FR-ANLYT-01 to FR-ANLYT-03"],
        ],
        widths=[0.9, 2.1, 1.0, 2.5],
        caption="Wireframes reproduced in this section")

    # ------------------------------------------------------------------
    doc.h2("3.5 System Mockup (Figma)")
    doc.p(
        "The high-fidelity mockup set contains 46 screens derived from the same page specifications "
        "as the wireframes: the information architecture is identical and only the shell and the "
        "visual tokens change. Every value in the mockup stylesheet is copied from "
        "`public/css/app.css`, so the mockups and the running application describe the same "
        "interface.")
    doc.note(
        "No Figma file exists for this project, and none is claimed. The mockups were produced as "
        "static HTML and CSS generated from declarative page specifications and rendered to PNG with "
        "headless Chrome; the design system page in the mockup deliverable documents the tokens the "
        "same way a Figma library would. The consequence for Section 5.1 is that no Figma URL can be "
        "supplied.")
    doc.p(
        "The visual language is institutional rather than decorative. Deep green carries structure "
        "and primary action, gold is reserved for emphasis and the active navigation marker, and "
        "status never depends on colour alone: every badge pairs a colour role with the stored word. "
        "Table 3.4 lists the tokens.")
    doc.table(
        ["Token", "Value", "Where it is used"],
        [
            ["brand-deep", "#062e1d", "Sidebar surface, footer, cover of this document"],
            ["brand-dark", "#083f28", "Headings, table captions"],
            ["brand", "#0b5d3b", "Primary buttons, links, focus rings, borders"],
            ["brand-100", "#d5e7dd", "Dividers and quiet borders"],
            ["brand-50", "#eaf3ee", "Selected rows and informational panels"],
            ["accent", "#c9a227", "Active navigation rule, emphasis that is not status"],
            ["canvas", "#f4f7f5", "Page background behind cards"],
            ["paper", "#ffffff", "Cards, tables and forms"],
            ["text", "#1f2937", "Body copy"],
            ["muted", "#6b7280", "Metadata, helper text and empty states"],
            ["ok / warn / danger / info", "#047857 / #b45309 / #b91c1c / #1d4ed8",
             "Status badges, always accompanied by a word"],
        ],
        widths=[1.35, 1.75, 3.4],
        caption="Design tokens shared by the mockups and the application")
    doc.p(
        "Typography uses one family, Segoe UI first because it is present on the university's "
        "Windows machines, on a single scale from a 22-pixel page title to 10.5-pixel uppercase "
        "labels, with a monospace face reserved for identifiers, times and weights. Spacing follows "
        "a 4-pixel unit, radii are 6 pixels for controls, 10 for inputs and notices and 14 for cards, "
        "and only two shadows are used: a hairline for resting cards and a larger one for overlays.")
    doc.figure("figures/mockups/MK-01--desktop.png",
               "Mockup MK-01 — Login screen in the high-fidelity visual language")
    doc.p(
        "The mockup of the login screen shows how the tokens combine: a quiet canvas, one white "
        "card, one primary button, and no decorative imagery that would compete with the form.")
    doc.figure("figures/mockups/MK-02--desktop.png",
               "Mockup MK-02 — Application shell: sidebar, top bar and content frame")
    doc.p(
        "Navigation is fixed and identical on every authenticated screen. The sidebar mirrors the "
        "route groups so that a user can predict where a function lives, groups its entries under "
        "Main, Management, Guidance and Services, Insights and System, and marks the active entry "
        "with a gold rule. The top bar carries the role-scoped search field, the notification count "
        "and the account chip; it is the only place where the signed-in identity is shown.")
    doc.figure("figures/mockups/MK-19--desktop.png",
               "Mockup MK-19 — Browse scholarships")
    doc.p(
        "List screens use the same table grammar: a filter row above, a header row that repeats if "
        "the table breaks across pages, status as a badge in its own column, and a single row action "
        "at the right edge. Numeric columns use tabular figures so that amounts align.")
    doc.figure("figures/mockups/MK-31--desktop.png",
               "Mockup MK-31 — Appointment slots and booking")
    doc.p(
        "Booking shows the constraint set rather than hiding it: the chosen date and type, the "
        "counselor, the generated slots and the reason field that the counselor will read. A slot "
        "that is no longer available disappears from the list instead of failing on submit.")
    doc.figure("figures/mockups/MK-40--desktop.png",
               "Mockup MK-40 — Analytics overview with period filter")
    doc.p(
        "Charts are descriptive and always state their series and period. The four summary tiles "
        "reconcile with the series beside them, so a reader can check the arithmetic instead of "
        "trusting the layout.")
    doc.figure("figures/mockups/MK-46--desktop.png",
               "Mockup MK-46 — The same screen at 1440, 1024 and 390 pixels")
    doc.p(
        "The responsive captures demonstrate the rule the application follows: the sidebar is "
        "persistent above 1024 pixels and collapses into a drawer below it, tables restack below 768 "
        "pixels, and the search field is dropped at phone width where it would compete with the page "
        "title. The delivered application applies the same breakpoints, which Section 4 does not "
        "attempt to demonstrate because its screenshots are desktop captures.")
