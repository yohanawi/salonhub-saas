<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SellGiftCardRequest;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\PaymentMethod;
use App\Services\Billing\CashRegisterService;
use App\Services\Billing\GiftCardService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\PosPinService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GiftCardController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'gift_cards.view', $entitlements);

        $cards = GiftCard::withoutTenantScope()
            ->with(['customer', 'branch'])
            ->where('tenant_id', $tenant->id)
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->trim()->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('code', 'like', '%' . GiftCard::normalizeCode($search) . '%')
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.billing.gift-cards.index', [
            'cards' => $cards,
            'statuses' => GiftCard::STATUSES,
            'outstanding' => (float) GiftCard::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', GiftCard::STATUS_ACTIVE)->sum('balance'),
        ]);
    }

    public function create(
        Request $request,
        PlanEntitlementService $entitlements,
        BranchContext $branchContext,
        PaymentMethodService $paymentMethods,
        SettingService $settings,
        CashRegisterService $cashRegister,
        PosPinService $posPin
    ): View {
        $tenant = $this->tenant($request, 'gift_cards.sell', $entitlements);
        $branch = $branchContext->currentForOperation($request->user());
        $paymentMethods->ensureDefaults($tenant);

        return view('pages/apps.billing.gift-cards.create', [
            'branch' => $branch,
            'customers' => Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Customer::STATUS_ACTIVE)->orderBy('first_name')->get(),
            'paymentMethods' => PaymentMethod::withoutTenantScope()->where('tenant_id', $tenant->id)->manual()->orderBy('sort_order')->orderBy('name')->get(),
            'allowSplitPayment' => (bool) $settings->get('pos.allow_split_payment', $tenant->id, $branch->id),
            'cashShift' => $cashRegister->openShiftFor($branch->id),
            'requireShift' => $cashRegister->requiresOpenShift($tenant->id, $branch->id),
            'posPin' => $posPin->viewData($branch, $request->user()),
        ]);
    }

    public function store(SellGiftCardRequest $request, BranchContext $branchContext, GiftCardService $giftCards, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $entitlements->ensureFeature($tenant, 'pos_billing');
        $branch = $branchContext->currentForOperation($request->user());
        $data = $request->validated();
        $customer = filled($data['customer_id'] ?? null)
            ? Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($data['customer_id'])
            : null;

        $card = $giftCards->sell($branch, $customer, $data, $request->user(), $request->cashierStaffId());

        return redirect()
            ->route('billing.gift-cards.show', $card)
            ->with('status', 'Gift card ' . $card->code . ' issued for LKR ' . number_format((float) $card->initial_amount, 2) . '.');
    }

    public function show(Request $request, GiftCard $giftCard, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'gift_cards.view', $entitlements);
        abort_unless((int) $giftCard->tenant_id === (int) $tenant->id, 404);

        $giftCard->load(['customer', 'branch', 'invoice', 'issuedBy', 'transactions.invoice', 'transactions.createdBy']);

        return view('pages/apps.billing.gift-cards.show', [
            'card' => $giftCard,
            'canVoid' => $request->user()->can('gift_cards.void') && $giftCard->status !== GiftCard::STATUS_VOID,
        ]);
    }

    public function void(Request $request, GiftCard $giftCard, GiftCardService $giftCards, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenant($request, 'gift_cards.void', $entitlements);
        abort_unless((int) $giftCard->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate(['void_reason' => ['required', 'string', 'max:1000']]);
        $giftCards->void($giftCard, $request->user(), $data['void_reason']);

        return back()->with('status', 'Gift card ' . $giftCard->code . ' voided.');
    }

    /**
     * Balance check for the POS payment line, so the cashier sees what is left before charging.
     */
    public function lookup(Request $request, GiftCardService $giftCards, PlanEntitlementService $entitlements): JsonResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant && $request->user()->can('create', \App\Models\Invoice::class), 403);
        $entitlements->ensureFeature($tenant, 'pos_billing');

        $card = $giftCards->find($tenant->id, $request->string('code')->toString());
        $reason = $giftCards->unusableReason($card, 0.01);

        return response()->json([
            'found' => (bool) $card,
            'usable' => $card !== null && $reason === null,
            'balance' => $card ? (float) $card->balance : 0,
            'message' => $reason ?? ('Balance LKR ' . number_format((float) $card->balance, 2)),
        ]);
    }

    private function tenant(Request $request, string $permission, PlanEntitlementService $entitlements): \App\Models\Tenant
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can($permission), 403);

        $entitlements->ensureFeature($user->tenant, 'pos_billing');

        return $user->tenant;
    }
}
