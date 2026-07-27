<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptionGroup extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items()
    {
        return $this->hasMany(OptionItem::class)->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_option_groups')
            ->using(ProductOptionGroup::class)
            ->withPivot(['min_selections', 'max_selections', 'sort_order']);
    }
}