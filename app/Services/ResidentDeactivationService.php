<?php

namespace App\Services;

use App\Repositories\ResidentRepository;

class ResidentDeactivationService
{
    public function __construct(
        private ResidentRepository $repository
    ) {}

    public function deactivate(int $id): array
    {
        $resident = $this->repository->findById($id);

        if ($resident === null) {
            return [
                'success' => false,
                'notFound' => true,
                'alreadyInactive' => false,
                'resident' => null,
            ];
        }

        if ($resident->status === 'Inactive') {
            return [
                'success' => true,
                'notFound' => false,
                'alreadyInactive' => true,
                'resident' => $resident,
            ];
        }

        $deactivated = $this->repository->deactivateById($id);

        return [
            'success' => true,
            'notFound' => false,
            'alreadyInactive' => false,
            'resident' => $deactivated,
        ];
    }
}
