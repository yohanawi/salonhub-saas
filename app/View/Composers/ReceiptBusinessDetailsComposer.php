<?php

namespace App\View\Composers;

use App\Services\Settings\SettingService;
use Illuminate\View\View;

/**
 * Gives every receipt the salon's legal details from Settings > General (legal name, registration and
 * VAT numbers, website, address), so customers get a proper tax receipt.
 */
class ReceiptBusinessDetailsComposer
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    public function compose(View $view): void
    {
        $invoice = $view->getData()['invoice'] ?? null;

        if (! $invoice?->tenant_id) {
            $view->with('business', []);

            return;
        }

        $setting = fn (string $key) => trim((string) $this->settings->get($key, $invoice->tenant_id, $invoice->branch_id));

        $view->with('business', array_filter([
            // Shown only when it differs from the trading name already at the top.
            'legal_name' => $setting('business.legal_name') !== $invoice->tenant?->name ? $setting('business.legal_name') : '',
            'registration_number' => $setting('business.registration_number'),
            'tax_number' => $setting('business.tax_number'),
            'website' => $setting('business.website'),
            'address' => $setting('business.address'),
        ]));
    }
}
