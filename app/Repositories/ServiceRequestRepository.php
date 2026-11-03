<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceRequestRepository
{
    public function countAll(): int
    {
        return DB::table('service_requests')->count();
    }

    public function countByStatus(): array
    {
        return DB::table('service_requests')
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function findRecent(int $limit): Collection
    {
        return $this->orderedQuery(DB::table('service_requests'))
            ->limit($limit)
            ->get()
            ->map(fn (object $row): ServiceRequest => $this->fromRow($row));
    }

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

        return $row === null ? null : $this->fromRow($row);
    }

    public function findAll(): Collection
    {
        return $this->search(null, null);
    }

    public function search(?string $searchTerm, ?string $statusFilter): Collection
    {
        $query = DB::table('service_requests');

        if ($searchTerm !== null && $searchTerm !== '') {
            $escapedTerm = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $searchTerm);
            $pattern = '%'.$escapedTerm.'%';

            $query->where(function ($query) use ($pattern) {
                $query->whereRaw("LOWER(service_type) LIKE LOWER(?) ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(description) LIKE LOWER(?) ESCAPE '!'", [$pattern]);
            });
        }

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        return $this->orderedQuery($query)
            ->get()
            ->map(fn (object $row): ServiceRequest => $this->fromRow($row));
    }

    private function orderedQuery(Builder $query): Builder
    {
        return $query->orderBy('date_requested', 'desc')
            ->orderBy('id', 'desc');
    }

    private function fromRow(object $row): ServiceRequest
    {
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
