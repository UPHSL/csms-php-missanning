<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\ServiceRequestQueryService;
use App\Services\ServiceRequestStatusUpdateService;
use App\Services\ServiceRequestSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ServiceRequestController extends Controller
{
    public function __construct(
        private ServiceRequestQueryService $queries,
        private ServiceRequestSubmissionService $submission,
        private ServiceRequestStatusUpdateService $statuses,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $search = is_string($search) ? $search : '';
        $status = is_string($status) ? trim($status) : '';

        try {
            $requests = $this->queries->searchServiceRequests($search, $status);
        } catch (InvalidArgumentException) {
            return redirect()->route('service-requests.index')
                ->withErrors(['status' => 'Choose a supported status.']);
        }

        return view('service-requests.index', [
            'requests' => $requests,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('service-requests.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $this->submissionInput($request);
        $residentId = filter_var($input['residentId'], FILTER_VALIDATE_INT);
        $serviceRequest = new ServiceRequest(
            $residentId === false ? 0 : $residentId,
            $input['serviceType'],
            $input['description'],
            $input['dateRequested'],
        );
        $result = $this->submission->submit($serviceRequest);

        if (! $result->success) {
            return redirect()->route('service-requests.create')
                ->withErrors($result->errors)
                ->withInput($input);
        }

        return redirect()->route('service-requests.index')
            ->with('success', 'Service Request submitted successfully.');
    }

    public function show(int $id): View
    {
        $serviceRequest = $this->queries->viewServiceRequest($id);
        abort_if($serviceRequest === null, 404);

        return view('service-requests.show', ['serviceRequest' => $serviceRequest]);
    }

    public function start(int $id): RedirectResponse
    {
        return $this->transition($id, ServiceRequest::STATUS_IN_PROGRESS);
    }

    public function complete(int $id): RedirectResponse
    {
        return $this->transition($id, ServiceRequest::STATUS_COMPLETED);
    }

    public function cancel(int $id): RedirectResponse
    {
        return $this->transition($id, ServiceRequest::STATUS_CANCELLED);
    }

    private function transition(int $id, string $status): RedirectResponse
    {
        $result = $this->statuses->update($id, $status);
        abort_if($result->failureReason === 'not_found', 404);

        if (! $result->success) {
            return redirect()->route('service-requests.show', $id)
                ->withErrors($result->errors);
        }

        return redirect()->route('service-requests.show', $id)
            ->with('success', match ($status) {
                ServiceRequest::STATUS_IN_PROGRESS => 'Service Request is now In Progress.',
                ServiceRequest::STATUS_COMPLETED => 'Service Request completed successfully.',
                ServiceRequest::STATUS_CANCELLED => 'Service Request cancelled successfully.',
            });
    }

    private function submissionInput(Request $request): array
    {
        $input = [];

        foreach (['residentId', 'serviceType', 'description', 'dateRequested'] as $field) {
            $value = $request->input($field);
            $input[$field] = is_string($value) || is_int($value) ? (string) $value : '';
        }

        return $input;
    }
}
