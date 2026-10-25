<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\ServiceRequestQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceRequestQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function queryService(): ServiceRequestQueryService
    {
        return new ServiceRequestQueryService(new ServiceRequestRepository);
    }

    private function saveRequest(
        string $type = 'Barangay Clearance',
        string $description = 'Employment requirement',
        string $date = '2026-09-15',
        string $status = ServiceRequest::STATUS_PENDING,
    ): ServiceRequest {
        $resident = (new ResidentRepository)->save(new Resident([
            'firstName' => 'Juan',
            'lastName' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]));

        return (new ServiceRequestRepository)->save(new ServiceRequest(
            $resident->id, $type, $description, $date, $status,
        ));
    }

    private function ids(Collection $requests): array
    {
        return $requests->pluck('id')->all();
    }

    public function test_lists_all_persisted_requests(): void
    {
        $first = $this->saveRequest();
        $second = $this->saveRequest('Community Assistance');

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $this->ids($this->queryService()->listServiceRequests()));
    }

    public function test_empty_listing_returns_an_empty_collection(): void
    {
        $requests = $this->queryService()->listServiceRequests();

        $this->assertInstanceOf(Collection::class, $requests);
        $this->assertTrue($requests->isEmpty());
    }

    public function test_listing_orders_by_date_then_id_descending(): void
    {
        $middle = $this->saveRequest(date: '2026-09-15');
        $oldest = $this->saveRequest(date: '2026-09-10');
        $newer = $this->saveRequest(date: '2026-09-18');
        $newest = $this->saveRequest(date: '2026-09-18');

        $this->assertSame([$newest->id, $newer->id, $middle->id, $oldest->id], $this->ids($this->queryService()->listServiceRequests()));
    }

    public function test_views_existing_request_by_id(): void
    {
        $saved = $this->saveRequest();

        $this->assertSame($saved->id, $this->queryService()->viewServiceRequest($saved->id)->id);
    }

    public function test_unknown_id_returns_null_without_inserting(): void
    {
        $this->assertNull($this->queryService()->viewServiceRequest(99999));
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_partial_service_type_search_is_case_insensitive(): void
    {
        $matching = $this->saveRequest('Barangay Clearance');
        $this->saveRequest('Community Assistance');

        $this->assertSame([$matching->id], $this->ids($this->queryService()->searchServiceRequests('  cLeAr  ')));
    }

    public function test_partial_description_search_is_case_insensitive(): void
    {
        $matching = $this->saveRequest(description: 'Needed for EMPLOYMENT');
        $this->saveRequest(description: 'Scholarship');

        $this->assertSame([$matching->id], $this->ids($this->queryService()->searchServiceRequests('ployM')));
    }

    public function test_blank_search_returns_all_in_listing_order(): void
    {
        $this->saveRequest(date: '2026-09-10');
        $this->saveRequest(date: '2026-09-18');

        $service = $this->queryService();
        $expected = $this->ids($service->listServiceRequests());
        $this->assertSame($expected, $this->ids($service->searchServiceRequests('      ')));
        $this->assertSame($expected, $this->ids($service->searchServiceRequests(null)));
    }

    public function test_no_text_match_returns_empty_collection(): void
    {
        $this->saveRequest();

        $results = $this->queryService()->searchServiceRequests('no-match');
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertTrue($results->isEmpty());
    }

    public function test_status_filter_returns_only_matching_status(): void
    {
        $pending = $this->saveRequest(status: ServiceRequest::STATUS_PENDING);
        $this->saveRequest(status: ServiceRequest::STATUS_COMPLETED);

        $this->assertSame([$pending->id], $this->ids($this->queryService()->searchServiceRequests(null, ServiceRequest::STATUS_PENDING)));
    }

    public function test_unfiltered_listing_includes_all_four_statuses(): void
    {
        foreach ([ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_COMPLETED, ServiceRequest::STATUS_CANCELLED] as $status) {
            $this->saveRequest(status: $status);
        }

        $this->assertEqualsCanonicalizing(
            [ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_COMPLETED, ServiceRequest::STATUS_CANCELLED],
            $this->queryService()->listServiceRequests()->pluck('status')->all(),
        );
    }

    public function test_combined_search_requires_text_and_status(): void
    {
        $both = $this->saveRequest('Barangay Clearance', status: ServiceRequest::STATUS_PENDING);
        $this->saveRequest('Barangay Clearance', status: ServiceRequest::STATUS_COMPLETED);
        $this->saveRequest('Community Assistance', status: ServiceRequest::STATUS_PENDING);
        $this->saveRequest('Community Assistance', status: ServiceRequest::STATUS_COMPLETED);

        $this->assertSame([$both->id], $this->ids($this->queryService()->searchServiceRequests('clear', ServiceRequest::STATUS_PENDING)));
    }

    public function test_match_in_both_text_fields_appears_once(): void
    {
        $saved = $this->saveRequest('Community Assistance', 'Assistance for medical support');

        $this->assertSame([$saved->id], $this->ids($this->queryService()->searchServiceRequests('assistance')));
    }

    public function test_query_results_preserve_all_six_fields_and_do_not_change_storage(): void
    {
        $saved = $this->saveRequest('Barangay Clearance', 'Employment requirement', '2026-09-18', ServiceRequest::STATUS_COMPLETED);
        $before = DB::table('service_requests')->where('id', $saved->id)->first();
        $service = $this->queryService();

        foreach ([$service->listServiceRequests()->first(), $service->searchServiceRequests('employment')->first(), $service->viewServiceRequest($saved->id)] as $found) {
            $this->assertInstanceOf(ServiceRequest::class, $found);
            $this->assertSame($saved->id, $found->id);
            $this->assertSame($saved->residentId, $found->residentId);
            $this->assertSame('Barangay Clearance', $found->serviceType);
            $this->assertSame('Employment requirement', $found->description);
            $this->assertSame('2026-09-18', $found->dateRequested);
            $this->assertSame(ServiceRequest::STATUS_COMPLETED, $found->status);
        }

        $this->assertEquals($before, DB::table('service_requests')->where('id', $saved->id)->first());
    }

    public function test_unsupported_status_filter_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->queryService()->searchServiceRequests(null, 'Approved');
    }

    public function test_each_terminal_and_active_status_can_be_filtered(): void
    {
        foreach ([ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_COMPLETED, ServiceRequest::STATUS_CANCELLED] as $status) {
            $saved = $this->saveRequest(status: $status);
            $this->assertSame([$saved->id], $this->ids($this->queryService()->searchServiceRequests(null, $status)));
        }
    }

    public function test_search_does_not_match_unrelated_fields_and_orders_results(): void
    {
        $first = $this->saveRequest('Clearance', date: '2026-09-15');
        $second = $this->saveRequest('Clearance', date: '2026-09-18');
        $third = $this->saveRequest('Clearance', date: '2026-09-18');
        $this->saveRequest('Other', 'Different', '2026-09-18', ServiceRequest::STATUS_COMPLETED);

        $service = $this->queryService();
        $this->assertSame([$third->id, $second->id, $first->id], $this->ids($service->searchServiceRequests('clear')));
        $this->assertTrue($service->searchServiceRequests((string) $first->residentId)->isEmpty());
        $this->assertTrue($service->searchServiceRequests('Completed')->isEmpty());
    }
}
