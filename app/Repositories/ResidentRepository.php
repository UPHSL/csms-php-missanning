<?php

namespace App\Repositories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Collection;

class ResidentRepository
{
    public function countAll(): int
    {
        return Resident::query()->count();
    }

    public function countByStatus(): array
    {
        return Resident::query()
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function save(Resident $resident): Resident
    {
        $resident->save();

        return $resident;
    }

    public function findById(int $id): ?Resident
    {
        return Resident::find($id);
    }

    public function findAll(): Collection
    {
        return Resident::query()
            ->orderByRaw(
                'LOWER(lastName) ASC'
            )
            ->orderByRaw(
                'LOWER(firstName) ASC'
            )
            ->orderBy(
                'id',
                'asc'
            )
            ->get();
    }
    public function update(Resident $resident): Resident
    {
        $resident->save();

        return $resident;
    }

    public function deactivateById(int $id): ?Resident
    {
        $resident = $this->findById($id);

        if ($resident === null) {
            return null;
        }

        $resident->status = 'Inactive';
        $resident->save();

        return $resident;
    }


    public function searchByName(
        string $searchTerm
    ): Collection {
        $escapedTerm = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $searchTerm);
        $pattern = '%'.$escapedTerm.'%';

        return Resident::query()
            ->where(
                function ($query) use ($pattern) {
                    $query
                        ->whereRaw(
                            "LOWER(firstName) LIKE LOWER(?) ESCAPE '!'",
                            [$pattern]
                        )
                        ->orWhereRaw(
                            "LOWER(lastName) LIKE LOWER(?) ESCAPE '!'",
                            [$pattern]
                        );
                }
            )
            ->orderByRaw(
                'LOWER(lastName) ASC'
            )
            ->orderByRaw(
                'LOWER(firstName) ASC'
            )
            ->orderBy(
                'id',
                'asc'
            )
            ->get();
    }
}
