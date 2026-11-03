<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;

class DashboardService
{
    public function __construct(
        private ResidentRepository $residents,
        private ServiceRequestRepository $serviceRequests,
    ) {}

    public function summary(): array
    {
        $residentCounts = $this->residents->countByStatus();
        $requestCounts = $this->serviceRequests->countByStatus();
        $pending = $requestCounts[ServiceRequest::STATUS_PENDING] ?? 0;
        $inProgress = $requestCounts[ServiceRequest::STATUS_IN_PROGRESS] ?? 0;

        return [
            'residents' => [
                'total' => $this->residents->countAll(),
                'active' => $residentCounts[Resident::STATUS_ACTIVE] ?? 0,
                'inactive' => $residentCounts['Inactive'] ?? 0,
            ],
            'serviceRequests' => [
                'total' => $this->serviceRequests->countAll(),
                'pending' => $pending,
                'inProgress' => $inProgress,
                'completed' => $requestCounts[ServiceRequest::STATUS_COMPLETED] ?? 0,
                'cancelled' => $requestCounts[ServiceRequest::STATUS_CANCELLED] ?? 0,
                'open' => $pending + $inProgress,
            ],
            'recentRequests' => $this->serviceRequests->findRecent(5),
        ];
    }
}
