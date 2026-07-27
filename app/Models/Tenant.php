<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    use HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = [
        'plan_id', 'slug', 'custom_domain', 'status', 'trial_ends_at',
        'plan_expires_at', 'storage_used_bytes', 'order_counter',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'plan_expires_at' => 'datetime',
        'storage_used_bytes' => 'integer',
        'order_counter' => 'integer',
    ];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function store() { return $this->hasOne(Store::class); }
    public function users() { return $this->hasMany(User::class); }
    public function categories() { return $this->hasMany(Category::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function businessHours() { return $this->hasMany(BusinessHour::class); }
    public function optionGroups() { return $this->hasMany(OptionGroup::class); }
    public function orderLogs() { return $this->hasMany(OrderLog::class); }

    public function isOpen(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    /**
     * Incrementa o contador de pedidos atomicamente via UPDATE...RETURNING —
     * decisão da Fase 5 para evitar a race condition de MAX(short_id)+1
     * quando dois pedidos chegam no mesmo segundo.
     */
    public function nextOrderNumber(): int
    {
        $result = DB::selectOne(
            'UPDATE tenants SET order_counter = order_counter + 1 WHERE id = ? RETURNING order_counter',
            [$this->id]
        );

        return $result->order_counter;
    }
}