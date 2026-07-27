<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Gera o UUID em PHP, ANTES do insert — não depende mais do default
 * gen_random_uuid() do Postgres. Correção necessária: o Eloquent só lê de
 * volta o valor gerado pelo banco (via RETURNING) quando incrementing = true.
 * Como nossa PK é UUID (incrementing = false), sem gerar em PHP o objeto
 * ficava com id = null em memória logo após o create(), mesmo com a linha
 * inserida corretamente no banco — daí o null se propagando para as FKs
 * dependentes (tenant_id em User, Store, etc). O default gen_random_uuid()
 * continua nas migrations como rede de segurança para inserts fora do Eloquent.
 *
 * As propriedades são setadas via initializeHasUuidPrimaryKey(), chamado
 * automaticamente pelo Eloquent no construtor — nunca declaradas direto na
 * trait, porque Illuminate\Database\Eloquent\Model já declara
 * `public $incrementing = true;` e uma redeclaração com valor diferente
 * causa Fatal Error de incompatibilidade em PHP.
 */
trait HasUuidPrimaryKey
{
    public function initializeHasUuidPrimaryKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}