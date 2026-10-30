<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function resident(): Resident
    {
        return (new ResidentRepository)->save(new Resident([
            'firstName' => 'Juan', 'lastName' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas', 'contactNumber' => '09171234567',
            'email' => 'juan@example.com', 'status' => 'Active',
        ]));
    }

    private function request(int $residentId): ServiceRequest
    {
        return new ServiceRequest($residentId, 'Barangay Clearance', 'Request for employment requirement', '2026-09-15');
    }

    public function test_database_generates_id_and_all_request_information_is_retrievable(): void
    {
        $resident = $this->resident();
        $request = $this->request($resident->id);
        $this->assertNull($request->id);

        $saved = (new ServiceRequestRepository)->save($request);
        $this->assertGreaterThan(0, $saved->id);
        $found = (new ServiceRequestRepository)->findById($saved->id);
        $this->assertNotNull($found);
        $this->assertNotSame($saved, $found);
        $this->assertSame($saved->id, $found->id);
        $this->assertSame($resident->id, $found->residentId);
        $this->assertSame('Barangay Clearance', $found->serviceType);
        $this->assertSame('Request for employment requirement', $found->description);
        $this->assertSame('2026-09-15', $found->dateRequested);
        $this->assertSame('Pending', $found->status);
        $this->assertDatabaseHas('service_requests', ['id' => $saved->id, 'resident_id' => $resident->id]);
    }

    public function test_missing_request_returns_null(): void
    {
        $this->assertNull((new ServiceRequestRepository)->findById(99999));
    }

    public function test_multiple_requests_have_distinct_ids_and_correct_resident_links(): void
    {
        $firstResident = $this->resident();
        $secondResident = $this->resident();
        $repository = new ServiceRequestRepository;
        $first = $repository->save($this->request($firstResident->id));
        $second = $repository->save($this->request($firstResident->id));
        $third = $repository->save($this->request($secondResident->id));

        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($second->id, $third->id);
        $this->assertNotSame($first->id, $third->id);
        $this->assertSame($firstResident->id, $repository->findById($first->id)->residentId);
        $this->assertSame($firstResident->id, $repository->findById($second->id)->residentId);
        $this->assertSame($secondResident->id, $repository->findById($third->id)->residentId);
        $this->assertDatabaseCount('service_requests', 3);
    }

    public function test_assigned_id_is_rejected_without_creating_a_row(): void
    {
        $request = $this->request($this->resident()->id);
        $request->id = 10;

        try {
            (new ServiceRequestRepository)->save($request);
            $this->fail('Assigned IDs must not be accepted as new requests.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->assertSame(10, $request->id);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_persistence_contains_only_the_six_required_columns(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'resident_id', 'service_type', 'description', 'date_requested', 'status'],
            Schema::getColumnListing('service_requests'),
        );
    }
    public function test_update_status_changes_only_the_persisted_status(): void
    {
        $resident = $this->resident();
        $request = $this->request($resident->id);
        $repository = new ServiceRequestRepository;

        $saved = $repository->save($request);

        $updated = $repository->updateStatus(
            $saved->id,
            ServiceRequest::STATUS_IN_PROGRESS
        );

        $this->assertNotNull($updated);
        $this->assertSame($saved->id, $updated->id);
        $this->assertSame($resident->id, $updated->residentId);
        $this->assertSame('Barangay Clearance', $updated->serviceType);
        $this->assertSame('Request for employment requirement', $updated->description);
        $this->assertSame('2026-09-15', $updated->dateRequested);
        $this->assertSame(ServiceRequest::STATUS_IN_PROGRESS, $updated->status);

        $this->assertDatabaseHas('service_requests', [
            'id' => $saved->id,
            'resident_id' => $resident->id,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirement',
            'date_requested' => '2026-09-15',
            'status' => ServiceRequest::STATUS_IN_PROGRESS,
        ]);
    }
}
