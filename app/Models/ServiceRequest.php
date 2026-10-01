<?php

namespace App\Models;

class ServiceRequest
{
    public const STATUS_PENDING = 'Pending';

    public ?int $id = null;

    public function __construct(
        public int $residentId,
        public string $serviceType,
        public string $description,
        public string $dateRequested,
        public string $status = self::STATUS_PENDING,
    ) {}
}
