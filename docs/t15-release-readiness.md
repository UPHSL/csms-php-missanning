# T15 Release Readiness

## 1. Developer Information

| Item | Value |
| --- | --- |
| Name | Jullie Anne A. Temporosa |
| GitHub Username | missanning |
| Technology Stack | PHP 8.3, Laravel 13, SQLite for isolated verification |
| T15 Branch | `feature/t15-system-hardening` |

## 2. Release Candidate Baseline

T14 was merged into the personal repository's `main` before T15 began. Local `main` was fast-forwarded to that merge before the T15 branch was created. The pre-T15 suite passed **202 tests and 886 assertions** using `php artisan test --compact`. The application was started locally; `/health`, `/dashboard`, `/residents`, and `/service-requests` each returned HTTP 200. The Dashboard, Resident Management, and Service Request Management pages loaded in the browser.

The local server was initially stopped when baseline checks began. It started normally; this was not an application failure. An initial test command in the restricted execution environment could not pass PHPUnit's file-readability check for `vendor/autoload.php`. The same baseline suite passed when run with normal filesystem access.

| Baseline item | Result |
| --- | --- |
| Application startup | PASS after starting the local server |
| `/health` | PASS, HTTP 200 |
| Dashboard | PASS, HTTP 200 and browser page loaded |
| Resident Management | PASS, HTTP 200 and browser page loaded |
| Service Request Management | PASS, HTTP 200 and browser page loaded |
| Starter through T14 tests | PASS, 202 tests and 886 assertions |

## 3. Hardening Changes

| Area | Issue or Risk Found | Action Taken | Files Changed | Test/Evidence |
| --- | --- | --- | --- | --- |
| Full regression behavior | Reviewed; no existing test failure identified. | Ran the complete pre-T15 suite and the expanded suite twice. | None | Baseline 202/886; both T15 runs 207/929. |
| Invalid and missing input | Reviewed; no uncontrolled missing-record behavior identified. | Added malformed-route checks with persisted-state assertions. | `tests/Feature/SystemHardeningTest.php` | Malformed Resident and Service Request paths return 404 without changing either domain table. |
| Persistence integrity | Reviewed; rejected updates needed stronger cross-field evidence. | Added a test that submits a valid field change together with an invalid contact number and a forged status. | `tests/Feature/SystemHardeningTest.php` | The entire persisted Resident row remains unchanged. |
| HTTP and mutation safety | Reviewed; no GET mutation identified. | Added repeated cross-module GET checks against snapshots of both domain tables. | `tests/Feature/SystemHardeningTest.php` | Dashboard, Resident, and Service Request reads leave both tables unchanged; browser refresh did not repeat submissions. |
| Query and database safety | Resident name search treated user-entered `%` and `_` as SQL wildcards, which could return unrelated records. | Escaped `!`, `%`, and `_` in the bound LIKE pattern. Kept database parameter binding. | `app/Repositories/ResidentRepository.php`; `tests/Feature/SystemHardeningTest.php` | The new literal-wildcard search test failed before the fix and passed afterward. Existing search tests still pass. |
| Workflow and lifecycle protection | Reviewed; no rule defect identified. | Added an integrated test for deactivation, submission eligibility, existing request processing, and dashboard counts. | `tests/Feature/SystemHardeningTest.php` | Missing/inactive submissions and invalid/terminal transitions were rejected; valid transitions and history remained available. |
| Interface and navigation consistency | Reviewed; no broken navigation found. | Verified links among the three main modules, form feedback, details, filtering, and controlled 404 pages. | None | Browser verification on the existing app and an isolated local database. |
| Test reliability | Reviewed; no order-dependent result identified. | Used the existing isolated database test pattern and ran the full suite twice in immediate succession. | `tests/Feature/SystemHardeningTest.php` | Both full runs passed 207 tests and 929 assertions. |
| Repository and release hygiene | Reviewed; no tracked runtime artifact or token-like secret found. | Removed the temporary manual-verification database after testing. Left unrelated local files out of the T15 change set. | None | Tracked-file and debug-pattern scans; final T15 code commit contains only the repository fix and five tests. |

The change does not add a business capability, status, field, or dashboard metric. Existing domain services remain responsible for validation and lifecycle rules.

## 4. Five New T15 Hardening Tests

All five tests are in `tests/Feature/SystemHardeningTest.php`.

