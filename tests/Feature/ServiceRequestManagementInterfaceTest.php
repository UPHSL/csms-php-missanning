<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestManagementInterfaceTest extends TestCase
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

    private function savedRequest(
        ?Resident $resident = null,
        string $serviceType = 'Barangay Clearance',
        string $description = 'Employment requirement',
        string $date = '2026-10-20',
        string $status = 'Pending',
    ): ServiceRequest {
        $resident ??= $this->resident();

        return (new ServiceRequestRepository)->save(new ServiceRequest(
            $resident->id, $serviceType, $description, $date, $status,
        ));
    }

    public function test_management_page_loads_with_an_empty_state(): void
    {
        $this->get('/service-requests')->assertOk()
            ->assertSee('Service Request Management')
            ->assertSee('Submit Service Request')
            ->assertSee('No Service Requests found.');
    }

    public function test_persisted_requests_show_required_fields_and_actions(): void
    {
        $resident = $this->resident();
        $first = $this->savedRequest($resident);
        $second = $this->savedRequest($resident, 'Medical Assistance', 'Clinic referral');

        $this->get('/service-requests')->assertOk()
            ->assertSee("#{$first->id}")
            ->assertSee("#{$second->id}")
            ->assertSee("#{$resident->id}")
            ->assertSee('Barangay Clearance')
            ->assertSee('Medical Assistance')
            ->assertSee('2026-10-20')
            ->assertSee('Pending')
            ->assertSee('View')
            ->assertSee('Start Processing')
            ->assertSee('Cancel');
    }

    public function test_listing_preserves_date_descending_then_id_descending_order(): void
    {
        $resident = $this->resident();
        $old = $this->savedRequest($resident, 'Oldest Request', date: '2026-10-01');
        $firstNew = $this->savedRequest($resident, 'First New Request', date: '2026-10-22');
        $lastNew = $this->savedRequest($resident, 'Last New Request', date: '2026-10-22');

        $html = $this->get('/service-requests')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'First New Request'), strpos($html, 'Last New Request'));
        $this->assertLessThan(strpos($html, 'Oldest Request'), strpos($html, 'First New Request'));
        $this->assertNotSame($old->id, $firstNew->id);
        $this->assertNotSame($firstNew->id, $lastNew->id);
    }

    public function test_text_search_matches_partial_case_insensitive_type_or_description(): void
    {
        $resident = $this->resident();
        $this->savedRequest($resident, 'Barangay Clearance', 'Work document');
        $this->savedRequest($resident, 'Medical Aid', 'EMPLOYMENT support');
        $this->savedRequest($resident, 'Scholarship', 'School fees');

        $this->get('/service-requests?search=%20%20cLeAr%20%20')->assertOk()
            ->assertSee('Barangay Clearance')->assertDontSee('Medical Aid')->assertDontSee('Scholarship');
        $this->get('/service-requests?search=ployM')->assertOk()
            ->assertSee('Medical Aid')->assertDontSee('Barangay Clearance')->assertDontSee('Scholarship');
        $this->get('/service-requests?search=%20%20')->assertOk()
            ->assertSee('Barangay Clearance')->assertSee('Medical Aid')->assertSee('Scholarship');
        $this->get('/service-requests?search=unmatched')->assertOk()
            ->assertSee('No Service Requests found.');
    }

    public function test_status_filter_includes_each_status_and_all_statuses(): void
    {
        $resident = $this->resident();
        foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $status) {
            $this->savedRequest($resident, $status.' Request', status: $status);
        }

        foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $status) {
            $response = $this->get('/service-requests?status='.urlencode($status))->assertOk()
                ->assertSee($status.' Request');
            foreach (array_diff(['Pending', 'In Progress', 'Completed', 'Cancelled'], [$status]) as $other) {
                $response->assertDontSee($other.' Request');
            }
        }

        $this->get('/service-requests?status=')->assertOk()
            ->assertSee('Pending Request')->assertSee('In Progress Request')
            ->assertSee('Completed Request')->assertSee('Cancelled Request');
    }

    public function test_combined_search_and_status_requires_both_conditions_and_preserves_inputs(): void
    {
        $resident = $this->resident();
        $this->savedRequest($resident, 'Clearance Pending', status: 'Pending');
        $this->savedRequest($resident, 'Clearance Completed', status: 'Completed');
        $this->savedRequest($resident, 'Assistance Pending', status: 'Pending');
        $this->savedRequest($resident, 'Assistance Completed', status: 'Completed');

        $this->get('/service-requests?search=clearance&status=Pending')->assertOk()
            ->assertSee('Clearance Pending')
            ->assertDontSee('Clearance Completed')
            ->assertDontSee('Assistance Pending')
            ->assertDontSee('Assistance Completed')
            ->assertSee('value="clearance"', false)
            ->assertSee('value="Pending" selected', false);
    }

    public function test_submission_form_has_four_labeled_fields_without_id_or_status_inputs(): void
    {
        $this->get('/service-requests/create')->assertOk()
            ->assertSee('Resident ID')->assertSee('Service Type')
            ->assertSee('Description')->assertSee('Date Requested')
            ->assertSee('View Residents')
            ->assertDontSee('name="id"', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_valid_submission_uses_existing_service_and_persists_pending_request(): void
    {
        $resident = $this->resident();
        $this->post('/service-requests', [
            'residentId' => $resident->id,
            'serviceType' => 'Barangay Clearance',
            'description' => 'Needed for employment',
            'dateRequested' => '2026-10-20',
            'id' => 999,
            'status' => 'Completed',
        ])->assertRedirect('/service-requests')->assertSessionHas('success');

        $request = (new ServiceRequestRepository)->findAll()->first();
        $this->assertNotNull($request);
        $this->assertGreaterThan(0, $request->id);
        $this->assertSame($resident->id, $request->residentId);
        $this->assertSame('Barangay Clearance', $request->serviceType);
        $this->assertSame('Needed for employment', $request->description);
        $this->assertSame('2026-10-20', $request->dateRequested);
        $this->assertSame('Pending', $request->status);
        $this->get('/service-requests')->assertOk()->assertSee('Barangay Clearance');
    }

    public function test_invalid_submission_shows_feedback_preserves_input_and_does_not_persist(): void
    {
        $resident = $this->resident();
        $this->post('/service-requests', [
            'residentId' => $resident->id,
            'serviceType' => '   ',
            'description' => '',
            'dateRequested' => 'not-a-date',
        ])->assertRedirect('/service-requests/create')
            ->assertSessionHasErrors(['serviceType', 'description', 'dateRequested'])
            ->assertSessionHas('_old_input.residentId', (string) $resident->id);

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_missing_resident_submission_shows_feedback_without_persisting(): void
    {
        $this->post('/service-requests', [
            'residentId' => '99999',
            'serviceType' => 'Barangay Clearance',
            'description' => 'Employment',
            'dateRequested' => '2026-10-20',
        ])->assertRedirect('/service-requests/create')
            ->assertSessionHasErrors(['residentId']);

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_inactive_resident_submission_shows_feedback_without_persisting(): void
    {
        $resident = $this->resident('Inactive');
        $this->post('/service-requests', [
            'residentId' => $resident->id,
            'serviceType' => 'Barangay Clearance',
            'description' => 'Employment',
            'dateRequested' => '2026-10-20',
        ])->assertRedirect('/service-requests/create')
            ->assertSessionHasErrors(['residentId']);

        $this->assertDatabaseCount('service_requests', 0);
        $this->assertSame('Inactive', $resident->fresh()->status);
    }

    public function test_details_page_displays_all_persisted_fields(): void
    {
        $request = $this->savedRequest();

        $this->get("/service-requests/{$request->id}")->assertOk()
            ->assertSee("#{$request->id}")
            ->assertSee("#{$request->residentId}")
            ->assertSee('Barangay Clearance')
            ->assertSee('Employment requirement')
            ->assertSee('2026-10-20')
            ->assertSee('Pending')
            ->assertSee('Start Processing')->assertSee('Cancel');
    }

    public function test_missing_request_details_return_404_without_creating_record(): void
    {
        $this->get('/service-requests/99999')->assertNotFound();
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_pending_request_can_start_processing_without_changing_other_fields(): void
    {
        $request = $this->savedRequest();

        $this->post("/service-requests/{$request->id}/start")
            ->assertRedirect("/service-requests/{$request->id}")->assertSessionHas('success');

        $saved = (new ServiceRequestRepository)->findById($request->id);
        $this->assertSame('In Progress', $saved->status);
        $this->assertSame($request->id, $saved->id);
        $this->assertSame($request->residentId, $saved->residentId);
        $this->assertSame($request->serviceType, $saved->serviceType);
        $this->assertSame($request->description, $saved->description);
        $this->assertSame($request->dateRequested, $saved->dateRequested);
    }

    public function test_pending_request_can_be_cancelled(): void
    {
        $request = $this->savedRequest();
        $this->post("/service-requests/{$request->id}/cancel")
            ->assertRedirect("/service-requests/{$request->id}")->assertSessionHas('success');
        $this->assertSame('Cancelled', (new ServiceRequestRepository)->findById($request->id)->status);
    }

    public function test_in_progress_request_can_be_completed(): void
    {
        $request = $this->savedRequest();
        $this->post("/service-requests/{$request->id}/start")->assertRedirect();
        $this->post("/service-requests/{$request->id}/complete")
            ->assertRedirect("/service-requests/{$request->id}")->assertSessionHas('success');
        $this->assertSame('Completed', (new ServiceRequestRepository)->findById($request->id)->status);
    }

    public function test_in_progress_request_can_be_cancelled(): void
    {
        $request = $this->savedRequest();
        $this->post("/service-requests/{$request->id}/start")->assertRedirect();
        $this->post("/service-requests/{$request->id}/cancel")
            ->assertRedirect("/service-requests/{$request->id}")->assertSessionHas('success');
        $this->assertSame('Cancelled', (new ServiceRequestRepository)->findById($request->id)->status);
    }

    public function test_direct_invalid_transition_is_rejected_by_existing_workflow(): void
    {
        $request = $this->savedRequest();
        $this->post("/service-requests/{$request->id}/complete")
            ->assertRedirect("/service-requests/{$request->id}")
            ->assertSessionHasErrors(['status']);
        $this->assertSame('Pending', (new ServiceRequestRepository)->findById($request->id)->status);
    }

    public function test_terminal_requests_remain_listed_searchable_filterable_and_viewable(): void
    {
        $resident = $this->resident();
        $completed = $this->savedRequest($resident, 'Completed Clearance', status: 'Completed');
        $cancelled = $this->savedRequest($resident, 'Cancelled Assistance', status: 'Cancelled');

        $this->get('/service-requests')->assertOk()
            ->assertSee('Completed Clearance')->assertSee('Cancelled Assistance');
        $this->get('/service-requests?search=Clearance&status=Completed')->assertOk()
            ->assertSee('Completed Clearance')->assertDontSee('Cancelled Assistance');
        $this->get('/service-requests?search=Assistance&status=Cancelled')->assertOk()
            ->assertSee('Cancelled Assistance')->assertDontSee('Completed Clearance');

        foreach ([$completed, $cancelled] as $request) {
            $this->get("/service-requests/{$request->id}")->assertOk()
                ->assertSee($request->status)
                ->assertDontSee("/service-requests/{$request->id}/start", false)
                ->assertDontSee("/service-requests/{$request->id}/complete", false)
                ->assertDontSee("/service-requests/{$request->id}/cancel", false);
        }
    }

    public function test_get_requests_do_not_create_or_modify_requests(): void
    {
        $request = $this->savedRequest();

        $this->get('/service-requests')->assertOk();
        $this->get('/service-requests?search=clear&status=Pending')->assertOk();
        $this->get('/service-requests/create')->assertOk();
        $this->get("/service-requests/{$request->id}")->assertOk();
        $this->get("/service-requests/{$request->id}/start")->assertStatus(405);
        $this->get("/service-requests/{$request->id}/complete")->assertStatus(405);
        $this->get("/service-requests/{$request->id}/cancel")->assertStatus(405);

        $this->assertDatabaseCount('service_requests', 1);
        $this->assertSame('Pending', (new ServiceRequestRepository)->findById($request->id)->status);
    }

    public function test_invalid_status_filter_and_missing_mutation_are_handled_safely(): void
    {
        $this->get('/service-requests?status=Unsupported')
            ->assertRedirect('/service-requests')->assertSessionHasErrors(['status']);
        $this->post('/service-requests/99999/start')->assertNotFound();
        $this->post('/service-requests/99999/complete')->assertNotFound();
        $this->post('/service-requests/99999/cancel')->assertNotFound();
    }

    public function test_resident_and_service_request_pages_link_to_each_other(): void
    {
        $this->get('/residents')->assertOk()->assertSee('Service Requests');
        $this->get('/service-requests')->assertOk()->assertSee('Residents');
        $this->get('/')->assertOk()->assertSee('Open Service Request Management');
    }
}
