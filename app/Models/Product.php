<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasTenant, HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'description', 'base_price_cents',
        'image_url', 'thumbnail_url', 'badge', 'sort_order', 'is_active', 'has_sizes',
    ];

    protected $casts = [
        'base_price_cents' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'has_sizes' => 'boolean',
    ];

    public function category() { return $this->belongsTo(Category::class); }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order');
    }

    public function optionGroups()
    {
        return $this->belongsToMany(OptionGroup::class, 'product_option_groups')
            ->using(ProductOptionGroup::class)
            ->withPivot(['min_selections', 'max_selections', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    /**
     * Preço "a partir de" — usado em listagens quando o produto tem tamanhos.
     */
    public function displayPriceCents(): int
    {
        if ($this->has_sizes) {
            return $this->sizes->min('price_cents') ?? $this->base_price_cents;
        }

        return $this->base_price_cents;
    }
}