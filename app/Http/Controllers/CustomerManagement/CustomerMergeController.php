<?php

namespace App\Http\Controllers\CustomerManagement;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Customers\CustomerMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerMergeController extends Controller
{
    /**
     * Merges the chosen duplicate into $customer, which is the record that stays.
     */
    public function store(Request $request, Customer $customer, CustomerMergeService $merge): RedirectResponse
    {
        $this->authorize('merge', $customer);

        $data = $request->validate([
            'duplicate_id' => [
                'required', 'integer', Rule::notIn([$customer->id]),
                Rule::exists('customers', 'id')->where('tenant_id', $customer->tenant_id)->whereNull('deleted_at'),
            ],
        ]);

        $duplicate = Customer::withoutTenantScope()->findOrFail($data['duplicate_id']);
        $this->authorize('merge', $duplicate);

        $merge->merge($customer, $duplicate, $request->user());

        return redirect()
            ->route('customer-management.customers.show', $customer)
            ->with('status', "{$duplicate->full_name} ({$duplicate->customer_code}) was merged into this customer.");
    }
}
