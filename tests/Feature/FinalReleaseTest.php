<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_identity_is_consistent_across_health_and_management_pages(): void
    {
        $this->get('/health')->assertOk()->assertExactJson([
            'status' => 'ok',
            'application' => 'Community Services Management System',
            'version' => '1.0.0',
        ]);

        foreach (['/dashboard', '/residents', '/service-requests'] as $path) {
            $this->get($path)->assertOk()->assertSee('v1.0.0');
        }
    }

    public function test_registered_resident_request_workflow_and_deactivation_reconcile_with_dashboard(): void
    {
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('service_requests', 0);

        $this->post('/residents', [
            'firstName' => 'Ana',
            'lastName' => 'Cruz',
            'address' => 'Barangay Mabini',
            'contactNumber' => '09171234567',
            'email' => 'ana@example.com',
        ])->assertRedirect('/residents');

        $resident = DB::table('residents')->first();
        $this->assertNotNull($resident);
        $this->assertSame('Active', $resident->status);

        $requestInput = [
            'residentId' => (string) $resident->id,
            'serviceType' => 'Barangay Clearance',
            'description' => 'Employment requirement',
            'dateRequested' => '2026-11-13',
        ];
        $this->post('/service-requests', $requestInput)
            ->assertRedirect('/service-requests');

        $request = DB::table('service_requests')->first();
        $this->assertNotNull($request);
        $this->assertSame($resident->id, $request->resident_id);
        $this->assertSame('Pending', $request->status);

        $this->post("/service-requests/{$request->id}/complete")
            ->assertSessionHasErrors(['status']);
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'Pending']);

        $this->post("/service-requests/{$request->id}/start")
            ->assertRedirect("/service-requests/{$request->id}");
        $this->post("/service-requests/{$request->id}/complete")
            ->assertRedirect("/service-requests/{$request->id}");

        $this->post("/residents/{$resident->id}/deactivate")
            ->assertRedirect('/residents');
        $this->post('/service-requests', $requestInput)
            ->assertSessionHasErrors(['residentId']);

        $this->assertDatabaseHas('residents', ['id' => $resident->id, 'status' => 'Inactive']);
        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'resident_id' => $resident->id,
            'status' => 'Completed',
        ]);
        $this->assertDatabaseCount('service_requests', 1);

        $summary = app(DashboardService::class)->summary();
        $this->assertSame(1, $summary['residents']['total']);
        $this->assertSame(0, $summary['residents']['active']);
        $this->assertSame(1, $summary['residents']['inactive']);
        $this->assertSame(1, $summary['serviceRequests']['completed']);
        $this->assertSame(0, $summary['serviceRequests']['open']);
        $this->assertSame($request->id, $summary['recentRequests']->first()->id);

        $this->get('/dashboard')->assertOk()->assertSee('v1.0.0');
        $this->get("/service-requests/{$request->id}")->assertOk()->assertSee('Completed');
    }
}
