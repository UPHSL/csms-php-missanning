<?php

namespace App\Services;

use App\Models\Resident;
use App\Repositories\ResidentRepository;

class ResidentUpdateService
{
    public function __construct(
        private ResidentRepository $repository,
        private ResidentValidator $validator
    ) {}

    public function update(
        int $id,
        string $firstName,
        string $lastName,
        string $address,
        string $contactNumber,
        string $email
    ): array {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            return [
                'success' => false,
                'notFound' => true,
                'errors' => null,
                'resident' => null,
            ];
        }

        $existing->firstName = $firstName;
        $existing->lastName = $lastName;
        $existing->address = $address;
        $existing->contactNumber = $contactNumber;
        $existing->email = $email;

        $validation = $this->validator->validate($existing);

        if ($validation->fails()) {
            return [
                'success' => false,
                'notFound' => false,
                'errors' => $validation->errors()->toArray(),
                'resident' => null,
            ];
        }

        $updated = $this->repository->update($existing);

        return [
            'success' => true,
            'notFound' => false,
            'errors' => null,
            'resident' => $updated,
        ];
    }
}