| Test Name | Category | What It Verifies | Why It Adds Value Beyond Existing Tests |
| --- | --- | --- | --- |
| `test_malformed_route_identifiers_return_not_found_without_changing_records` | Invalid or malformed input | Non-numeric Resident and Service Request paths return controlled 404 responses; both persisted tables remain unchanged. | Covers malformed browser paths across both domains, beyond existing missing numeric-ID checks. |
| `test_invalid_update_cannot_partially_change_an_inactive_resident_or_reactivate_them` | Rejected mutation protects persistence | An invalid update with otherwise valid changes and a forged status leaves the complete inactive Resident row unchanged. | Tests partial-update atomicity and status spoofing together, with a full-row comparison. |
| `test_repeated_cross_module_get_requests_leave_both_tables_unchanged` | Read-only request safety | Repeated Dashboard, Resident, and Service Request GETs leave both domain tables unchanged. | Checks several read paths together and compares complete persisted snapshots, not only HTTP responses. |
| `test_deactivation_blocks_new_submission_but_existing_request_remains_processable_and_reported` | Cross-feature regression | Deactivation blocks a new request, while a prior request can still advance and the Dashboard reports both states correctly. | Connects Resident lifecycle, Service Request eligibility/workflow, and Dashboard aggregation in one scenario. |
| `test_resident_search_treats_sql_wildcards_as_literal_text` | Repeatability or boundary behavior | Searching for `%` matches only a name containing a literal percent sign and does not change persisted records. | Covers an input boundary that previously produced over-broad search results. |

## 5. Full Regression Test Results

Command: `php artisan test --compact`

| Run | Result | Total |
| --- | --- | --- |
| Run 1, T15 branch | PASS | 207 tests, 929 assertions |
| Run 2, immediately after Run 1 | PASS | 207 tests, 929 assertions |
| Post-merge run on updated local `main` | PASS | 207 tests, 929 assertions |

No previous test was deleted, disabled, or weakened. The five new tests passed separately before the two full runs.

## 6. Manual Release Verification

Browser mutation checks used a fresh, isolated local SQLite database and a server on port 8001. The temporary database and server were removed/stopped afterward. Read-only checks also used the existing local app on port 8000. Test records in the existing local database were not changed.

| Verification | Result | Notes |
| --- | --- | --- |
| Application starts | PASS | Laravel server started on both local verification ports. |
| `/health` works | PASS | HTTP 200 after T15 and after the merge. |
| Dashboard loads | PASS | Empty state and persisted counts displayed; HTTP 200. |
| Resident listing/search works | PASS | Active/inactive records remained visible; literal `%` search returned no unrelated records; no-match state displayed. |
| Valid Resident registration works | PASS | Isolated Resident received ID 1 and Active status; refresh did not duplicate it. |
| Invalid Resident registration is rejected | PASS | Blank first name and invalid contact number displayed field feedback; record count stayed at one. |
| Resident update works | PASS | Address changed while ID and status stayed unchanged. A later invalid update showed feedback and a refresh restored the original persisted values. |
| Resident deactivation works and record remains visible | PASS | Status changed to Inactive; record remained searchable. A repeated direct deactivation was handled safely. |
| Valid Service Request submission for Active Resident works | PASS | Request received ID 1, the correct Resident ID, and Pending status; refresh did not duplicate it. |
| Missing/Inactive Resident submission is rejected | PASS | Both cases showed eligibility feedback and did not add a request. |
| Service Request search/filter/details work | PASS | Listing, text search, status filter, combined search/filter, no-match state, and details were checked. |
| Valid Service Request workflow works | PASS | One request moved Pending → In Progress → Completed; another moved Pending → Cancelled. |
| Invalid/terminal transition is rejected | PASS | Direct Pending → Completed and Cancelled → In Progress attempts showed transition errors and preserved status. |
| Dashboard reflects Resident/Service Request changes | PASS | In Progress increased Open; completion reduced Open; deactivation changed Active/Inactive counts without changing total. |
| Read-only GET operations do not modify persistence | PASS | Repeated browser reads and refreshes were checked; the new cross-module test compares complete persisted snapshots. |

Missing Service Request IDs and malformed Resident path IDs displayed controlled 404 pages without internal error details. Navigation among Dashboard, Residents, and Service Requests worked through page links.

## 7. Repository Hygiene Review

| Check | Result | Notes |
| --- | --- | --- |
| Debug output | PASS | No tracked `dd`, `dump`, `var_dump`, `print_r`, or console debug calls found in application, route, view, or test code. |
| Temporary files | PASS | The isolated manual-verification database was removed. |
| Generated build/runtime artifacts | PASS | No tracked log, SQLite, vendor, node_modules, or build output files found. |
| Accidental local database files | PASS | No SQLite database is tracked. |
| Commented-out abandoned code | PASS | No abandoned implementation block identified during the reviewed T15 paths. |
| Secrets/credentials | PASS for tracked repository files | No token-like secret found in tracked source. The local `origin` URL now contains no embedded credential, Git Credential Manager is configured, and the developer reports running GitHub's targeted token revocation command. |
| Broken navigation/links | PASS | Main module links and recent-request details links worked in the browser. |
| Direct SQL parameter binding | PASS | Search values are bound as parameters; Dashboard counts remain database-level aggregate queries. |

An unrelated untracked local tool directory was intentionally left outside the T15 change set.

## 8. Known Limitations

No known release-blocking limitations identified within the defined CSMS scope.

## 9. Release Candidate Statement

**READY FOR T16 FINAL EXAM**
