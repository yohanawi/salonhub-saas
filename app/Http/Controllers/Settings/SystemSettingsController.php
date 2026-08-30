<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\Settings\SettingDefinitionRegistry;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function index(Request $request, SettingDefinitionRegistry $registry): View
    {
        $this->authorize('viewAny', Setting::class);

        [$isSuperAdmin, $tenant, $branch, $branches, $tenants] = $this->context($request);

        return view('pages/apps.settings.index', [
            'sections' => $registry->sections(),
            'registry' => $registry,
            'tenant' => $tenant,
            'branch' => $branch,
            'branches' => $branches,
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'storedCounts' => $this->storedCounts($tenant, $branch),
        ]);
    }

    public function edit(Request $request, string $section, SettingDefinitionRegistry $registry, SettingService $settings): View
    {
        $this->authorize('viewAny', Setting::class);
        abort_unless($registry->section($section), 404);

        [$isSuperAdmin, $tenant, $branch, $branches, $tenants] = $this->context($request);
        $definitions = $registry->definitionsForSection($section);

        return view('pages/apps.settings.edit', [
            'sectionKey' => $section,
            'section' => $registry->section($section),
            'sections' => $registry->sections(),
            'definitions' => $definitions,
            'values' => $settings->resolved($tenant, $branch)->only(array_keys($definitions))->all(),
            'encryptedKeys' => $settings->storedEncryptedKeys($tenant, $branch),
            'registry' => $registry,
            'tenant' => $tenant,
            'branch' => $branch,
            'branches' => $branches,
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $section, SettingDefinitionRegistry $registry, SettingService $settings): RedirectResponse
    {
        abort_unless($registry->section($section), 404);

        [$isSuperAdmin, $tenant, $branch] = $this->context($request);
        abort_if(! $isSuperAdmin && ! $tenant, 403);
        abort_if($branch && (! $tenant || (int) $branch->tenant_id !== (int) $tenant->id), 422);

        if ($section === 'general' && $tenant) {
            $this->updateTenantProfile($tenant, $request);
        }

        $payload = collect($request->input('settings', []))
            ->only(array_keys($registry->definitionsForSection($section)))
            ->all();

        $settings->setMany($tenant, $branch, $payload, $request->user(), $request);

        return redirect()
            ->route('settings.edit', ['section' => $section, 'tenant_id' => $tenant?->id, 'branch_id' => $branch?->id])
            ->with('status', 'Settings saved successfully.');
    }

    private function context(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        abort_if($request->filled('branch_id') && ! $tenant, 422);

        $branches = $tenant
            ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get()
            : collect();

        $branch = $request->filled('branch_id')
            ? Branch::withoutTenantScope()
                ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
                ->findOrFail($request->integer('branch_id'))
            : null;

        $tenants = $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect();

        return [$isSuperAdmin, $tenant, $branch, $branches, $tenants];
    }

    private function storedCounts(?Tenant $tenant, ?Branch $branch): array
    {
        return Setting::withoutTenantScope()
            ->where('tenant_id', $tenant?->id)
            ->where('branch_id', $branch?->id)
            ->get(['group'])
            ->countBy('group')
            ->all();
    }

    private function updateTenantProfile(Tenant $tenant, UpdateSettingsRequest $request): void
    {
        $input = collect($request->input('tenant', []))
            ->only(['name', 'email', 'phone', 'country', 'currency', 'timezone', 'business_type'])
            ->filter(fn ($value) => $value !== null)
            ->all();

        if ($request->hasFile('tenant.logo')) {
            $input['logo_path'] = $request->file('tenant.logo')->store('tenant-logos', 'public');
        }

        if ($input === []) {
            return;
        }

        $oldValues = Arr::only($tenant->getOriginal(), array_keys($input));
        $tenant->fill($input);

        if (! $tenant->isDirty()) {
            return;
        }

        $tenant->save();

        AuditLog::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'settings.tenant_profile_updated',
            'event' => 'settings.tenant_profile_updated',
            'module' => 'settings',
            'description' => 'Salon profile settings updated.',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
            'old_values' => $oldValues,
            'new_values' => Arr::only($tenant->fresh()->toArray(), array_keys($input)),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'metadata' => [
                'section' => 'general',
                'scope' => "tenant:{$tenant->id}",
            ],
            'created_at' => now(),
        ]);
    }
}
