# T09: Validate and Submit Service Requests

## Purpose and scope

Allow community services staff to submit a valid new Service Request for an existing Active Resident. Reuse the T08 domain model and existing Resident repository. Reject invalid requests before persistence, preserve Pending status and all request information, and leave the Resident unchanged.

This specification follows the supplied T09 activity and the design approved in chat. A separate PHP-specific coding guide has not been supplied; existing repository conventions determine component names and interfaces.

No request UI, HTTP endpoint, controller, search/listing, editing, deletion, status transitions, or Resident reactivation is included.

## Verified baseline and workflow

- T08 was merged through PR #11, commit `d505a76`.
- Local main was updated from origin and was clean.
- The baseline suite passed: 68 tests, 180 assertions. This includes application home-page and `/health` HTTP tests.
- All T09 work uses `feature/t09-service-request-submission`, created from updated main.
- Review the specification, then prepare and review an implementation plan before implementation.
- Complete the feature with the required commit `feat: validate and submit service requests`, push, PR review, merge, and local main update. The required PR title is `T09 - Validate and Submit Service Requests`; base is main in the student's classroom repository.

## Architecture and alternatives

Use Laravel validation and the query builder with the existing plain PHP `App\Models\ServiceRequest`. This preserves the T08 constructor, camelCase properties, and domain tests while keeping SQL concerns in a repository.

Converting the model to Eloquent would require changing the existing constructor and domain contract. Adding a separate Eloquent request model would introduce a second representation unnecessarily. The query-builder repository is the selected approach.

## Intrinsic validation

Add `App\Services\ServiceRequestValidator` with `validate(ServiceRequest): ValidatorContract` and `isValid(ServiceRequest): bool`, following ResidentValidator conventions.

Validate the following independently of any Resident lookup:

| Property | Rule |
| --- | --- |
| id | Must be exactly null before submission; zero and every assigned integer fail. |
| residentId | Must be a positive integer. |
| serviceType | Required meaningful text; empty and whitespace-only fail. No allowlist or length limits. |
| description | Required meaningful text; empty and whitespace-only fail. No length limits. |
| dateRequested | Required strict calendar date in YYYY-MM-DD form; impossible dates fail. Past and future dates are permitted. |
| status | Must equal ServiceRequest::STATUS_PENDING exactly. |

Return field-specific validation errors using camelCase domain property names. The existing constructor enforces non-null integer/string types; absent text is represented as an empty string and an absent numeric identifier as zero at this domain boundary. Do not loosen the existing model merely to permit invalid PHP types. Tests must cover blank dates, malformed dates, and impossible dates.

## Persistence

Add a migration creating `service_requests` with exactly the six required domain columns:

- `id`: database-generated primary key.
- `resident_id`: foreign key referencing residents.id, with restrictive deletion.
- `service_type`: text.
- `description`: text.
- `date_requested`: date preserving YYYY-MM-DD.
- `status`: string with Pending default.

No Resident personal information is copied. Using text for service type and description avoids inventing length limits.

Add `App\Repositories\ServiceRequestRepository`:

- `save(ServiceRequest): ServiceRequest` inserts a new row using `insertGetId`, excluding id from the supplied insert data, assigns the returned database ID, and returns the persisted request. An already assigned ID is rejected defensively; save is not an update operation.
- `findById(int): ?ServiceRequest` queries the row and maps snake_case columns into the existing model constructor, then sets the stored id. Missing rows return null.

The submission service owns validation and eligibility checks; the repository owns database operations. Normal workflow failures must be resolved before save. Unexpected database errors propagate as exceptions rather than being mislabeled as validation or eligibility failures.

## Submission and result

Add `App\Services\ServiceRequestSubmissionService`, injecting ServiceRequestValidator, the existing ResidentRepository, and ServiceRequestRepository. Its public operation is `submit(ServiceRequest): ServiceRequestSubmissionResult`.

Execute in this order:

1. Validate intrinsic fields. On failure, return validation failure with field messages.
2. Retrieve the Resident with ResidentRepository::findById.
3. If absent, return Resident-not-found.
4. If status is not exactly Resident::STATUS_ACTIVE, return Resident-inactive/ineligible. The established persisted lifecycle uses Active and Inactive.
5. Save the request, preserving all fields and Pending status.
6. Return success with the request carrying its database-generated ID.

Do not mutate or save the Resident. All failed workflow outcomes leave the incoming request ID unchanged and create no request row.

Add `App\Services\ServiceRequestSubmissionResult` containing `success` (bool), `serviceRequest` (nullable ServiceRequest), `errors` (array), and `failureReason` (nullable string). Stable reasons are `validation_failed`, `resident_not_found`, and `resident_inactive`; successful results have null failureReason and empty errors. Failures include readable messages and a null serviceRequest. Validation errors retain their field names.

## Verification

Add validator, repository, and submission tests without weakening or deleting existing tests.

Required coverage:

1. Valid submission succeeds for an Active Resident.
2. Persistence generates a positive request ID; multiple requests have distinct IDs.
3. The generated ID retrieves the saved request; missing IDs return null.
4. All six stored fields are preserved, including Pending.
5. Empty and whitespace-only service types fail; arbitrary meaningful service types pass.
6. Empty and whitespace-only descriptions fail.
7. Zero and negative resident IDs fail.
8. Invalid requests do not reach repository save and create no row.
9. A structurally valid missing Resident is rejected without persistence.
10. An Inactive Resident is rejected, remains stored and Inactive, and creates no request.
11. Non-Pending initial status fails without persistence.
12. Assigned request IDs fail, preventing resubmission/update behavior.
13. A separate repository and reconnected file-backed SQLite connection retrieve a submitted request, proving durable persistence.
14. Every Resident attribute remains unchanged after successful submission.
15. Blank, malformed, and impossible request dates fail; valid past/future dates are preserved.
16. Results distinguish all three expected failure categories from success.

Use mocks where useful to assert rejected submissions never call save. Use real database integration tests for persistence and workflow, including a temporary file-backed SQLite database for reconnect verification. Temporary databases must be disconnected and removed during teardown.

Run focused T09 tests, the complete regression suite, and Pint on intended T09 PHP files. Inspect diffs for unrelated formatting changes. Verify the running application's home page and `/health` before final delivery; baseline framework HTTP tests already passed, but a live-server check has not yet been performed.

## Delivery evidence

Record test counts, application/health results, branch and commit, repository URL, and merged PR URL. Review PR files before merging. The student submits the classroom repository URL and merged T09 PR URL to Moodle and remains responsible for explaining each component.
