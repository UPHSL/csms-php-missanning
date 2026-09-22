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

    public function listResidents(): Collection
    {
        return Resident::orderBy('lastName')
            ->orderBy('firstName')
            ->orderBy('id')
            ->get();
    }

    public function searchByName(string $term): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return $this->listResidents();
        }

        return Resident::where(function ($query) use ($term) {
            $query->whereRaw('LOWER(lastName) LIKE ?', ['%'.strtolower($term).'%'])
                ->orWhereRaw('LOWER(firstName) LIKE ?', ['%'.strtolower($term).'%']);
        })
            ->orderBy('lastName')
            ->orderBy('firstName')
            ->orderBy('id')
            ->get();
    }
}
