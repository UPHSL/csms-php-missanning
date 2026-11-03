<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentQueryService;
use App\Services\ResidentUpdateService;
use App\Services\ResidentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentUpdateServiceTest extends TestCase
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

    private function makeUpdateService(): ResidentUpdateService
    {
        return new ResidentUpdateService(
            new ResidentRepository(),
            new ResidentValidator()
        );
    }

    private function makeQueryService(): ResidentQueryService
    {
        return new ResidentQueryService(new ResidentRepository());
    }

    // Test 1
    public function test_valid_update_succeeds(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $result = $this->makeUpdateService()->update(
            $resident->id,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $this->assertTrue($result['success']);
        $this->assertFalse($result['notFound']);
        $this->assertNull($result['errors']);
    }

    // Test 2
    public function test_resident_id_is_preserved(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());
        $originalId = $resident->id;

        $result = $this->makeUpdateService()->update(
            $resident->id,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $this->assertEquals($originalId, $result['resident']->id);
    }

    // Test 3
    public function test_permitted_fields_are_persisted(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeUpdateService()->update(
            $resident->id,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $updated = $repository->findById($resident->id);

        $this->assertEquals('Maria', $updated->firstName);
        $this->assertEquals('Santos', $updated->lastName);
        $this->assertEquals('456 New St', $updated->address);
        $this->assertEquals('09181234567', $updated->contactNumber);
        $this->assertEquals('maria@example.com', $updated->email);
    }

    // Test 4
    public function test_status_is_preserved(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident(['status' => 'Inactive']));

        $this->makeUpdateService()->update(
            $resident->id,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $updated = $repository->findById($resident->id);

        $this->assertEquals('Inactive', $updated->status);
    }

    // Test 5
    public function test_invalid_update_fails(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $result = $this->makeUpdateService()->update(
            $resident->id,
            '',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $this->assertFalse($result['success']);
        $this->assertFalse($result['notFound']);
        $this->assertNotNull($result['errors']);
    }

    // Test 6
    public function test_invalid_update_does_not_modify_persisted_data(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeUpdateService()->update(
            $resident->id,
            '',
            'Santos',
            '456 New St',
            'ABC',
            'maria@example.com'
        );

        $unchanged = $repository->findById($resident->id);

        $this->assertEquals('Juan', $unchanged->firstName);
        $this->assertEquals('Cruz', $unchanged->lastName);
        $this->assertEquals('09171234567', $unchanged->contactNumber);
    }

    // Test 7
    public function test_updating_nonexistent_resident_returns_not_found(): void
    {
        $result = $this->makeUpdateService()->update(
            999999,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $this->assertFalse($result['success']);
        $this->assertTrue($result['notFound']);
    }

    // Test 8
    public function test_nonexistent_update_does_not_create_resident(): void
    {
        $before = Resident::count();

        $this->makeUpdateService()->update(
            999999,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $this->assertEquals($before, Resident::count());
    }

    // Test 9
    public function test_updated_resident_is_visible_through_t05_querying(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());

        $this->makeUpdateService()->update(
            $resident->id,
            'Miguel',
            'Santos',
            '456 New St',
            '09181234567',
            'miguel@example.com'
        );

        $results = $this->makeQueryService()->searchResidents('Miguel');

        $this->assertCount(1, $results);
        $this->assertEquals('Miguel', $results->first()->firstName);
    }

    // Test 10
    public function test_updated_contact_number_and_fields_are_preserved(): void
    {
        $repository = new ResidentRepository();
        $resident = $repository->save($this->makeResident());
        $originalId = $resident->id;

        $this->makeUpdateService()->update(
            $resident->id,
            'Maria',
            'Santos',
            '456 New St',
            '09181234567',
            'maria@example.com'
        );

        $updated = $repository->findById($originalId);

        $this->assertEquals('09181234567', $updated->contactNumber);
        $this->assertEquals('Maria', $updated->firstName);
        $this->assertEquals('Santos', $updated->lastName);
        $this->assertEquals($originalId, $updated->id);
        $this->assertEquals(Resident::STATUS_ACTIVE, $updated->status);
    }
}
