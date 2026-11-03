<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function resident(array $attributes = []): Resident
    {
        return (new ResidentRepository)->save(new Resident(array_merge([
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'maria@example.com',
            'status' => Resident::STATUS_ACTIVE,
        ], $attributes)));
    }

    private function serviceRequest(Resident $resident): ServiceRequest
    {
        return (new ServiceRequestRepository)->save(new ServiceRequest(
            $resident->id,
            'Barangay Clearance',
            'Employment requirement',
            '2026-10-20',
        ));
    }

    private function records(string $table): array
    {
        return DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
    }

    public function test_malformed_route_identifiers_return_not_found_without_changing_records(): void
    {
        $resident = $this->resident();
        $this->serviceRequest($resident);
        $residents = $this->records('residents');
        $requests = $this->records('service_requests');

        $this->get('/residents/not-a-number/edit')->assertNotFound();
        $this->patch('/residents/not-a-number', ['firstName' => 'Changed'])->assertNotFound();
        $this->get('/service-requests/not-a-number')->assertNotFound();
        $this->post('/service-requests/not-a-number/start')->assertNotFound();

        $this->assertSame($residents, $this->records('residents'));
        $this->assertSame($requests, $this->records('service_requests'));
    }

    public function test_invalid_update_cannot_partially_change_an_inactive_resident_or_reactivate_them(): void
    {
        $resident = $this->resident(['status' => 'Inactive']);
        $original = $this->records('residents');

        $this->patch("/residents/{$resident->id}", [
            'firstName' => 'Changed',
            'lastName' => 'Santos',
            'address' => 'New address',
            'contactNumber' => 'invalid',
            'email' => 'maria@example.com',
            'status' => 'Active',
        ])->assertRedirect("/residents/{$resident->id}/edit")
            ->assertSessionHasErrors(['contact_number']);

        $this->assertSame($original, $this->records('residents'));
    }

    public function test_repeated_cross_module_get_requests_leave_both_tables_unchanged(): void
    {
        $resident = $this->resident();
        $request = $this->serviceRequest($resident);
        $residents = $this->records('residents');
        $requests = $this->records('service_requests');

        for ($visit = 0; $visit < 2; $visit++) {
            $this->get('/dashboard')->assertOk();
            $this->get('/residents?search=Maria')->assertOk();
            $this->get("/residents/{$resident->id}/edit")->assertOk();
            $this->get('/service-requests?search=Clearance&status=Pending')->assertOk();
            $this->get("/service-requests/{$request->id}")->assertOk();
        }

        $this->assertSame($residents, $this->records('residents'));
        $this->assertSame($requests, $this->records('service_requests'));
    }

    public function test_deactivation_blocks_new_submission_but_existing_request_remains_processable_and_reported(): void
    {
        $resident = $this->resident();
        $request = $this->serviceRequest($resident);

        $this->post("/residents/{$resident->id}/deactivate")->assertRedirect('/residents');
        $this->post('/service-requests', [
            'residentId' => (string) $resident->id,
            'serviceType' => 'New assistance',
            'description' => 'New request after deactivation',
            'dateRequested' => '2026-10-21',
        ])->assertRedirect('/service-requests/create')->assertSessionHasErrors(['residentId']);
        $this->assertDatabaseCount('service_requests', 1);

        $this->post("/service-requests/{$request->id}/start")
            ->assertRedirect("/service-requests/{$request->id}");
        $this->get("/service-requests/{$request->id}")->assertOk()->assertSee('In Progress');

        $summary = app(DashboardService::class)->summary();
        $this->assertSame(1, $summary['residents']['inactive']);
        $this->assertSame(0, $summary['residents']['active']);
        $this->assertSame(1, $summary['serviceRequests']['inProgress']);
        $this->assertSame(1, $summary['serviceRequests']['open']);
    }

    public function test_resident_search_treats_sql_wildcards_as_literal_text(): void
    {
        $this->resident();
        $special = $this->resident([
            'firstName' => 'Percent%',
            'lastName' => 'Person',
            'email' => 'percent@example.com',
        ]);

        $this->get('/residents?search=%25')->assertOk()
            ->assertSee('Percent% Person')
            ->assertDontSee('Maria Santos');

        $this->assertDatabaseCount('residents', 2);
        $this->assertSame('Percent%', $special->fresh()->firstName);
    }
}
