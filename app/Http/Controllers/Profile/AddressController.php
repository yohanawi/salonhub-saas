<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The signed-in user's own addresses, managed from My Profile → Billing.
 */
class AddressController extends Controller
{
    private const SECTION = 'kt_billing_addresses';

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->addresses()->count() >= Address::MAX_PER_USER) {
            throw ValidationException::withMessages(['address_line_1' => 'You can keep up to ' . Address::MAX_PER_USER . ' addresses. Remove one first.']);
        }

        $data = $this->validated($request);

        DB::transaction(function () use ($user, $data) {
            // The first address, or one marked primary, becomes the primary address.
            $primary = ($data['is_primary'] ?? false) || ! $user->addresses()->exists();

            if ($primary) {
                $user->addresses()->update(['is_primary' => false]);
            }

            $user->addresses()->create([...$data, 'is_primary' => $primary]);
        });

        return $this->back('Address added.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->ensureOwned($request, $address);

        $data = $this->validated($request);

        DB::transaction(function () use ($request, $address, $data) {
            if ($data['is_primary'] ?? false) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_primary' => false]);
            }

            // Unticking "primary" on the only primary address keeps it primary; another one has to be chosen.
            $address->update([...$data, 'is_primary' => $address->is_primary || ($data['is_primary'] ?? false)]);
        });

        return $this->back('Address updated.');
    }

    public function makePrimary(Request $request, Address $address): RedirectResponse
    {
        $this->ensureOwned($request, $address);

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_primary' => false]);
            $address->update(['is_primary' => true]);
        });

        return $this->back('Primary address changed.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->ensureOwned($request, $address);

        DB::transaction(function () use ($request, $address) {
            $wasPrimary = $address->is_primary;
            $address->delete();

            // Another address takes over as primary so the profile always shows one.
            if ($wasPrimary) {
                $request->user()->addresses()->orderBy('id')->first()?->update(['is_primary' => true]);
            }
        });

        return $this->back('Address removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'integer', Rule::in(array_keys(Address::TYPES))],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $data['is_primary'] = (bool) ($data['is_primary'] ?? false);

        return $data;
    }

    private function ensureOwned(Request $request, Address $address): void
    {
        // Someone else's address is reported as missing, not forbidden.
        abort_unless((int) $address->user_id === (int) $request->user()->id, 404);
    }

    private function back(string $status): RedirectResponse
    {
        return redirect(route('profile.billing') . '#' . self::SECTION)->with('status', $status);
    }
}
