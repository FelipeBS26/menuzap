<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'logo_url', 'banner_url',
        'primary_color', 'secondary_color', 'whatsapp_number', 'whatsapp_contact',
        'instagram_url', 'delivery_fee_cents', 'min_order_cents', 'estimated_time_min',
        'accepts_delivery', 'accepts_pickup', 'accepts_dine_in', 'payment_methods',
        'is_open', 'closed_message', 'whatsapp_message_emoji',
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'accepts_delivery' => 'boolean',
        'accepts_pickup' => 'boolean',
        'accepts_dine_in' => 'boolean',
        'is_open' => 'boolean',
        'whatsapp_message_emoji' => 'boolean',
        'delivery_fee_cents' => 'integer',
        'min_order_cents' => 'integer',
        'estimated_time_min' => 'integer',
    ];

    // business_hours referencia tenant_id diretamente, não store_id
    public function businessHours()
    {
        return $this->hasMany(BusinessHour::class, 'tenant_id', 'tenant_id')->orderBy('day_of_week');
    }
}