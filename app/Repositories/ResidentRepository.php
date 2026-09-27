<?php

namespace App\Repositories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Collection;

class ResidentRepository
{
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

    public function searchByName(
        string $searchTerm
    ): Collection {
        $pattern =
            '%'.$searchTerm.'%';

        return Resident::query()
            ->where(
                function ($query) use ($pattern) {
                    $query
                        ->whereRaw(
                            'LOWER(firstName) LIKE LOWER(?)',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'LOWER(lastName) LIKE LOWER(?)',
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
