<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\ServiceRequestStatusUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestStatusUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    private function resident(): Resident
    {
        return (new ResidentRepository)->save(new Resident([
            'firstName' => 'Juan',
            'lastName' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]));
    }

    private function request(
        int $residentId,
        string $status = ServiceRequest::STATUS_PENDING,
    ): ServiceRequest {
        return new ServiceRequest(
            residentId: $residentId,
            serviceType: 'Barangay Clearance',
            description: 'Request for employment requirement',
            dateRequested: '2026-09-15',
            status: $status,
        );
    }

    private function saveRequest(
        string $status = ServiceRequest::STATUS_PENDING,
    ): ServiceRequest {
        $resident = $this->resident();

        return (new ServiceRequestRepository)->save(
            $this->request($resident->id, $status)
        );
    }

    private function service(): ServiceRequestStatusUpdateService
    {
        return new ServiceRequestStatusUpdateService(
            new ServiceRequestRepository
        );
    }

    public function test_pending_to_in_progress_succeeds(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_IN_PROGRESS
        );

        $this->assertTrue($result->success);
        $this->assertSame(
            ServiceRequest::STATUS_IN_PROGRESS,
            $result->serviceRequest->status
        );
    }

    public function test_pending_to_cancelled_succeeds(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_CANCELLED
        );

        $this->assertTrue($result->success);
        $this->assertSame(
            ServiceRequest::STATUS_CANCELLED,
            $result->serviceRequest->status
        );
    }

    public function test_in_progress_to_completed_succeeds(): void
    {
        $request = $this->saveRequest(ServiceRequest::STATUS_IN_PROGRESS);

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_COMPLETED
        );

        $this->assertTrue($result->success);
        $this->assertSame(
            ServiceRequest::STATUS_COMPLETED,
            $result->serviceRequest->status
        );
    }

    public function test_in_progress_to_cancelled_succeeds(): void
    {
        $request = $this->saveRequest(ServiceRequest::STATUS_IN_PROGRESS);

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_CANCELLED
        );

        $this->assertTrue($result->success);
        $this->assertSame(
            ServiceRequest::STATUS_CANCELLED,
            $result->serviceRequest->status
        );
    }

    public function test_pending_to_completed_fails_and_remains_pending(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_COMPLETED
        );

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_PENDING,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }

    public function test_in_progress_to_pending_fails_and_remains_in_progress(): void
    {
        $request = $this->saveRequest(ServiceRequest::STATUS_IN_PROGRESS);

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_PENDING
        );

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_IN_PROGRESS,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }

    public function test_completed_is_terminal(): void
    {
        $request = $this->saveRequest(ServiceRequest::STATUS_COMPLETED);

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_CANCELLED
        );

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_COMPLETED,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }

    public function test_cancelled_is_terminal(): void
    {
        $request = $this->saveRequest(ServiceRequest::STATUS_CANCELLED);

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_COMPLETED
        );

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_CANCELLED,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }

    public function test_unsupported_status_is_rejected(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            'Approved'
        );

        $this->assertFalse($result->success);
        $this->assertSame('unsupported_status', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_PENDING,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }

    public function test_nonexistent_service_request_does_not_create_a_record(): void
    {
        $result = $this->service()->update(
            99999,
            ServiceRequest::STATUS_IN_PROGRESS
        );

        $this->assertFalse($result->success);
        $this->assertSame('not_found', $result->failureReason);
        $this->assertNull($result->serviceRequest);
        $this->assertDatabaseMissing('service_requests', ['id' => 99999]);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_successful_transition_preserves_all_information_except_status(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_IN_PROGRESS
        );

        $updated = $result->serviceRequest;

        $this->assertTrue($result->success);
        $this->assertSame($request->id, $updated->id);
        $this->assertSame($request->residentId, $updated->residentId);
        $this->assertSame($request->serviceType, $updated->serviceType);
        $this->assertSame($request->description, $updated->description);
        $this->assertSame($request->dateRequested, $updated->dateRequested);
        $this->assertSame(
            ServiceRequest::STATUS_IN_PROGRESS,
            $updated->status
        );
    }

    public function test_invalid_transition_does_not_modify_persistence(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_COMPLETED
        );

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->failureReason);

        $persisted = (new ServiceRequestRepository)->findById($request->id);

        $this->assertSame(
            ServiceRequest::STATUS_PENDING,
            $persisted->status
        );
    }

    public function test_same_status_request_is_rejected(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ServiceRequest::STATUS_PENDING
        );

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_PENDING,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }

    public function test_whitespace_status_is_rejected_as_unsupported(): void
    {
        $request = $this->saveRequest();

        $result = $this->service()->update(
            $request->id,
            ' In Progress '
        );

        $this->assertFalse($result->success);
        $this->assertSame('unsupported_status', $result->failureReason);
        $this->assertSame(
            ServiceRequest::STATUS_PENDING,
            (new ServiceRequestRepository)->findById($request->id)->status
        );
    }
}