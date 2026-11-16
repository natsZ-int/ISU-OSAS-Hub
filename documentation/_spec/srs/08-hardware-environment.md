## 8. Sample Hardware Requirements

The following figures describe a deployment adequate for an office-scale installation serving one campus. They are samples for planning, not measured minimums.

| ID | Component | Sample specification | Rationale |
| --- | --- | --- | --- |
| HW-01 | Application server | Quad-core x86-64 processor, 8 GB RAM, 100 GB SSD | Serves PHP requests, queue worker and scheduler on one host |
| HW-02 | Database server | Same host or separate host with 4 GB RAM and SSD storage | MariaDB performance is bound by disk latency for write-heavy audit and notification tables |
| HW-03 | Document storage | 50 GB allocated to the private storage volume, expandable | Documents are small but numerous; five megabytes is the per-file ceiling |
| HW-04 | Client device | Dual-core processor, 4 GB RAM, 1366×768 display or larger | Sufficient for a modern browser rendering a server-side interface |
| HW-05 | Client device, mobile | Any current smartphone with a 360 px viewport or wider | The interface collapses to a single column at this width |
| HW-06 | Network | 5 Mbps sustained per concurrent office user; internet access for email relay | Pages are server-rendered and text-heavy, so bandwidth demand is modest |
| HW-07 | Backup target | Storage equal to twice the database size plus the document volume | Supports a rolling backup of records and files |
| HW-08 | Uninterruptible power supply | Sized for the server and network equipment | Prevents corrupt writes during unexpected power loss |

> Capacity note. The dominant storage growth is not student records but uploaded documents and audit entries. A scholarship cycle with one thousand applicants, each uploading four documents at an average of one megabyte, consumes roughly four gigabytes per cycle.

<!-- pagebreak -->

## 9. Software and Operating Environment

### 9.1 Server Environment

| Component | Requirement | Notes |
| --- | --- | --- |
| Operating system | Windows or Linux | Development is performed on Windows with XAMPP; production may use either |
| Web server | Apache 2.4 or Nginx | Document root must be the `public` directory |
| PHP | 8.2 or later | Required by Laravel 12 |
| PHP extensions | OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, BCMath | The standard Laravel extension set |
| Database | MariaDB 10.4 or later, or MySQL 8 | Schema name `isu_osas_hub` in the delivered configuration |
| Composer | 2.x | Installs server-side dependencies |
| Queue worker | Framework worker process | Required for notification email |
| Scheduler | Operating system scheduler invoking the framework scheduler | Required for appointment reminders |
| SMTP relay | Any standards-compliant SMTP service | Required for OTP and notification email |

### 9.2 Client Environment

| Component | Requirement |
| --- | --- |
| Browser | Current Chrome, Edge, Firefox or Safari |
| JavaScript | Enabled; used only for progressive enhancement |
| Cookies | Enabled; required for session continuity |
| Screen | 360 px minimum width; 1366 px or wider recommended for administrative work |

### 9.3 Development Tooling

| Tool | Role |
| --- | --- |
| Composer | Server-side dependency management |
| Node.js and npm | Present for asset tooling; the delivered interface does not require a build step |
| Visual Studio Code | Source editing |
| PHPUnit | Automated test execution |
| XAMPP | Local Apache, PHP and MariaDB bundle |

### 9.4 Configuration

Runtime configuration is supplied through environment variables rather than source edits. The delivered environment file targets MariaDB with an SMTP mail transport; the shipped example file targets a file-backed SQLite database with log-based mail, which is convenient for fresh development but is not the deployed configuration. Two academic variables, the current academic year and the current semester, govern enrollment verification and must be updated at the start of every term.

> Observation. The asset dependencies include a utility-first CSS framework that the delivered layout does not use: the interface loads a single hand-written stylesheet directly. Documentation, wireframes and mockups therefore follow the hand-written stylesheet as the design system of record.

### 9.5 Deployment Prerequisites

A working installation requires the database schema to be migrated, the three roles and the initial administrator to be seeded, the storage link or private disk to be writable by the web server, an SMTP credential to be configured, and both the queue worker and the scheduler to be running. Guidance appointment types must be created by an administrator before students can book, and scholarship records must be authored before students see anything in the catalogue.
