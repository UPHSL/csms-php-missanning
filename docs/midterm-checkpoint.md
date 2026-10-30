# Midterm Checkpoint - T10 Manage Service Request Status

## 1. Developer Information

**Name:** Jullie Anne A. Temporosa
**GitHub Username:** missanning
**Primary Technology Stack:** PHP / Laravel / SQLite / PHPUnit
**T10 Branch:** `feature/t10-service-request-status`

---

## 2. My T10 Implementation

For T10, I extended the existing Service Request functionality from T08 and T09 to support controlled status transitions for persisted Service Requests. I reused the existing `ServiceRequest` model and `ServiceRequestRepository` instead of creating a new Service Request domain model. The `ServiceRequestStatusUpdateService` retrieves the existing Service Request by its ID and checks whether the requested target status is supported and whether the transition is allowed from the current persisted status. The implementation supports `Pending`, `In Progress`, `Completed`, and `Cancelled`, with only the specified transitions being permitted. Invalid transitions, unsupported statuses, same-status requests, and nonexistent Service Request IDs are rejected before the persistence layer is modified. For valid transitions, the repository updates only the `status` field of the existing Service Request and retrieves the updated persisted record. The implementation preserves the Service Request ID, Resident ID, service type, description, and requested date during a successful status update. The result is returned through `ServiceRequestStatusUpdateResult`, which distinguishes successful updates from not-found, unsupported-status, and invalid-transition failures.

## 3. My Transition Rules

The T10 implementation enforces the following allowed transitions:

| Current Status | Allowed Next Status |
| -------------- | ------------------- |
| `Pending`      | `In Progress`       |
| `Pending`      | `Cancelled`         |
| `In Progress`  | `Completed`         |
| `In Progress`  | `Cancelled`         |
| `Completed`    | None                |
| `Cancelled`    | None                |

`Pending` cannot transition directly to `Completed` because a Service Request must first move to `In Progress`.

`Completed` is a terminal state because it represents a finished Service Request. Once a request is completed, it cannot return to another status.

`Cancelled` is also a terminal state. T10 does not support reopening or reactivating cancelled Service Requests.

Same-status requests such as `Pending` to `Pending` are rejected because they do not represent a valid status transition.

Unsupported target statuses such as `Approved`, `Rejected`, `Processing`, `Done`, or `Closed` are also rejected.

Invalid transitions do not modify the persisted Service Request.

## 4. Files I Changed

### `app/Models/ServiceRequest.php`

Added constants for the four supported T10 statuses:

* `Pending`
* `In Progress`
* `Completed`
* `Cancelled`

The existing Service Request model continues to be reused.

### `app/Repositories/ServiceRequestRepository.php`

Added the `updateStatus()` method to update the persisted status of an existing Service Request. The method targets the existing Service Request by ID and updates only its `status` field before retrieving the persisted record.

### `app/Services/ServiceRequestStatusUpdateResult.php`

Added a result class for representing successful and failed status-management operations. It provides the updated Service Request for successful operations and identifies failure reasons for unsuccessful operations.

### `app/Services/ServiceRequestStatusUpdateService.php`

Added the application/service-layer workflow for T10. This component retrieves the Service Request, checks supported statuses, enforces the allowed transition rules, prevents invalid transitions from reaching persistence, and returns the appropriate status-update result.

### `tests/Feature/ServiceRequestRepositoryTest.php`

Added a repository test verifying that updating the Service Request status changes only the persisted status while preserving the other Service Request information.

### `tests/Feature/ServiceRequestStatusUpdateServiceTest.php`

Added the T10 automated tests covering the required transition, validation, persistence, terminal-state, not-found, and preservation scenarios, including the student-designed test.

## 5. Problem I Encountered

During development, I initially had to make sure that changing a Service Request status did not simply accept any status value or modify the database before checking whether the transition was allowed. Since Service Requests follow a controlled workflow, allowing an invalid change such as Pending to Completed could result in inaccurate records. I addressed this by separating the transition rules into the service layer, validating the current and requested statuses before calling the repository, and adding tests to verify that invalid transitions leave the persisted record unchanged.

## 6. My Student-Designed Test

**Test Name:** `test_whitespace_status_is_rejected_as_unsupported`

The test verifies that a status value containing whitespace around an otherwise valid status is rejected as an unsupported target status. The operation must fail safely and must not modify the persisted Service Request.

I added this test to verify that the status validation uses strict supported-status matching rather than accepting values that are only similar to the supported status values. This provides an additional edge-case check for the status-management workflow.

## 7. Tools and References Used

The following tools and references were used during T10 development:

* PHP / Laravel development environment
* PHPUnit automated testing
* SQLite persistence
* Git and GitHub for version control and Pull Request management
* Visual Studio Code for implementation and debugging
* AI coding assistant (ChatGPT) for development guidance, assisted code review, and assisted test review.

I remain responsible for understanding and being able to explain the submitted T10 implementation.

## Verification

The T10-specific automated test suite passed:

* **14 tests passed**
* **44 assertions passed**

The complete regression suite passed:

* **130 tests passed**
* **488 assertions passed**
* **0 failures**

The `/health` endpoint also remained operational after the T10 implementation.
