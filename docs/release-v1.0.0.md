# CSMS v1.0.0 Release Notes

## 1. Release

| Item | Value |
| --- | --- |
| Application | Community Services Management System |
| Release | v1.0.0 |
| Release Type | Final Course Release |

## 2. Release Summary

CSMS v1.0.0 provides browser-based management of residents and their service requests. It supports resident registration and lifecycle management, controlled request processing, searchable records, and a dashboard that summarizes current community service work. The application identifies its release version consistently in the interface and the `/health` response.

## 3. Resident Management Capabilities

- Register residents with validated name, address, contact number, and email information.
- List and search persisted residents, including inactive records.
- Update editable resident information without changing the resident ID or status through the edit form.
- Deactivate residents while retaining their records and existing service requests.
- Treat SQL wildcard characters in resident name searches as literal input.

## 4. Service Request Management Capabilities

- Submit validated service requests for active residents. New requests begin as Pending.
- List, search, filter by status, and view request details.
- Process requests through the controlled Pending → In Progress → Completed workflow. Pending and In Progress requests may also be cancelled; invalid and terminal-state transitions are rejected.
- Preserve existing request records when a resident becomes inactive, while rejecting new submissions for that resident.

## 5. Dashboard Capabilities

The dashboard shows total, active, and inactive resident counts; total service requests and counts by status; Open Service Requests as Pending plus In Progress; and up to five recent service requests with links to their details.

## 6. Quality and Hardening

T15 added checks for malformed route identifiers, rejected updates, read-only request safety, resident eligibility across modules, and literal wildcard searches. T16 added a persisted end-to-end scenario covering registration, request workflow, deactivation, eligibility, and dashboard reconciliation. The final release identity is checked automatically, and the complete suite passed twice in immediate succession.

## 7. Final Verification

| Check | Result |
| --- | --- |
| Final Regression Run 1 | PASS — 209 tests, 972 assertions (`php artisan test --compact`) |
| Final Regression Run 2 | PASS — 209 tests, 972 assertions, immediately after Run 1 |
| Application Startup | PASS — Laravel server started normally |
| `/health` | PASS — HTTP 200; application identity, version `1.0.0`, and `ok` status; no sensitive diagnostics |
| Dashboard | PASS — resident and request metrics, Open count, and recent requests displayed |
| Resident Management | PASS — listing, search, invalid and valid registration, update, and deactivation checked |
| Service Request Management | PASS — submission, eligibility, listing, search, filtering, details, valid workflow, and invalid transition checked |
| Read-only Safety and Navigation | PASS — repeated GET requests left persisted rows unchanged; management links worked |
| Final Release Version | PASS — `v1.0.0` visible on management pages; `/health` reports `1.0.0` |

Manual mutation checks used an isolated temporary SQLite database, leaving the existing local records untouched.

## 8. Known Limitations

No known release-blocking limitations remain within the defined CSMS v1.0.0 scope.
