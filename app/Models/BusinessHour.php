<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessHour extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'day_of_week', 'opens_at', 'closes_at', 'is_overnight', 'is_24h', 'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_overnight' => 'boolean',
        'is_24h' => 'boolean',
        'is_active' => 'boolean',
    ];
}