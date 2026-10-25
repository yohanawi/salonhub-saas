<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class ProfileAddressTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    private array $address = [
        'type' => Address::TYPE_BILLING,
        'address_line_1' => '12 Galle Road',
        'city' => 'Colombo 03',
        'country' => 'Sri Lanka',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_first_address_becomes_primary_and_shows_on_the_billing_page(): void
    {
        [, $owner] = $this->makeSalon();

        $this->actingAs($owner)
            ->post(route('profile.addresses.store'), $this->address + ['label' => 'Salon office'])
            ->assertSessionHas('status', 'Address added.');

        $address = $owner->addresses()->firstOrFail();
        $this->assertTrue($address->is_primary);
        $this->assertNull($address->postal_code);

        $this->actingAs($owner)
            ->get(route('profile.billing'))
            ->assertOk()
            ->assertSee('Salon office')
            ->assertSee('12 Galle Road')
            ->assertDontSee("Address management isn't available yet", false);
    }

    public function test_primary_address_can_be_switched_and_moves_on_when_deleted(): void
    {
        [, $owner] = $this->makeSalon();
        $this->actingAs($owner)->post(route('profile.addresses.store'), $this->address);
        $this->actingAs($owner)->post(route('profile.addresses.store'), ['address_line_1' => '5 Kandy Road', 'city' => 'Kandy'] + $this->address);

        [$first, $second] = Address::query()->where('user_id', $owner->id)->orderBy('id')->get();
        $this->assertFalse($second->is_primary);

        $this->actingAs($owner)->post(route('profile.addresses.primary', $second));
        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame($second->id, $owner->fresh()->default_address->id);

        $this->actingAs($owner)->delete(route('profile.addresses.destroy', $second));
        $this->assertNull(Address::find($second->id));
        $this->assertTrue($first->fresh()->is_primary);
    }

    public function test_address_can_be_edited(): void
    {
        [, $owner] = $this->makeSalon();
        $this->actingAs($owner)->post(route('profile.addresses.store'), $this->address);
        $address = $owner->addresses()->firstOrFail();

        $this->actingAs($owner)
            ->put(route('profile.addresses.update', $address), ['city' => 'Negombo', 'postal_code' => '11500'] + $this->address)
            ->assertSessionHas('status', 'Address updated.');

        $address->refresh();
        $this->assertSame('Negombo', $address->city);
        $this->assertSame('11500', $address->postal_code);
        $this->assertTrue($address->is_primary);
    }

    public function test_address_needs_the_required_fields_and_respects_the_limit(): void
    {
        [, $owner] = $this->makeSalon();

        $this->actingAs($owner)
            ->post(route('profile.addresses.store'), ['type' => Address::TYPE_BILLING])
            ->assertSessionHasErrors(['address_line_1', 'city', 'country']);

        foreach (range(1, Address::MAX_PER_USER) as $index) {
            $this->actingAs($owner)->post(route('profile.addresses.store'), $this->address);
        }

        $this->actingAs($owner)
            ->post(route('profile.addresses.store'), $this->address)
            ->assertSessionHasErrors('address_line_1');
        $this->assertSame(Address::MAX_PER_USER, $owner->addresses()->count());
    }

    public function test_users_cannot_touch_someone_elses_address(): void
    {
        [, $owner] = $this->makeSalon();
        $this->actingAs($owner)->post(route('profile.addresses.store'), $this->address);
        $address = $owner->addresses()->firstOrFail();
        $other = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($other)->delete(route('profile.addresses.destroy', $address))->assertNotFound();
        $this->actingAs($other)->put(route('profile.addresses.update', $address), $this->address)->assertNotFound();
        $this->assertNotNull(Address::find($address->id));
    }
}
