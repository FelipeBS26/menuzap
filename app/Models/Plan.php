<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'price_cents', 'max_products', 'max_categories', 'features', 'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price_cents' => 'integer',
        'max_products' => 'integer',
        'max_categories' => 'integer',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}