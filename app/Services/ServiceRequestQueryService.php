<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;
use Illuminate\Support\Collection;

class ServiceRequestQueryService
{
    public function __construct(
        private ServiceRequestRepository $repository,
    ) {}

    public function listServiceRequests(): Collection
    {
        return $this->repository->findAll();
    }

    public function viewServiceRequest(int $id): ?ServiceRequest
    {
        return $this->repository->findById($id);
    }

    public function searchServiceRequests(?string $searchTerm = null, ?string $statusFilter = null): Collection
    {
        $searchTerm = trim($searchTerm ?? '');
        $statusFilter = trim($statusFilter ?? '');

        if ($statusFilter !== '' && ! in_array($statusFilter, [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_COMPLETED,
            ServiceRequest::STATUS_CANCELLED,
        ], true)) {
            throw new \InvalidArgumentException('Unsupported Service Request status filter.');
        }

        if ($searchTerm === '' && $statusFilter === '') {
            return $this->listServiceRequests();
        }

        return $this->repository->search(
            $searchTerm === '' ? null : $searchTerm,
            $statusFilter === '' ? null : $statusFilter,
        );
    }
}
