<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\ServiceRequestSubmissionService;
use App\Services\ServiceRequestValidator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceRequestSubmissionServiceTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $path = tempnam(sys_get_temp_dir(), 'csms_t09_');
        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary SQLite database.');
        }
        $this->databasePath = $path;
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $path,
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        DB::purge('sqlite');
        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }
        parent::tearDown();
    }

    private function resident(string $status = 'Active'): Resident
    {
        return (new ResidentRepository)->save(new Resident([
            'firstName' => 'Juan', 'lastName' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas', 'contactNumber' => '09171234567',
            'email' => 'juan@example.com', 'status' => $status,
        ]));
    }

    private function request(int $residentId): ServiceRequest
    {
        return new ServiceRequest($residentId, 'Barangay Clearance', 'Request for employment requirement', '2026-09-15');
    }

    private function service(): ServiceRequestSubmissionService
    {
        return new ServiceRequestSubmissionService(new ServiceRequestValidator, new ResidentRepository, new ServiceRequestRepository);
    }

    public function test_active_resident_submission_generates_id_and_survives_database_reconnection(): void
    {
        $resident = $this->resident();
        $attributes = (new ResidentRepository)->findById($resident->id)->getAttributes();
        $request = $this->request($resident->id);
        $this->assertNull($request->id);

        $result = $this->service()->submit($request);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->serviceRequest);
        $this->assertGreaterThan(0, $result->serviceRequest->id);
        $this->assertNull($result->failureReason);
        $this->assertSame([], $result->errors);
        $id = $result->serviceRequest->id;
        DB::purge('sqlite');

        $found = (new ServiceRequestRepository)->findById($id);
        $this->assertNotNull($found);
        $this->assertSame($id, $found->id);
        $this->assertSame($resident->id, $found->residentId);
        $this->assertSame('Barangay Clearance', $found->serviceType);
        $this->assertSame('Request for employment requirement', $found->description);
        $this->assertSame('2026-09-15', $found->dateRequested);
        $this->assertSame('Pending', $found->status);
        $this->assertDatabaseCount('service_requests', 1);
        $this->assertSame($attributes, (new ResidentRepository)->findById($resident->id)->getAttributes());
    }

    public static function invalidRequests(): array
    {
        return [
            'assigned id' => ['id', 10],
            'zero assigned id' => ['id', 0],
            'missing resident id' => ['residentId', 0],
            'negative resident id' => ['residentId', -1],
            'missing service type' => ['serviceType', ''],
            'blank service type' => ['serviceType', " \t\n"],
            'missing description' => ['description', ''],
            'blank description' => ['description', " \t\n"],
            'missing date' => ['dateRequested', ''],
            'malformed date' => ['dateRequested', 'not-a-date'],
            'impossible date' => ['dateRequested', '2026-02-30'],
            'completed' => ['status', 'Completed'],
            'cancelled' => ['status', 'Cancelled'],
            'in progress' => ['status', 'In Progress'],
        ];
    }

    #[DataProvider('invalidRequests')]
    public function test_invalid_request_never_reaches_resident_lookup_or_persistence(string $field, int|string $value): void
    {
        $request = $this->request($this->resident()->id);
        $request->$field = $value;
        $originalId = $request->id;
        $residents = Mockery::mock(ResidentRepository::class);
        $residents->shouldNotReceive('findById');
        $requests = Mockery::mock(ServiceRequestRepository::class);
        $requests->shouldNotReceive('save');
        $service = new ServiceRequestSubmissionService(new ServiceRequestValidator, $residents, $requests);

        $result = $service->submit($request);

        $this->assertFalse($result->success);
        $this->assertNull($result->serviceRequest);
        $this->assertSame('validation_failed', $result->failureReason);
        $this->assertArrayHasKey($field, $result->errors);
        $this->assertNotEmpty($result->errors[$field]);
        $this->assertSame($originalId, $request->id);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_missing_resident_is_rejected_before_persistence(): void
    {
        $request = $this->request(99999);
        $requests = Mockery::mock(ServiceRequestRepository::class);
        $requests->shouldNotReceive('save');
        $service = new ServiceRequestSubmissionService(new ServiceRequestValidator, new ResidentRepository, $requests);

        $result = $service->submit($request);

        $this->assertFalse($result->success);
        $this->assertNull($result->serviceRequest);
        $this->assertSame('resident_not_found', $result->failureReason);
        $this->assertNotEmpty($result->errors['residentId']);
        $this->assertNull($request->id);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_inactive_resident_is_rejected_and_remains_unchanged_and_searchable(): void
    {
        $resident = $this->resident('Inactive');
        $attributes = (new ResidentRepository)->findById($resident->id)->getAttributes();
        $request = $this->request($resident->id);
        $requests = Mockery::mock(ServiceRequestRepository::class);
        $requests->shouldNotReceive('save');
        $service = new ServiceRequestSubmissionService(new ServiceRequestValidator, new ResidentRepository, $requests);

        $result = $service->submit($request);

        $this->assertFalse($result->success);
        $this->assertNull($result->serviceRequest);
        $this->assertSame('resident_inactive', $result->failureReason);
        $this->assertNotEmpty($result->errors['residentId']);
        $this->assertNull($request->id);
        $this->assertDatabaseCount('service_requests', 0);
        $repository = new ResidentRepository;
        $this->assertSame($attributes, $repository->findById($resident->id)->getAttributes());
        $this->assertSame('Inactive', $repository->findById($resident->id)->status);
        $this->assertSame($resident->id, $repository->searchByName('Juan')->first()->id);
    }

    public function test_resubmitting_a_persisted_request_does_not_create_another_row(): void
    {
        $service = $this->service();
        $first = $service->submit($this->request($this->resident()->id));
        $this->assertTrue($first->success);
        $id = $first->serviceRequest->id;

        $second = $service->submit($first->serviceRequest);

        $this->assertFalse($second->success);
        $this->assertSame('validation_failed', $second->failureReason);
        $this->assertArrayHasKey('id', $second->errors);
        $this->assertSame($id, $first->serviceRequest->id);
        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_persistence_exception_propagates_without_assigning_an_id(): void
    {
        $request = $this->request($this->resident()->id);
        $requests = Mockery::mock(ServiceRequestRepository::class);
        $requests->shouldReceive('save')->once()->with($request)->andThrow(new \RuntimeException('Database unavailable'));
        $service = new ServiceRequestSubmissionService(new ServiceRequestValidator, new ResidentRepository, $requests);

        try {
            $service->submit($request);
            $this->fail('Persistence exceptions must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database unavailable', $exception->getMessage());
        }

        $this->assertNull($request->id);
        $this->assertDatabaseCount('service_requests', 0);
    }
}
