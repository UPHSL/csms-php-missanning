<?php

namespace App\Models;

class ServiceRequest
{
    public const STATUS_PENDING = 'Pending';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';

    public ?int $id = null;

    public function __construct(
        public int $residentId,
        public string $serviceType,
        public string $description,
        public string $dateRequested,
        public string $status = self::STATUS_PENDING,
    ) {}
}
