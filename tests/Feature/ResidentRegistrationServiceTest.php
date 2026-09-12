<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentRegistrationService;
use App\Services\ResidentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResidentRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResidentRegistrationService $service;
    private ResidentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ResidentRepository();
        $this->service = new ResidentRegistrationService(
            new ResidentValidator(),
            $this->repository,
        );
    }

    private function makeResident(array $overrides = []): Resident
    {
        return new Resident(array_merge([
            'firstName' => 'Juan',
            'lastName' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ], $overrides));
    }

    public function test_valid_resident_can_be_registered(): void
    {
        $resident = $this->makeResident();

        $result = $this->service->register($resident);

        $this->assertNotNull($result);
    }

    public function test_registered_resident_receives_an_identifier(): void
    {
        $resident = $this->makeResident();

        $result = $this->service->register($resident);

        $this->assertNotNull($result->id);
    }

    public function test_registered_resident_is_persisted(): void
    {
        $resident = $this->makeResident();

        $result = $this->service->register($resident);
        $found = $this->repository->findById($result->id);

        $this->assertNotNull($found);
    }

    public function test_registered_resident_information_is_preserved(): void
    {
        $resident = $this->makeResident();

        $result = $this->service->register($resident);
        $found = $this->repository->findById($result->id);

        $this->assertEquals('Juan', $found->firstName);
        $this->assertEquals('Dela Cruz', $found->lastName);
        $this->assertEquals('Barangay Santo Tomas', $found->address);
        $this->assertEquals('09171234567', $found->contactNumber);
        $this->assertEquals('juan@example.com', $found->email);
        $this->assertEquals('Active', $found->status);
    }

    public function test_default_active_status_is_preserved(): void
    {
        $resident = $this->makeResident();

        $result = $this->service->register($resident);
        $found = $this->repository->findById($result->id);

        $this->assertEquals('Active', $found->status);
    }

    public function test_invalid_resident_registration_fails(): void
    {
        $resident = $this->makeResident(['firstName' => '']);

        $this->expectException(ValidationException::class);

        $this->service->register($resident);
    }

    public function test_invalid_resident_is_not_persisted(): void
    {
        $resident = $this->makeResident(['firstName' => '']);

        try {
            $this->service->register($resident);
        } catch (ValidationException) {}

        $this->assertDatabaseMissing('residents', ['email' => 'juan@example.com']);
    }

    public function test_validation_failure_identifies_the_invalid_field(): void
    {
        $resident = $this->makeResident(['firstName' => '']);

        try {
            $this->service->register($resident);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('first_name', $e->errors());
        }
    }
}
