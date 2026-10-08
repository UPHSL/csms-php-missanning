<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestStatusUpdateResult
{
    public function __construct(
        public bool $success,
        public ?ServiceRequest $serviceRequest = null,
        public array $errors = [],
        public ?string $failureReason = null,
    ) {}

    public static function successful(ServiceRequest $request): self
    {
        return new self(
            success: true,
            serviceRequest: $request,
        );
    }

    public static function failed(string $reason, array $errors = []): self
    {
        return new self(
            success: false,
            errors: $errors,
            failureReason: $reason,
        );
    }
}