<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A pivot mais importante do sistema. min_selections/max_selections vivem
 * AQUI, não em OptionGroup — o mesmo grupo "Bordas recheadas" pode ser
 * obrigatório numa pizza e opcional noutro produto (decisão da Fase 5).
 * Sem soft delete de propósito: desvincular = deletar a linha.
 */
class ProductOptionGroup extends Pivot
{
    use HasTenant, HasUuidPrimaryKey;

    protected $table = 'product_option_groups';

    protected $fillable = [
        'product_id', 'option_group_id', 'tenant_id', 'min_selections', 'max_selections', 'sort_order',
    ];

    protected $casts = [
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];

    public function isRequired(): bool
    {
        return $this->min_selections > 0;
    }

    public function isExact(): bool
    {
        return $this->min_selections === $this->max_selections;
    }
}