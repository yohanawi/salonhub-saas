<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceCatalogService
{
    public function create(Tenant $tenant, array $data): Service
    {
        return DB::transaction(function () use ($tenant, $data) {
            $branches = $data['branches'] ?? [];
            unset($data['branches']);

            $service = $tenant->services()->create($this->servicePayload($data, $tenant));

            $this->syncBranches($service, $tenant, $branches);
            $this->syncExtras($service, $tenant, $data);

            return $service->fresh(['category', 'branches']);
        });
    }

    public function update(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data) {
            $branches = $data['branches'] ?? [];
            unset($data['branches']);

            $service->update($this->servicePayload($data, $service->tenant, $service));
            $this->syncBranches($service, $service->tenant, $branches);
            $this->syncExtras($service, $service->tenant, $data);

            return $service->fresh(['category', 'branches']);
        });
    }

    public function syncBranches(Service $service, Tenant $tenant, array $branches): void
    {
        $allowedBranchIds = Branch::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', array_keys($branches))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $pivot = [];

        foreach ($branches as $branchId => $branchData) {
            $branchId = (int) $branchId;

            if (! in_array($branchId, $allowedBranchIds, true) || empty($branchData['enabled'])) {
                continue;
            }

            $pivot[$branchId] = [
                'tenant_id' => $tenant->id,
                'price' => $branchData['price'] ?? null,
                'duration_minutes' => $branchData['duration_minutes'] ?? null,
                'is_active' => (bool) ($branchData['is_active'] ?? true),
            ];
        }

        $service->branches()->sync($pivot);
    }

    private function servicePayload(array $data, Tenant $tenant, ?Service $service = null): array
    {
        $slug = $this->uniqueSlug(Str::slug($data['name']), $tenant, $service);

        return [
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => $data['default_price'],
            'duration_minutes' => $data['default_duration_minutes'],
            'default_price' => $data['default_price'],
            'default_duration_minutes' => $data['default_duration_minutes'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            // Fields the request did not send keep their current value (or the column default for new services).
            'online_description' => array_key_exists('online_description', $data) ? $data['online_description'] : $service?->online_description,
            'show_online' => array_key_exists('show_online', $data) ? (bool) $data['show_online'] : ($service?->show_online ?? true),
            'is_addon' => array_key_exists('is_addon', $data) ? (bool) $data['is_addon'] : ($service?->is_addon ?? false),
        ];
    }

    /**
     * Image, suggested add-ons and consumable products. Add-ons and consumables are only touched when the
     * form sent them, so callers that update the core fields alone keep the existing configuration.
     */
    private function syncExtras(Service $service, Tenant $tenant, array $data): void
    {
        $this->syncImage($service, $data);

        if (array_key_exists('addon_ids', $data) || ! empty($data['addons_present'])) {
            $addons = [];

            foreach (array_values(array_unique(array_map('intval', $data['addon_ids'] ?? []))) as $index => $addonId) {
                if ($addonId === (int) $service->id) {
                    continue;
                }

                $addons[$addonId] = ['tenant_id' => $tenant->id, 'sort_order' => $index];
            }

            $service->addons()->sync($addons);
        }

        if (array_key_exists('consumables', $data) || ! empty($data['consumables_present'])) {
            $consumables = [];

            foreach ($data['consumables'] ?? [] as $line) {
                if (empty($line['product_id'])) {
                    continue;
                }

                $consumables[(int) $line['product_id']] = [
                    'tenant_id' => $tenant->id,
                    'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
                ];
            }

            $service->consumables()->sync($consumables);
        }
    }

    private function syncImage(Service $service, array $data): void
    {
        $image = $data['image'] ?? null;

        if (! $image instanceof UploadedFile && empty($data['remove_image'])) {
            return;
        }

        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }

        $service->update([
            'image_path' => $image instanceof UploadedFile
                ? $image->store("services/{$service->tenant_id}", 'public')
                : null,
        ]);
    }

    private function uniqueSlug(string $slug, Tenant $tenant, ?Service $service = null): string
    {
        $slug = $slug !== '' ? $slug : Str::random(8);
        $base = $slug;
        $suffix = 2;

        while (Service::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->when($service, fn ($query) => $query->whereKeyNot($service->id))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
