<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;

class ServiceRequestStatusUpdateService
{
    public function __construct(
        private ServiceRequestRepository $serviceRequestRepository,
    ) {}

    public function update(int $id, string $requestedStatus): ServiceRequestStatusUpdateResult
    {
        $request = $this->serviceRequestRepository->findById($id);

        if ($request === null) {
            return ServiceRequestStatusUpdateResult::failed(
                'not_found',
                ['id' => ['The requested Service Request was not found.']],
            );
        }

        if (!in_array($requestedStatus, [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_COMPLETED,
            ServiceRequest::STATUS_CANCELLED,
        ], true)) {
            return ServiceRequestStatusUpdateResult::failed(
                'unsupported_status',
                ['status' => ['The requested status is not supported.']],
            );
        }

        if (!$this->isAllowedTransition($request->status, $requestedStatus)) {
            return ServiceRequestStatusUpdateResult::failed(
                'invalid_transition',
                ['status' => ['The requested status transition is not allowed.']],
            );
        }

        $updated = $this->serviceRequestRepository->updateStatus(
            $request->id,
            $requestedStatus,
        );

        if ($updated === null) {
            return ServiceRequestStatusUpdateResult::failed(
                'not_found',
                ['id' => ['The requested Service Request was not found.']],
            );
        }

        return ServiceRequestStatusUpdateResult::successful($updated);
    }

    private function isAllowedTransition(string $currentStatus, string $requestedStatus): bool
    {
        return match ($currentStatus) {
            ServiceRequest::STATUS_PENDING =>
                in_array($requestedStatus, [
                    ServiceRequest::STATUS_IN_PROGRESS,
                    ServiceRequest::STATUS_CANCELLED,
                ], true),

            ServiceRequest::STATUS_IN_PROGRESS =>
                in_array($requestedStatus, [
                    ServiceRequest::STATUS_COMPLETED,
                    ServiceRequest::STATUS_CANCELLED,
                ], true),

            ServiceRequest::STATUS_COMPLETED,
            ServiceRequest::STATUS_CANCELLED => false,

            default => false,
        };
    }
}