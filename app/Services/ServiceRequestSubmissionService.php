<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;

class ServiceRequestSubmissionService
{
    public function __construct(
        private ServiceRequestValidator $validator,
        private ResidentRepository $residentRepository,
        private ServiceRequestRepository $serviceRequestRepository,
    ) {}

    public function submit(ServiceRequest $request): ServiceRequestSubmissionResult
    {
        $validation = $this->validator->validate($request);

        if ($validation->fails()) {
            return ServiceRequestSubmissionResult::failed('validation_failed', $validation->errors()->toArray());
        }

        $resident = $this->residentRepository->findById($request->residentId);

        if ($resident === null) {
            return ServiceRequestSubmissionResult::failed('resident_not_found', [
                'residentId' => ['The referenced Resident was not found.'],
            ]);
        }

        if ($resident->status !== Resident::STATUS_ACTIVE) {
            return ServiceRequestSubmissionResult::failed('resident_inactive', [
                'residentId' => ['Only Active Residents may submit new service requests.'],
            ]);
        }

        return ServiceRequestSubmissionResult::successful($this->serviceRequestRepository->save($request));
    }
}
