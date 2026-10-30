<?php

namespace Tests\Unit;

use App\Models\ServiceRequest;
use PHPUnit\Framework\TestCase;

class ServiceRequestTest extends TestCase
{
    private function makeServiceRequest(array $overrides = []): ServiceRequest
    {
        return new ServiceRequest(
            residentId: $overrides['residentId'] ?? 25,
            serviceType: $overrides['serviceType'] ?? 'Barangay Clearance',
            description: $overrides['description'] ?? 'Request for employment requirement',
            dateRequested: $overrides['dateRequested'] ?? '2025-01-15',
        );
    }

    // Test 1
    public function test_service_request_can_be_created(): void
    {
        $request = $this->makeServiceRequest();

        $this->assertInstanceOf(ServiceRequest::class, $request);
    }

    // Test 2
    public function test_service_request_information_is_accessible(): void
    {
        $request = $this->makeServiceRequest();

        $this->assertEquals(25, $request->residentId);
        $this->assertEquals('Barangay Clearance', $request->serviceType);
        $this->assertEquals('Request for employment requirement', $request->description);
        $this->assertEquals('2025-01-15', $request->dateRequested);
    }

    // Test 3
    public function test_resident_id_is_preserved(): void
    {
        $request = $this->makeServiceRequest(['residentId' => 25]);

        $this->assertEquals(25, $request->residentId);
    }

    // Test 4
    public function test_new_service_request_has_unassigned_id(): void
    {
        $request = $this->makeServiceRequest();

        $this->assertNull($request->id);
    }

    // Test 5
    public function test_new_service_request_defaults_to_pending(): void
    {
        $request = $this->makeServiceRequest();

        $this->assertEquals(ServiceRequest::STATUS_PENDING, $request->status);
    }

    // Test 6
    public function test_service_request_information_is_independent_between_objects(): void
    {
        $request1 = $this->makeServiceRequest([
            'residentId' => 25,
            'serviceType' => 'Barangay Clearance',
            'description' => 'First request',
            'dateRequested' => '2025-01-15',
        ]);

        $request2 = $this->makeServiceRequest([
            'residentId' => 42,
            'serviceType' => 'Certificate Request',
            'description' => 'Second request',
            'dateRequested' => '2025-02-20',
        ]);

        $this->assertEquals(25, $request1->residentId);
        $this->assertEquals(42, $request2->residentId);
        $this->assertEquals('Barangay Clearance', $request1->serviceType);
        $this->assertEquals('Certificate Request', $request2->serviceType);
        $this->assertEquals('First request', $request1->description);
        $this->assertEquals('Second request', $request2->description);
    }
}
