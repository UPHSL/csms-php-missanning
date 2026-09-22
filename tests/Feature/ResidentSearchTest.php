<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentSearchTest extends TestCase
{
    use RefreshDatabase;

    private ResidentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ResidentRepository();
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

    public function test_list_all_persisted_residents(): void
    {
        $resident1 = $this->makeResident(['firstName' => 'Juan', 'email' => 'juan@example.com']);
        $resident2 = $this->makeResident(['firstName' => 'Maria', 'email' => 'maria@example.com']);

        $this->repository->save($resident1);
        $this->repository->save($resident2);

        $result = $this->repository->listResidents();

        $this->assertCount(2, $result);
    }

    public function test_empty_resident_listing_returns_empty_collection(): void
    {
        $result = $this->repository->listResidents();

        $this->assertCount(0, $result);
        $this->assertEmpty($result);
    }

    public function test_resident_listing_uses_required_ordering(): void
    {
        $this->repository->save($this->makeResident(['firstName' => 'Ana', 'lastName' => 'Santos', 'email' => 'ana@example.com']));
        $this->repository->save($this->makeResident(['firstName' => 'Pedro', 'lastName' => 'Cruz', 'email' => 'pedro@example.com']));
        $this->repository->save($this->makeResident(['firstName' => 'Maria', 'lastName' => 'Andres', 'email' => 'maria@example.com']));
        $this->repository->save($this->makeResident(['firstName' => 'Juan', 'lastName' => 'Cruz', 'email' => 'juan@example.com']));

        $result = $this->repository->listResidents();

        $this->assertEquals('Andres', $result[0]->lastName);
        $this->assertEquals('Cruz', $result[1]->lastName);
        $this->assertEquals('Juan', $result[1]->firstName);
        $this->assertEquals('Cruz', $result[2]->lastName);
        $this->assertEquals('Pedro', $result[2]->firstName);
        $this->assertEquals('Santos', $result[3]->lastName);
    }

    public function test_partial_first_name_search_is_case_insensitive(): void
    {
        $resident = $this->makeResident(['firstName' => 'Juan']);
        $this->repository->save($resident);

        $result = $this->repository->searchByName('jUa');

        $this->assertCount(1, $result);
        $this->assertEquals('Juan', $result[0]->firstName);
    }

    public function test_partial_last_name_search_is_case_insensitive(): void
    {
        $resident = $this->makeResident(['lastName' => 'Dela Cruz']);
        $this->repository->save($resident);

        $result = $this->repository->searchByName('cRuZ');

        $this->assertCount(1, $result);
        $this->assertEquals('Dela Cruz', $result[0]->lastName);
    }

    public function test_blank_search_returns_all_residents(): void
    {
        $this->repository->save($this->makeResident(['firstName' => 'Juan', 'email' => 'juan@example.com']));
        $this->repository->save($this->makeResident(['firstName' => 'Maria', 'email' => 'maria@example.com']));

        $result = $this->repository->searchByName('   ');

        $this->assertCount(2, $result);
    }

    public function test_search_with_no_match_returns_empty_collection(): void
    {
        $this->repository->save($this->makeResident());

        $result = $this->repository->searchByName('ZzzUnknownResident');

        $this->assertCount(0, $result);
        $this->assertEmpty($result);
    }

    public function test_search_results_preserve_resident_information(): void
    {
        $resident = $this->makeResident([
            'firstName' => 'Juan',
            'lastName' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]);
        $this->repository->save($resident);

        $result = $this->repository->searchByName('Juan');

        $this->assertCount(1, $result);
        $found = $result[0];
        $this->assertEquals('Juan', $found->firstName);
        $this->assertEquals('Dela Cruz', $found->lastName);
        $this->assertEquals('Barangay Santo Tomas', $found->address);
        $this->assertEquals('09171234567', $found->contactNumber);
        $this->assertEquals('juan@example.com', $found->email);
        $this->assertEquals('Active', $found->status);
        $this->assertStringStartsWith('0', $found->contactNumber);
    }

    public function test_active_and_inactive_residents_are_included(): void
    {
        $this->repository->save($this->makeResident(['status' => 'Active', 'email' => 'active@example.com']));
        $this->repository->save($this->makeResident(['status' => 'Inactive', 'email' => 'inactive@example.com']));

        $result = $this->repository->listResidents();

        $this->assertCount(2, $result);
        $statuses = $result->pluck('status')->toArray();
        $this->assertContains('Active', $statuses);
        $this->assertContains('Inactive', $statuses);
    }

    public function test_matching_resident_is_not_duplicated(): void
    {
        $resident = $this->makeResident([
            'firstName' => 'Cruz',
            'lastName' => 'Cruz',
        ]);
        $this->repository->save($resident);

        $result = $this->repository->searchByName('Cruz');

        $this->assertCount(1, $result);
    }
}
