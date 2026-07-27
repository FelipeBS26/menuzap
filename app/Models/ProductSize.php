<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSize extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = ['product_id', 'tenant_id', 'name', 'price_cents', 'sort_order'];

    protected $casts = [
        'price_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product() { return $this->belongsTo(Product::class); }
}