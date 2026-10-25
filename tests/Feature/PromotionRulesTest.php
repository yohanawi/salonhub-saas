<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Subscription;
use App\Services\Promotions\PromotionService;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class PromotionRulesTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Queue::fake();
    }

    public function test_owner_creates_buy_x_get_y_and_happy_hour_promotions(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['promotions_discounts']);
        $service = $this->makeService($tenant, $branch);

        $this->actingAs($owner)->post(route('promotions.promotions.store'), [
            'name' => 'Buy 2 Get 1 Free',
            'promotion_type' => Promotion::TYPE_BOGO,
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'get_discount_percentage' => 100,
            'discount_type' => Promotion::DISCOUNT_PERCENTAGE,
            'application_type' => Promotion::APPLICATION_AUTOMATIC,
            'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'customer_scope' => Promotion::CUSTOMER_ALL,
            'branch_scope' => Promotion::BRANCH_ALL,
            'target_scope' => Promotion::TARGET_SERVICES,
            'service_ids' => [$service->id],
            'status' => Promotion::STATUS_ACTIVE,
            'active_days' => [1, 2, 3],
            'active_from_time' => '14:00',
            'active_to_time' => '17:00',
        ])->assertRedirect();

        $promotion = Promotion::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertTrue($promotion->isBogo());
        $this->assertSame([2, 1], [$promotion->buy_quantity, $promotion->get_quantity]);
        $this->assertSame([1, 2, 3], $promotion->active_days);
        $this->assertSame('Buy 2 get 1 free', $promotion->discount_label);
        $this->assertSame('Mon, Tue, Wed · 14:00–17:00', $promotion->schedule_label);

        $this->actingAs($owner)->get(route('promotions.promotions.index'))->assertOk()->assertSee('Buy 2 get 1 free');
        $this->actingAs($owner)->get(route('promotions.promotions.edit', $promotion))->assertOk()->assertSee('Happy Hour / Days');

        // BOGO needs quantities; birthday needs a window.
        $this->actingAs($owner)->post(route('promotions.promotions.store'), [
            'name' => 'Broken',
            'promotion_type' => Promotion::TYPE_BOGO,
            'discount_type' => Promotion::DISCOUNT_PERCENTAGE,
            'application_type' => Promotion::APPLICATION_AUTOMATIC,
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'customer_scope' => Promotion::CUSTOMER_ALL,
            'branch_scope' => Promotion::BRANCH_ALL,
            'target_scope' => Promotion::TARGET_INVOICE,
            'status' => Promotion::STATUS_ACTIVE,
        ])->assertSessionHasErrors(['buy_quantity', 'get_quantity']);
    }

    public function test_buy_two_get_one_discounts_the_cheapest_line(): void
    {
        [$tenant, , $branch] = $this->makeSalon(['promotions_discounts']);
        $cut = $this->makeService($tenant, $branch);
        $wash = $this->makeService($tenant, $branch);
        $blow = $this->makeService($tenant, $branch);

        $promotion = $this->promotion($tenant, [
            'promotion_type' => Promotion::TYPE_BOGO,
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'get_discount_percentage' => 100,
            'target_scope' => Promotion::TARGET_SERVICES,
        ]);
        $promotion->services()->sync(collect([$cut, $wash, $blow])->mapWithKeys(fn ($service) => [$service->id => ['tenant_id' => $tenant->id]])->all());

        $items = collect([
            $this->line($cut->id, 3000),
            $this->line($wash->id, 1000),
            $this->line($blow->id, 2000),
        ]);

        $result = app(PromotionService::class)->applyToItemSnapshots($tenant, null, $branch, $items, []);

        $this->assertSame($promotion->id, $result['promotion']->id);
        $this->assertEquals(1000.0, $result['discount']);
        $this->assertEquals(1000.0, $result['items'][1]['promotion_discount_amount']);
        $this->assertEquals(0.0, (float) $result['items'][1]['net_amount']);
        $this->assertArrayNotHasKey('promotion_discount_amount', $result['items'][0]);

        // Two units only: not enough for the offer.
        $two = collect([$this->line($cut->id, 3000), $this->line($wash->id, 1000)]);
        $this->assertNull(app(PromotionService::class)->applyToItemSnapshots($tenant, null, $branch, $two, [])['promotion']);

        // Half price on the "get" item and quantity on one line.
        $promotion->update(['get_discount_percentage' => 50]);
        $multi = collect([$this->line($cut->id, 9000, 3)]);
        $this->assertEquals(1500.0, app(PromotionService::class)->applyToItemSnapshots($tenant, null, $branch, $multi, [])['discount']);
    }

    public function test_happy_hour_only_applies_inside_its_window(): void
    {
        [$tenant, , $branch] = $this->makeSalon(['promotions_discounts']);
        $service = $this->makeService($tenant, $branch);
        $now = CarbonImmutable::now($branch->timezone);

        $promotion = $this->promotion($tenant, [
            'discount_value' => 20,
            'active_days' => [$now->dayOfWeek],
            'active_from_time' => $now->subHour()->format('H:i'),
            'active_to_time' => $now->addHour()->format('H:i'),
        ]);

        $items = collect([$this->line($service->id, 5000)]);
        $service = app(PromotionService::class);

        $this->assertEquals(1000.0, $service->applyToItemSnapshots($tenant, null, $branch, $items, [])['discount']);

        $promotion->update(['active_from_time' => $now->addHours(2)->format('H:i'), 'active_to_time' => $now->addHours(3)->format('H:i')]);
        $result = $service->checkEligibility($promotion->fresh(), null, $branch, $items);
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('only available', $result->message);

        $promotion->update(['active_days' => [($now->dayOfWeek + 1) % 7], 'active_from_time' => null, 'active_to_time' => null]);
        $this->assertTrue($service->checkEligibility($promotion->fresh(), null, $branch, $items)->failed());
    }

    public function test_birthday_discount_applies_automatically_once_a_year(): void
    {
        [$tenant, , $branch] = $this->makeSalon(['promotions_discounts']);
        $service = $this->makeService($tenant, $branch);
        $today = CarbonImmutable::now($branch->timezone);

        $promotion = $this->promotion($tenant, [
            'promotion_type' => Promotion::TYPE_BIRTHDAY,
            'birthday_window' => 'week',
            'discount_value' => 25,
        ]);

        $customer = $this->makeCustomer($tenant, $branch);
        $customer->update(['date_of_birth' => $today->subYears(30)->addDays(2)->toDateString()]);
        $items = collect([$this->line($service->id, 4000)]);
        $promotions = app(PromotionService::class);

        $applied = $promotions->applyToItemSnapshots($tenant, $customer->fresh(), $branch, $items, []);
        $this->assertSame($promotion->id, $applied['promotion']?->id);
        $this->assertEquals(1000.0, $applied['discount']);

        // No customer / far from birthday: not applied.
        $this->assertNull($promotions->applyToItemSnapshots($tenant, null, $branch, $items, [])['promotion']);
        $customer->update(['date_of_birth' => $today->subYears(30)->addDays(20)->toDateString()]);
        $this->assertNull($promotions->applyToItemSnapshots($tenant, $customer->fresh(), $branch, $items, [])['promotion']);

        // Already used this year.
        $customer->update(['date_of_birth' => $today->subYears(30)->toDateString()]);
        PromotionUsage::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'promotion_id' => $promotion->id,
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'discount_amount' => 1000,
            'status' => PromotionUsage::STATUS_USED,
            'used_at' => now(),
        ]);
        $result = $promotions->checkEligibility($promotion, $customer->fresh(), $branch, $items);
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('already been used this year', $result->message);
    }

    public function test_birthday_greetings_are_sent_once_with_the_offer(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['promotions_discounts']);
        $this->enableSms($tenant, $owner);
        app(SettingService::class)->setMany($tenant, null, ['promotion.birthday_greeting_enabled' => true], $owner);
        $this->promotion($tenant, ['promotion_type' => Promotion::TYPE_BIRTHDAY, 'birthday_window' => 'week', 'discount_value' => 25, 'name' => 'Birthday Treat']);

        $today = CarbonImmutable::now($tenant->timezone);
        $birthday = $this->makeCustomer($tenant, $branch);
        $birthday->update(['date_of_birth' => $today->subYears(25)->toDateString()]);
        $other = $this->makeCustomer($tenant, $branch);
        $other->update(['date_of_birth' => $today->subYears(25)->addDays(5)->toDateString()]);

        $this->artisan('customers:birthday-greetings', ['--tenant_id' => $tenant->id])->assertSuccessful();
        $this->artisan('customers:birthday-greetings', ['--tenant_id' => $tenant->id])->assertSuccessful();

        $logs = MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'birthday_greeting')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($birthday->id, (int) $logs->first()->related_id);
        $this->assertStringContainsString('25.00% off (Birthday Treat)', $logs->first()->body);
    }

    public function test_leap_day_birthdays_are_greeted_on_28_february_in_other_years(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['promotions_discounts']);
        $this->enableSms($tenant, $owner);
        app(SettingService::class)->setMany($tenant, null, ['promotion.birthday_greeting_enabled' => true], $owner);

        $leapDay = $this->makeCustomer($tenant, $branch);
        $leapDay->update(['date_of_birth' => '2000-02-29']);
        $marchFirst = $this->makeCustomer($tenant, $branch);
        $marchFirst->update(['date_of_birth' => '2000-03-01']);

        // Scheduled jobs skip salons whose trial has ended, so keep this one on a paid plan.
        Subscription::withoutTenantScope()->where('tenant_id', $tenant->id)->update(['status' => Subscription::STATUS_ACTIVE, 'trial_ends_at' => null]);
        $this->travelTo(CarbonImmutable::parse('2027-02-28 10:00', $tenant->timezone));
        $this->artisan('customers:birthday-greetings', ['--tenant_id' => $tenant->id])->assertSuccessful();

        $logs = MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'birthday_greeting')->get();
        $this->assertSame([$leapDay->id], $logs->pluck('related_id')->map(fn ($id) => (int) $id)->all());
    }

    private function promotion($tenant, array $attributes): Promotion
    {
        return Promotion::withoutTenantScope()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Offer ' . fake()->unique()->numberBetween(100, 999),
            'promotion_type' => Promotion::TYPE_STANDARD,
            'discount_type' => Promotion::DISCOUNT_PERCENTAGE,
            'discount_value' => 10,
            'application_type' => Promotion::APPLICATION_AUTOMATIC,
            'starts_at' => now()->subDay(),
            'customer_scope' => Promotion::CUSTOMER_ALL,
            'branch_scope' => Promotion::BRANCH_ALL,
            'target_scope' => Promotion::TARGET_INVOICE,
            'status' => Promotion::STATUS_ACTIVE,
        ], $attributes));
    }

    private function line(int $serviceId, float $gross, int $quantity = 1): array
    {
        return [
            'service_id' => $serviceId,
            'product_id' => null,
            'quantity' => $quantity,
            'unit_price' => $gross / $quantity,
            'gross_amount' => $gross,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'net_amount' => $gross,
        ];
    }
}
