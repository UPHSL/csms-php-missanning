<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentRepositoryTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_resident_can_be_persisted(): void
    {
        $repository = new ResidentRepository();
        $resident = $this->makeResident();

        $repository->save($resident);

        $this->assertDatabaseHas('residents', [
            'firstName' => 'Juan',
        ]);
    }

    public function test_resident_receives_identifier_after_persistence(): void
    {
        $repository = new ResidentRepository();
        $resident = $this->makeResident();

        $this->assertNull($resident->id);

        $repository->save($resident);

        $this->assertNotNull($resident->id);
    }

    public function test_resident_can_be_retrieved_by_identifier(): void
    {
        $repository = new ResidentRepository();
        $resident = $this->makeResident();
        $repository->save($resident);

        $found = $repository->findById($resident->id);

        $this->assertNotNull($found);
        $this->assertEquals($resident->id, $found->id);
    }

    public function test_resident_information_is_preserved_after_persistence(): void
    {
        $repository = new ResidentRepository();
        $resident = $this->makeResident();
        $repository->save($resident);

        $found = $repository->findById($resident->id);

        $this->assertEquals('Juan', $found->firstName);
        $this->assertEquals('Dela Cruz', $found->lastName);
        $this->assertEquals('Barangay Santo Tomas', $found->address);
        $this->assertEquals('09171234567', $found->contactNumber);
        $this->assertEquals('juan@example.com', $found->email);
        $this->assertEquals('Active', $found->status);
    }

    public function test_active_status_is_preserved_after_persistence(): void
    {
        $repository = new ResidentRepository();
        $resident = $this->makeResident(['status' => 'Active']);
        $repository->save($resident);

        $found = $repository->findById($resident->id);

        $this->assertEquals('Active', $found->status);
    }

    public function test_find_by_id_returns_null_for_missing_resident(): void
    {
        $repository = new ResidentRepository();

        $result = $repository->findById(99999);

        $this->assertNull($result);
    }

    public function test_new_repository_instance_can_retrieve_previously_stored_resident(): void
    {
        $repository1 = new ResidentRepository();
        $resident = $this->makeResident();
        $repository1->save($resident);
        $id = $resident->id;

        $repository2 = new ResidentRepository();
        $found = $repository2->findById($id);

        $this->assertNotNull($found);
        $this->assertEquals($id, $found->id);
    }

    public function test_multiple_residents_can_be_persisted_and_retrieved_independently(): void
    {
        $repository = new ResidentRepository();

        $resident1 = $this->makeResident([
            'firstName' => 'Maria',
            'email' => 'maria@example.com',
        ]);
        $resident2 = $this->makeResident([
            'firstName' => 'Pedro',
            'email' => 'pedro@example.com',
        ]);

        $repository->save($resident1);
        $repository->save($resident2);

        $found1 = $repository->findById($resident1->id);
        $found2 = $repository->findById($resident2->id);

        $this->assertEquals('Maria', $found1->firstName);
        $this->assertEquals('Pedro', $found2->firstName);
        $this->assertNotEquals($found1->id, $found2->id);
    }
}
