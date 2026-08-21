<?php

namespace Tests\Feature;

use App\Models\Resident;
use Tests\TestCase;

class ResidentTest extends TestCase
{
    public function test_resident_can_be_created_with_valid_information(): void
    {
        $resident = new Resident([
            'id' => 1,
            'firstName' => 'Juan',
            'lastName' => 'Dela Cruz',
            'address' => '123 Main Street',
            'contactNumber' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]);

        $this->assertEquals(1, $resident->id);
        $this->assertEquals('Juan', $resident->firstName);
        $this->assertEquals('Dela Cruz', $resident->lastName);
    }

    public function test_resident_information_can_be_assigned_and_retrieved(): void
    {
        $resident = new Resident();

        $resident->id = 2;
        $resident->firstName = 'Maria';
        $resident->lastName = 'Santos';
        $resident->address = '456 Community Road';
        $resident->contactNumber = '09181234567';
        $resident->email = 'maria@example.com';

        $this->assertEquals(2, $resident->id);
        $this->assertEquals('Maria', $resident->firstName);
        $this->assertEquals('Santos', $resident->lastName);
        $this->assertEquals('456 Community Road', $resident->address);
        $this->assertEquals('09181234567', $resident->contactNumber);
        $this->assertEquals('maria@example.com', $resident->email);
    }

    public function test_resident_can_have_active_status(): void
    {
        $resident = new Resident([
            'id' => 3,
            'firstName' => 'Pedro',
            'lastName' => 'Garcia',
            'address' => '789 Barangay Road',
            'contactNumber' => '09191234567',
            'email' => 'pedro@example.com',
            'status' => 'Active',
        ]);

        $this->assertEquals('Active', $resident->status);
    }
}