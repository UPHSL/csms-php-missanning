<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentDeactivationService;
use App\Services\ResidentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentDeactivationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeResident(array $overrides = []): Resident
    {
        return new Resident(array_merge([
            'firstName' => 'Juan',
            'lastName' => 'Cruz',
            'address' => '123 Main St',
            'contactNumber' => '09171234567',
            'email' => 'juan@example.com',
            'status' => Resident::STATUS_ACTIVE,
        ], $overrides));
    }

    private function makeDeactivationService(): ResidentDeactivationService
    {
        return new ResidentDeactivationService(new ResidentRepository());
    }

    private function makeQueryService(): ResidentQueryService
    {
        return new ResidentQueryService(new ResidentRepository());
    }

    // Test 1
    public function test_active_resident_can_be_deactivated(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $result = $this->makeDeactivationService()->deactivate($resident->id);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['notFound']);
    }

    // Test 2
    public function test_resident_status_becomes_inactive_in_persistence(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeDeactivationService()->deactivate($resident->id);

        $retrieved = $repository->findById($resident->id);

        $this->assertEquals('Inactive', $retrieved->status);
    }

    // Test 3
    public function test_resident_id_is_preserved(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());
        $originalId = $resident->id;

        $result = $this->makeDeactivationService()->deactivate($resident->id);

        $this->assertEquals($originalId, $result['resident']->id);
    }

    // Test 4
    public function test_resident_information_is_preserved(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeDeactivationService()->deactivate($resident->id);

        $retrieved = $repository->findById($resident->id);

        $this->assertEquals('Juan', $retrieved->firstName);
        $this->assertEquals('Cruz', $retrieved->lastName);
        $this->assertEquals('123 Main St', $retrieved->address);
        $this->assertEquals('09171234567', $retrieved->contactNumber);
        $this->assertEquals('juan@example.com', $retrieved->email);
    }

    // Test 5
    public function test_deactivated_resident_remains_persisted_and_retrievable(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeDeactivationService()->deactivate($resident->id);

        $retrieved = $repository->findById($resident->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($resident->id, $retrieved->id);
        $this->assertEquals('Inactive', $retrieved->status);
    }

    // Test 6
    public function test_deactivated_resident_remains_available_through_t05(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeDeactivationService()->deactivate($resident->id);

        $results = $this->makeQueryService()->searchResidents('Juan');

        $this->assertCount(1, $results);
        $this->assertEquals('Inactive', $results->first()->status);
    }

    // Test 7
    public function test_already_inactive_resident_is_handled_safely(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident(['status' => 'Inactive']));
        $originalId = $resident->id;
        $before = Resident::count();

        $result = $this->makeDeactivationService()->deactivate($resident->id);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['alreadyInactive']);
        $this->assertEquals($originalId, $result['resident']->id);
        $this->assertEquals('Inactive', $result['resident']->status);
        $this->assertEquals($before, Resident::count());
    }

    // Test 8
    public function test_nonexistent_resident_is_handled_safely(): void
    {
        $result = $this->makeDeactivationService()->deactivate(999999);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['notFound']);
        $this->assertNull($result['resident']);
    }

    // Test 9
    public function test_nonexistent_deactivation_does_not_create_or_delete_records(): void
    {
        $repository = new ResidentRepository();
        $repository->save($this->makeResident());
        $before = Resident::count();

        $this->makeDeactivationService()->deactivate(999999);

        $this->assertEquals($before, Resident::count());
    }

    // Test 10
    public function test_deactivating_one_resident_does_not_affect_another(): void
    {
        $repository = new ResidentRepository();
        $resident1 = $repository->save($this->makeResident());
        $resident2 = $repository->save($this->makeResident([
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'email' => 'maria@example.com',
        ]));

        $this->makeDeactivationService()->deactivate($resident1->id);

        $retrieved2 = $repository->findById($resident2->id);

        $this->assertEquals('Active', $retrieved2->status);
        $this->assertEquals('Maria', $retrieved2->firstName);
        $this->assertEquals('Santos', $retrieved2->lastName);
    }
}
