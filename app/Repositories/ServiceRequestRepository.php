<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class ServiceRequestRepository
{
    public function save(ServiceRequest $request): ServiceRequest
    {
        if ($request->id !== null) {
            throw new \InvalidArgumentException('Only service requests with an unassigned ID can be saved.');
        }

        $request->id = DB::table('service_requests')->insertGetId([
            'resident_id' => $request->residentId,
            'service_type' => $request->serviceType,
            'description' => $request->description,
            'date_requested' => $request->dateRequested,
            'status' => $request->status,
        ]);

        return $request;
    }

    public function findById(int $id): ?ServiceRequest
    {
        $row = DB::table('service_requests')->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        $request = new ServiceRequest(
            residentId: (int) $row->resident_id,
            serviceType: $row->service_type,
            description: $row->description,
            dateRequested: $row->date_requested,
            status: $row->status,
        );
        $request->id = (int) $row->id;

        return $request;
    }

    public function updateStatus(int $id, string $status): ?ServiceRequest
    {
        $updated = DB::table('service_requests')
            ->where('id', $id)
            ->update(['status' => $status]);

        if ($updated === 0) {
            return null;
        }

        return $this->findById($id);
    }
}
