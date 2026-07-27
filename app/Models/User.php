<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Deliberadamente SEM o trait HasTenant / TenantScope.
 * O login precisa localizar o usuário pelo e-mail ANTES de qualquer
 * contexto de tenant existir — e super_admins têm tenant_id nulo por design.
 * O isolamento de dados de outros models continua garantido por eles mesmos.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasUuidPrimaryKey, Notifiable, SoftDeletes;

    protected $fillable = ['tenant_id', 'email', 'name', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isTenantOwner(): bool { return $this->role === 'tenant_owner'; }
}