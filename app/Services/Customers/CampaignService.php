<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Validation\ValidationException;

/**
 * Sends one SMS/WhatsApp message to everyone in a segment who agreed to marketing messages.
 */
class CampaignService
{
    /** A single campaign is capped so a typo cannot text the whole database at once. */
    public const MAX_RECIPIENTS = 2000;

    public function __construct(
        private readonly CustomerSegmentService $segments,
        private readonly MessagingService $messaging,
    ) {
    }

    public function recipients(Tenant $tenant, string $segment)
    {
        return $this->segments->query($tenant, $segment)
            ->where('customers.marketing_consent', true)
            ->whereNotNull('customers.phone')
            ->where('customers.phone', '!=', '');
    }

    public function send(Tenant $tenant, string $segment, string $name, string $message, User $user): CustomerCampaign
    {
        if (! $this->messaging->enabled($tenant->id, null)) {
            throw ValidationException::withMessages(['message' => 'Set up SMS or WhatsApp in Settings → Integrations before sending campaigns.']);
        }

        $recipients = $this->recipients($tenant, $segment)->orderBy('customers.id')->limit(self::MAX_RECIPIENTS + 1)->get();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['segment' => 'Nobody in this segment has agreed to marketing messages.']);
        }

        if ($recipients->count() > self::MAX_RECIPIENTS) {
            throw ValidationException::withMessages(['segment' => 'This segment has more than ' . number_format(self::MAX_RECIPIENTS) . ' recipients. Narrow it down first.']);
        }

        $campaign = CustomerCampaign::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'segment' => $segment,
            'message' => $message,
            'recipients_count' => $recipients->count(),
            'created_by' => $user->id,
        ]);

        $queued = 0;

        foreach ($recipients as $customer) {
            /** @var Customer $customer */
            $body = $this->messaging->render($message, [
                'customer_name' => $customer->first_name ?: $customer->full_name,
                'first_name' => $customer->first_name,
                'salon_name' => $tenant->name,
            ]);

            if ($this->messaging->send($tenant->id, $customer->branch_id, 'campaign', $customer->phone, $body, $customer, $user)) {
                $queued++;
            }
        }

        $campaign->update(['queued_count' => $queued, 'sent_at' => now()]);

        return $campaign;
    }
}
