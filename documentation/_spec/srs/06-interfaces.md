## 6. External Interface Requirements

### 6.1 Interface Requirements

| ID | Interface | Requirement |
| --- | --- | --- |
| EIR-01 | Web browser interface | The system shall be operable through a standards-compliant browser with JavaScript enabled, using server-rendered HTML and a single stylesheet, and shall degrade gracefully where optional scripting is unavailable. |
| EIR-02 | Email transport | The system shall deliver one-time passcodes and notification email through a configured SMTP server, using a queued job for notification mail. |
| EIR-03 | Database | The system shall persist all records in a MariaDB or MySQL database accessed through the framework's database layer, using parameterised queries throughout. |
| EIR-04 | File system | The system shall store uploaded documents on a private local disk and shall require an application route for every read. |
| EIR-05 | CSV export | The system shall stream analytics data as a comma-separated file with a header row, suitable for opening in a spreadsheet application. |
| EIR-06 | Scheduler | The system shall expose a console command that sends appointment reminders and is intended to be invoked by the operating system scheduler. |
| EIR-07 | Queue worker | The system shall process notification email through the database queue, requiring an operator-run worker. |

### 6.2 User Interface Conventions

The interface follows one shell for all authenticated users: a persistent left sidebar of role-appropriate links, a top bar carrying the current page context, search, notification count and account menu, and a main content region. Every page begins with a page header stating the purpose of the screen and offering its primary action. List pages present filters above results, results in a bordered table with a header row, and pagination below. Forms place labels above inputs, show validation messages directly beneath the offending field, and place the primary submission action at the end of the form with a secondary cancel action beside it. Destructive actions use a distinct danger treatment and require confirmation.

### 6.3 Screen Inventory

The system comprises fifty-five distinct application pages, of which fifty-two are role-facing screens and three are authentication screens, together with a set of reusable components. The verification of each screen against its route and its requirement identifiers is recorded in the wireframe index and in the traceability matrix.

### 6.4 Error and Feedback Interface

Invalid input returns the form with field-level messages and the previously entered values. An unauthorised request returns HTTP 403 with an explanatory page. A request for a record that does not exist, or a scholarship that is not open, returns HTTP 404. Workflow violations return the record page with an explanatory message rather than a generic failure. Successful operations return to the relevant page with a confirmation banner.

### 6.5 Accessibility Interface

Pages expose a skip link to the main region, semantic landmarks, labelled navigation, visible keyboard focus, and status regions announced to assistive technology. Animation is suppressed when the operating system reports a reduced-motion preference. Colour is never the sole carrier of meaning: every status is also labelled.
