# T09 Service Request Submission Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Validate and persist new Pending Service Requests for existing Active Residents.

**Architecture:** Preserve the T08 plain PHP domain model. Laravel validation handles intrinsic rules, a query-builder repository handles storage, and a submission service uses the existing ResidentRepository to verify eligibility before saving.

**Tech Stack:** PHP ^8.3, Laravel ^13.8, SQLite, PHPUnit, Laravel Pint. No additional dependencies.

**Spec:** `docs/superpowers/specs/2026-10-01-t09-service-request-submission-design.md`

## Global Constraints

- All T09 work uses `feature/t09-service-request-submission`, created from updated main.
- Reuse the T08 domain model and existing Resident repository.
- New request ID must be exactly null; persistence generates the ID.
- Pending is the only valid initial status; Resident status must equal Active.
- No request UI, HTTP endpoint, controller, search/listing, editing, deletion, status transitions, or Resident reactivation is included.
- Preserve all previous tests and Resident data.
- Required feature commit: `feat: validate and submit service requests`.
- Required PR title: `T09 - Validate and Submit Service Requests`; base main in the student's classroom repository.

## Review Focus

1. Assigned ID zero must fail just like any other non-null ID (Task 1).
2. Impossible leap dates must fail while a real leap date passes (Task 1).
3. Persisting two requests for one Resident must yield distinct identities (Task 2).
4. Resubmitting a saved object must fail without creating another row (Task 3).
5. A database exception must propagate rather than become an eligibility failure (Task 3).

## Task 1: Intrinsic validator

**Files:** Create `app/Services/ServiceRequestValidator.php` and `tests/Unit/ServiceRequestValidatorTest.php`.

**Interfaces:** Consumes existing ServiceRequest public properties. Produces `validate(ServiceRequest $request): Illuminate\Contracts\Validation\Validator` and `isValid(ServiceRequest $request): bool`.

- [ ] Write failing tests extending Tests\TestCase for Laravel's validator facade. Use a valid request with residentId 25, serviceType `Barangay Clearance`, description `Request for employment requirement`, dateRequested `2026-09-15`, default Pending, and null id. Assert passes. For each invalid field assert fails and `errors()->has($field)` using camelCase field names. Cases: id 0 and 10; residentId 0 and -1; serviceType empty and space/tab/newline; description empty and space/tab/newline; dateRequested empty, `invalid`, `2026-02-30`, `2025-02-29`, and `2026-9-15`; status empty, Completed, Cancelled, and In Progress. Assert `2024-02-29`, past/future dates, and an arbitrary meaningful service type pass. Assert multiple invalid fields all appear in errors.
- [ ] Run `php artisan test --filter=ServiceRequestValidatorTest`; expect failure because the validator class does not exist.
- [ ] Implement validator with Laravel Validator::make; map the six existing properties without database lookup. Require id null with a custom rule, residentId integer/min:1, nonblank required string fields, date_format:Y-m-d, and exact Pending using Rule::in. Keep original field values unchanged.
- [ ] Run `php artisan test --filter=ServiceRequestValidatorTest`; expect all tests pass.
- [ ] Stage only these files and commit `feat: validate service request information`.

## Task 2: Schema and repository

**Files:** Create `database/migrations/2026_10_01_000000_create_service_requests_table.php`, `app/Repositories/ServiceRequestRepository.php`, and `tests/Feature/ServiceRequestRepositoryTest.php`.

**Interfaces:** Produces `save(ServiceRequest $request): ServiceRequest` and `findById(int $id): ?ServiceRequest`. Consumes the existing ServiceRequest constructor; database-to-domain mapping is snake_case to camelCase.

- [ ] Write integration tests using RefreshDatabase and an Active Resident saved through ResidentRepository. Assert a request starts with null ID, save returns a positive ID, and a fresh repository retrieves all six equal fields. Assert findById for 99999 returns null, two requests for the same Resident get distinct IDs, and different Residents remain correctly linked. Assert saving an assigned-ID request throws InvalidArgumentException and creates no row. Inspect table columns to ensure only the six required columns exist.
- [ ] Run `php artisan test --filter=ServiceRequestRepositoryTest`; expect missing repository/table failures.
- [ ] Create the migration with id, foreignId resident_id constrained to residents with restrictive deletion, text service_type, text description, date date_requested, and string status default Pending. Implement down with dropIfExists. Omit timestamps and Resident personal fields.
- [ ] Implement repository save using DB::table('service_requests')->insertGetId, rejecting non-null id before insert. Assign the returned ID to the domain object. Implement findById using the query builder, hydrate the existing constructor and set id; return null for absent rows.
- [ ] Run `php artisan test --filter=ServiceRequestRepositoryTest`; expect all tests pass.
- [ ] Stage only these files and commit `feat: persist service requests`.

