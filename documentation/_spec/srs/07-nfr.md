## 7. Non-Functional Requirements

Non-functional requirements constrain how the functional requirements are delivered. They are grouped by quality attribute and each is stated in a form that can be verified by inspection, measurement or test.

{{NFR_STATS}}

### 7.1 Performance (NFR-PERF)

Performance targets assume the reference hardware in Section 8, a seeded dataset representative of one academic year, and a single application server. They are expressed as targets rather than measurements; verification is described in Section 14.

{{REQ_TABLE:NFR-PERF}}

### 7.2 Security (NFR-SEC)

Security is implemented through server-side authorization, hashed credentials, private file storage, request throttling and an audit trail. The system does not implement multi-factor authentication for staff accounts; the one-time passcode is an email-ownership check performed once per account, not a second factor at every login.

{{REQ_TABLE:NFR-SEC}}

### 7.3 Usability (NFR-USA)

Usability requirements reflect the operational reality of an office counter: staff process records in volume, so consistency and predictability matter more than novelty. Every list behaves the same way, every status looks the same way, and every irreversible action asks first.

{{REQ_TABLE:NFR-USA}}

### 7.4 Reliability and Integrity (NFR-REL)

The dominant reliability risk in this system is a partially applied workflow change: an application moved to approved while a document remains unverified, or a reminder dispatched twice by an overlapping scheduler. The requirements below address that class of failure.

{{REQ_TABLE:NFR-REL}}

### 7.5 Maintainability (NFR-MAINT)

Maintainability requirements protect the layered structure of the application so that a change to a business rule does not require editing views, and a change to a status value does not require searching the codebase for string literals.

{{REQ_TABLE:NFR-MAINT}}

### 7.6 Compatibility (NFR-COMPAT)

{{REQ_TABLE:NFR-COMPAT}}

### 7.7 Portability and Responsiveness (NFR-PORT)

The interface is deliberately built without a client-side framework or build step, which keeps deployment to a single upload and keeps the application usable on the low-bandwidth connections common in provincial campuses.

{{REQ_TABLE:NFR-PORT}}

### 7.8 Accessibility (NFR-ACC)

{{REQ_TABLE:NFR-ACC}}

### 7.9 Data Quality and Retention (NFR-DATA)

{{REQ_TABLE:NFR-DATA}}
