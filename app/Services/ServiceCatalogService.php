<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
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
        ];
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
