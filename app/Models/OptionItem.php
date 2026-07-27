<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptionItem extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = ['option_group_id', 'tenant_id', 'name', 'price_cents', 'sort_order', 'is_active'];

    protected $casts = [
        'price_cents' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function group() { return $this->belongsTo(OptionGroup::class, 'option_group_id'); }
}