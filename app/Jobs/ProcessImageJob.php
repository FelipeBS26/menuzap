<?php

namespace App\Jobs;

use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Pipeline de upload: valida → redimensiona → converte para WebP → gera
 * thumbnail (só para Product) → salva no disco público → apaga o temporário.
 *
 * Movido do Sprint 5 para cá na auditoria da Fase 10 — o formulário de loja
 * e o de produto (Parte 3) dependem disso desde já, não faz sentido ficar
 * sem otimização de imagem por 3 sprints.
 *
 * CONVENÇÃO OBRIGATÓRIA (Fase 4): jobs em fila não herdam o contexto de
 * tenant do middleware. tenant_id chega explícito no construtor e o
 * TenantContext é reativado manualmente na primeira linha do handle().
 */
class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $tenantId,
        protected string $modelClass,
        protected string $modelId,
        protected string $field,
        protected string $tempPath,
        protected int $maxWidth = 800,
        protected int $maxHeight = 800,
    ) {}

    public function handle(): void
    {
        TenantContext::set($this->tenantId);

        $model = $this->modelClass::findOrFail($this->modelId);

        // API do Intervention Image v4 (mudou da v3 há poucas semanas):
        // instanciação via usingDriver() estático, leitura via decode() no
        // lugar de read(), codificação via encodeUsingFormat() no lugar de
        // toWebp(). Sem Facade — não depende de config/binding do Laravel.
        $manager = ImageManager::usingDriver(Driver::class);
        $image = $manager->decode(Storage::path($this->tempPath));
        $image->cover($this->maxWidth, $this->maxHeight);

        $filename = uniqid('img_').'.webp';
        $path = "tenants/{$this->tenantId}/{$filename}";
        Storage::disk('public')->put($path, (string) $image->encodeUsingFormat(Format::WEBP, quality: 82));

        $updates = [$this->field => Storage::disk('public')->url($path)];

        // Thumbnail só existe no schema de Product (Fase 5) — Store não tem.
        if ($model instanceof Product) {
            $thumbnail = clone $image;
            $thumbnail->cover(300, 300);
            $thumbPath = "tenants/{$this->tenantId}/thumb_{$filename}";
            Storage::disk('public')->put($thumbPath, (string) $thumbnail->encodeUsingFormat(Format::WEBP, quality: 75));
            $updates['thumbnail_url'] = Storage::disk('public')->url($thumbPath);
        }

        $model->update($updates);

        Storage::delete($this->tempPath);
    }
}