## Task 3: Submission orchestration and durable persistence

**Files:** Create `app/Services/ServiceRequestSubmissionResult.php`, `app/Services/ServiceRequestSubmissionService.php`, and `tests/Feature/ServiceRequestSubmissionServiceTest.php`.

**Interfaces:** Consumes Task 1 validator, Task 2 repository, and ResidentRepository::findById(int): ?Resident. Produces submission service constructor `(ServiceRequestValidator $validator, ResidentRepository $residentRepository, ServiceRequestRepository $serviceRequestRepository)` and `submit(ServiceRequest $request): ServiceRequestSubmissionResult`. Result exposes bool success, ?ServiceRequest serviceRequest, array errors, and ?string failureReason. Factories: `successful(ServiceRequest $request): self` and `failed(string $reason, array $errors): self`.

- [ ] Write integration tests using a temporary file-backed SQLite database following ResidentRegistrationServiceTest setup/teardown. Assert valid Active submission succeeds, returns the same persisted information with a generated ID, null failureReason and empty errors. Compare all Resident attributes before and after. Retrieve the request through a second repository after DB::purge to prove durable storage. Assert Pending and residentId are preserved.
- [ ] Write rejection tests for each validator category, nonexistent Resident, and Inactive Resident: success false, result serviceRequest null, input id unchanged, request count unchanged, and reason exactly validation_failed, resident_not_found, or resident_inactive. Check field errors for validation and readable errors for eligibility. Confirm the Inactive Resident is unchanged and remains retrievable/searchable using the existing repository. Assert resubmitting a successfully stored object fails validation and does not increase row count.
- [ ] Add mock-based tests asserting invalid requests do not query ResidentRepository or call save, and missing/Inactive Residents do not call save. Make a save mock throw RuntimeException and assert it propagates. This proves operation order and avoids hiding persistence failures.
- [ ] Run `php artisan test --filter=ServiceRequestSubmissionServiceTest`; expect missing service/result failures.
- [ ] Implement result factories with successful and failed states as specified. Implement submit: validate; return validation_failed with errors()->toArray on failure; find Resident; return resident_not_found if null; return resident_inactive unless status equals Resident::STATUS_ACTIVE; save; return successful result. No Resident writes, ID generation, or automatic status changes in the service.
- [ ] Run `php artisan test --filter=ServiceRequest`; expect all T08 and T09 request tests pass.
- [ ] Stage only these files and commit `feat: validate and submit service requests`.

## Task 4: Regression, review, and delivery

**Files:** Review all T09 files; record verification in `docs/superpowers/plans/2026-10-01-t09-service-request-submission.md` by marking completed steps. No unrelated source edits.

**Interfaces:** Existing `/` and `/health` remain operational; all Starter through T09 tests remain enabled.

- [ ] Run Pint on the explicit five new implementation PHP files and three new test files. Use `php vendor/bin/pint` followed by their paths; inspect its diff for unexpected changes. Run `git diff --check`.
- [ ] Run `php artisan test`; expect all baseline and T09 tests pass with no skipped/disabled tests. Record actual test/assertion totals. If formatting caused changes, rerun focused tests and commit only intended formatting changes.
- [ ] Start `php artisan serve --host=127.0.0.1 --port=8099`, request `/` and `/health` using Invoke-WebRequest/Invoke-RestMethod, assert HTTP 200 and health status ok, then stop the process. If port is occupied, use another available local port. Use a managed command session so no visible helper window is needed.
- [ ] Inspect `git status --short`, `git diff main --stat`, and `git diff main` against the spec. Review validator independence, save order, database-generated IDs, preserved fields, eligibility distinctions, and absence of T10 work. Obtain a whole-branch code review via the execution skill and resolve actionable findings before delivery.
- [ ] Stage and review only intended remaining T09 files with `git diff --staged`. Ensure the required feature commit is present and the working tree is clean.
- [ ] Push `feature/t09-service-request-submission` to origin. Create a PR in UPHSL/csms-php-missanning against main with the required title, describing all components, failure outcomes, tests, regression totals, application/health evidence, and actual known issues. Use a body file for gh and attach the created PR to this chat with attach_artifact.
- [ ] Review the actual PR diff and checks; merge only after successful verification and review. Update local main using `git switch main` and `git pull --ff-only origin main`, then confirm clean/up-to-date state.
- [ ] Provide the classroom repository URL and merged PR URL for the student to submit to Moodle, plus a brief explanation of the implementation.

## Plan self-review

The four tasks cover the approved spec and all required T09 scenarios. Repository and service interfaces match across tasks. Review-focus cases are assigned to their owning tests. No dependencies, new model, presentation feature, or T10 workflow is introduced. Live-server verification remains explicit because the baseline checks so far used framework HTTP tests.
