<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderLog extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'short_id', 'customer_name', 'customer_phone', 'order_type',
        'payment_method', 'total_cents', 'items_snapshot', 'whatsapp_message', 'ip_address',
    ];

    protected $casts = [
        'short_id' => 'integer',
        'total_cents' => 'integer',
        'items_snapshot' => 'array', // imutável — nunca reescrito após criado
    ];
}