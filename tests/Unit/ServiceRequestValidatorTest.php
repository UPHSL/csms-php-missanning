<?php

namespace Tests\Unit;

use App\Models\ServiceRequest;
use App\Services\ServiceRequestValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceRequestValidatorTest extends TestCase
{
    private function request(): ServiceRequest
    {
        return new ServiceRequest(25, 'Barangay Clearance', 'Request for employment requirement', '2026-09-15');
    }

    public function test_valid_request_passes_without_resident_lookup(): void
    {
        $validator = new ServiceRequestValidator;

        $this->assertTrue($validator->isValid($this->request()));
        $this->assertSame([], $validator->validate($this->request())->errors()->toArray());
    }

    public static function invalidFields(): array
    {
        return [
            'zero assigned id' => ['id', 0],
            'assigned id' => ['id', 10],
            'zero resident' => ['residentId', 0],
            'negative resident' => ['residentId', -1],
            'empty service' => ['serviceType', ''],
            'blank service' => ['serviceType', " \t\n"],
            'empty description' => ['description', ''],
            'blank description' => ['description', " \t\n"],
            'absent date' => ['dateRequested', ''],
            'invalid date' => ['dateRequested', 'invalid'],
            'impossible date' => ['dateRequested', '2026-02-30'],
            'invalid leap date' => ['dateRequested', '2025-02-29'],
            'noncanonical date' => ['dateRequested', '2026-9-15'],
            'empty status' => ['status', ''],
            'completed' => ['status', 'Completed'],
            'cancelled' => ['status', 'Cancelled'],
            'in progress' => ['status', 'In Progress'],
            'wrong case status' => ['status', 'pending'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_rejects_invalid_field_with_identifiable_error(string $field, int|string $value): void
    {
        $request = $this->request();
        $request->$field = $value;
        $validator = new ServiceRequestValidator;
        $validation = $validator->validate($request);

        $this->assertTrue($validation->fails());
        $this->assertTrue($validation->errors()->has($field));
        $this->assertFalse($validator->isValid($request));
    }

    public function test_reports_multiple_invalid_fields(): void
    {
        $request = $this->request();
        $request->residentId = 0;
        $request->serviceType = '';
        $request->description = '';

        $errors = (new ServiceRequestValidator)->validate($request)->errors();

        foreach (['residentId', 'serviceType', 'description'] as $field) {
            $this->assertTrue($errors->has($field));
        }
    }

    public static function validDates(): array
    {
        return [['2024-02-29'], ['2000-01-01'], ['2099-12-31']];
    }

    #[DataProvider('validDates')]
    public function test_accepts_real_dates_without_time_sensitive_restrictions(string $date): void
    {
        $request = $this->request();
        $request->dateRequested = $date;
        $request->serviceType = 'A different community service';

        $this->assertTrue((new ServiceRequestValidator)->isValid($request));
        $this->assertSame($date, $request->dateRequested);
    }
}
