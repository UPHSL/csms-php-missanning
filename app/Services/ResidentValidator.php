<?php

namespace App\Services;

use App\Models\Resident;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

class ResidentValidator
{
    public function validate(Resident $resident): ValidatorContract
    {
        $attrs = $resident->getAttributes();

        return Validator::make(
            [
                'first_name' => $attrs['first_name'] ?? $attrs['firstName'] ?? null,
                'last_name' => $attrs['last_name'] ?? $attrs['lastName'] ?? null,
                'address' => $attrs['address'] ?? null,
                'contact_number' => $attrs['contact_number'] ?? $attrs['contactNumber'] ?? null,
                'email' => $attrs['email'] ?? null,
                'status' => $attrs['status'] ?? null,
            ],
            self::rules()
        );
    }

    public function isValid(Resident $resident): bool
    {
        return ! $this->validate($resident)->fails();
    }

    public static function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'not_regex:/^\s*$/',
            ],

            'last_name' => [
                'required',
                'string',
                'not_regex:/^\s*$/',
            ],

            'address' => [
                'required',
                'string',
                'not_regex:/^\s*$/',
            ],

            'contact_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],

            'email' => [
                'required',
                'string',
                'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
            ],

            'status' => [
                'required',
                'string',
                'in:Active,Inactive',
            ],
        ];
    }
}
