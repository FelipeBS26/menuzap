<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // HasTenant aqui é um no-op seguro quando TenantContext não está setado
    // (rotas do super admin não passam pelos middlewares de tenant).
    use HasTenant, HasUuidPrimaryKey;

    public $timestamps = true;
    const UPDATED_AT = null; // log é imutável — não faz sentido ter updated_at

    protected $fillable = [
        'user_id', 'tenant_id', 'action', 'target_type', 'target_id', 'payload', 'ip_address',
    ];

    protected $casts = ['payload' => 'array'];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, ?string $targetType = null, ?string $targetId = null, array $payload = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()?->tenant_id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
            'ip_address' => request()->ip(),
        ]);
    }
}