<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceRequestValidator
{
    public function validate(ServiceRequest $request): ValidatorContract
    {
        return Validator::make([
            'id' => $request->id,
            'residentId' => $request->residentId,
            'serviceType' => $request->serviceType,
            'description' => $request->description,
            'dateRequested' => $request->dateRequested,
            'status' => $request->status,
        ], [
            'id' => [function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value !== null) {
                    $fail('A new service request must have an unassigned ID.');
                }
            }],
            'residentId' => ['required', 'integer', 'min:1'],
            'serviceType' => ['required', 'string', 'not_regex:/^\s*$/u'],
            'description' => ['required', 'string', 'not_regex:/^\s*$/u'],
            'dateRequested' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in([ServiceRequest::STATUS_PENDING])],
        ]);
    }

    public function isValid(ServiceRequest $request): bool
    {
        return $this->validate($request)->passes();
    }
}
