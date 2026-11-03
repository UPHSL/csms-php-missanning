<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\DashboardService;
use App\Services\ResidentDeactivationService;
use App\Services\ServiceRequestStatusUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function resident(string $status = 'Active'): Resident
    {
        return (new ResidentRepository)->save(new Resident([
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'maria@example.com',
            'status' => $status,
        ]));
    }

    private function serviceRequest(
        ?Resident $resident = null,
        string $type = 'Barangay Clearance',
        string $date = '2026-10-20',
        string $status = 'Pending',
    ): ServiceRequest {
        $resident ??= $this->resident();

        return (new ServiceRequestRepository)->save(new ServiceRequest(
            $resident->id,
            $type,
            'Employment requirement',
            $date,
            $status,
        ));
    }

    private function summary(): array
    {
        return app(DashboardService::class)->summary();
    }

    public function test_dashboard_loads_with_required_sections_and_navigation(): void
    {
        $this->get('/dashboard')->assertOk()
            ->assertSee('CSMS Dashboard')
            ->assertSee('Resident Summary')
            ->assertSee('Service Request Summary')
            ->assertSee('Open Service Requests')
            ->assertSee('Recent Service Requests')
            ->assertSee('Residents')
            ->assertSee('Service Requests');
    }

    public function test_empty_dashboard_has_zero_counts_and_clear_recent_empty_state(): void
    {
        $summary = $this->summary();

        $this->assertSame(['total' => 0, 'active' => 0, 'inactive' => 0], $summary['residents']);
        $this->assertSame([
            'total' => 0, 'pending' => 0, 'inProgress' => 0,
            'completed' => 0, 'cancelled' => 0, 'open' => 0,
        ], $summary['serviceRequests']);
        $this->assertCount(0, $summary['recentRequests']);
        $this->get('/dashboard')->assertOk()->assertSee('No Service Requests available.');
    }

    public function test_total_resident_count_includes_all_persisted_residents(): void
    {
        $this->resident();
        $this->resident('Inactive');
        $this->resident();

        $this->assertSame(3, $this->summary()['residents']['total']);
    }

    public function test_resident_status_counts_reconcile_with_total(): void
    {
        $this->resident();
        $this->resident('Inactive');
        $this->resident('Inactive');

        $counts = $this->summary()['residents'];
        $this->assertSame(1, $counts['active']);
        $this->assertSame(2, $counts['inactive']);
        $this->assertSame($counts['total'], $counts['active'] + $counts['inactive']);
    }

    public function test_total_service_request_count_includes_all_statuses(): void
    {
        $resident = $this->resident();
        $this->serviceRequest($resident, status: 'Pending');
        $this->serviceRequest($resident, status: 'Completed');
        $this->serviceRequest($resident, status: 'Cancelled');

        $this->assertSame(3, $this->summary()['serviceRequests']['total']);
    }

    public function test_each_service_request_status_count_is_correct(): void
    {
        $resident = $this->resident();
        $this->serviceRequest($resident, status: 'Pending');
        $this->serviceRequest($resident, status: 'Pending');
        $this->serviceRequest($resident, status: 'In Progress');
        $this->serviceRequest($resident, status: 'Completed');
        $this->serviceRequest($resident, status: 'Cancelled');

        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(2, $counts['pending']);
        $this->assertSame(1, $counts['inProgress']);
        $this->assertSame(1, $counts['completed']);
        $this->assertSame(1, $counts['cancelled']);
    }

    public function test_service_request_status_counts_reconcile_with_total(): void
    {
        $resident = $this->resident();
        foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $status) {
            $this->serviceRequest($resident, status: $status);
        }

        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(4, $counts['total']);
        $this->assertSame($counts['total'], $counts['pending'] + $counts['inProgress'] + $counts['completed'] + $counts['cancelled']);
    }

    public function test_open_requests_equal_pending_plus_in_progress_only(): void
    {
        $resident = $this->resident();
        $this->serviceRequest($resident, status: 'Pending');
        $this->serviceRequest($resident, status: 'In Progress');
        $this->serviceRequest($resident, status: 'In Progress');
        $this->serviceRequest($resident, status: 'Completed');
        $this->serviceRequest($resident, status: 'Cancelled');

        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(3, $counts['open']);
        $this->assertSame($counts['pending'] + $counts['inProgress'], $counts['open']);
    }

    public function test_resident_deactivation_is_reflected_without_changing_total(): void
    {
        $resident = $this->resident();
        $this->assertSame(['total' => 1, 'active' => 1, 'inactive' => 0], $this->summary()['residents']);

        (new ResidentDeactivationService(new ResidentRepository))->deactivate($resident->id);

        $this->assertSame(['total' => 1, 'active' => 0, 'inactive' => 1], $this->summary()['residents']);
    }

    public function test_starting_processing_refreshes_pending_and_in_progress_counts(): void
    {
        $request = $this->serviceRequest();
        $this->assertSame(1, $this->summary()['serviceRequests']['pending']);

        (new ServiceRequestStatusUpdateService(new ServiceRequestRepository))->update($request->id, 'In Progress');

        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(1, $counts['total']);
        $this->assertSame(0, $counts['pending']);
        $this->assertSame(1, $counts['inProgress']);
        $this->assertSame(1, $counts['open']);
    }

    public function test_terminal_transition_reduces_open_count_but_preserves_total(): void
    {
        $request = $this->serviceRequest();
        $statuses = new ServiceRequestStatusUpdateService(new ServiceRequestRepository);
        $statuses->update($request->id, 'In Progress');
        $this->assertSame(1, $this->summary()['serviceRequests']['open']);

        $statuses->update($request->id, 'Completed');

        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(1, $counts['total']);
        $this->assertSame(0, $counts['open']);
        $this->assertSame(1, $counts['completed']);
    }

    public function test_recent_requests_are_limited_to_five(): void
    {
        $resident = $this->resident();
        for ($number = 1; $number <= 7; $number++) {
            $this->serviceRequest($resident, 'Request '.$number, '2026-10-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT));
        }

        $recent = $this->summary()['recentRequests'];
        $this->assertCount(5, $recent);
        $this->assertSame(['Request 7', 'Request 6', 'Request 5', 'Request 4', 'Request 3'], $recent->pluck('serviceType')->all());
        $this->get('/dashboard')->assertOk()->assertDontSee('Request 1')->assertDontSee('Request 2');
    }

    public function test_recent_requests_order_by_date_then_descending_id(): void
    {
        $resident = $this->resident();
        $old = $this->serviceRequest($resident, 'Old', '2026-10-01');
        $firstNew = $this->serviceRequest($resident, 'First New', '2026-10-20');
        $lastNew = $this->serviceRequest($resident, 'Last New', '2026-10-20');

        $this->assertSame([$lastNew->id, $firstNew->id, $old->id], $this->summary()['recentRequests']->pluck('id')->all());
    }

    public function test_terminal_requests_can_appear_in_recent_results_and_link_to_details(): void
    {
        $resident = $this->resident();
        $completed = $this->serviceRequest($resident, 'Completed Request', '2026-10-20', 'Completed');
        $cancelled = $this->serviceRequest($resident, 'Cancelled Request', '2026-10-21', 'Cancelled');

        $this->assertSame([$cancelled->id, $completed->id], $this->summary()['recentRequests']->pluck('id')->all());
        $this->get('/dashboard')->assertOk()
            ->assertSee('Completed Request')->assertSee('Cancelled Request')
            ->assertSee(route('service-requests.show', $completed->id))
            ->assertSee(route('service-requests.show', $cancelled->id));
    }

    public function test_dashboard_get_does_not_modify_persisted_records(): void
    {
        $resident = $this->resident();
        $request = $this->serviceRequest($resident);
        $beforeResident = DB::table('residents')->where('id', $resident->id)->first();
        $beforeRequest = DB::table('service_requests')->where('id', $request->id)->first();

        $this->get('/dashboard')->assertOk();
        $this->get('/dashboard')->assertOk();

        $this->assertEquals($beforeResident, DB::table('residents')->where('id', $resident->id)->first());
        $this->assertEquals($beforeRequest, DB::table('service_requests')->where('id', $request->id)->first());
        $this->assertDatabaseCount('residents', 1);
        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_existing_management_pages_link_to_dashboard(): void
    {
        $this->get('/residents')->assertOk()->assertSee(route('dashboard.index'));
        $this->get('/service-requests')->assertOk()->assertSee(route('dashboard.index'));
        $this->get('/')->assertOk()->assertSee('Open CSMS Dashboard');
    }
}
