<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    public const STATUS_ACTIVE = 'Active';

    protected $fillable = [
        'id',
        'firstName',
        'lastName',
        'address',
        'contactNumber',
        'email',
        'status',
    ];
}
