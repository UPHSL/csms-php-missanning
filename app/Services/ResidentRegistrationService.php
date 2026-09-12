<?php

namespace App\Services;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Validation\ValidationException;

class ResidentRegistrationService
{
    public function __construct(
        private ResidentValidator $validator,
        private ResidentRepository $repository,
    ) {}

    public function register(Resident $resident): Resident
    {
        $validation = $this->validator->validate($resident);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        return $this->repository->save($resident);
    }
}
