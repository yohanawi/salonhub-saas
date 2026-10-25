<?php

namespace App\Http\Controllers\CustomerManagement;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CustomerConsultation;
use App\Models\CustomerPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Treatment records: before/after photos and consultation & consent forms.
 * Photos are private (local disk) and streamed only to staff who can see the customer.
 */
class CustomerCareController extends Controller
{
    public function storePhotos(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'kind' => ['required', Rule::in(array_keys(CustomerPhoto::KINDS))],
            'caption' => ['nullable', 'string', 'max:255'],
            'taken_on' => ['nullable', 'date', 'before_or_equal:today'],
            'appointment_id' => ['nullable', 'integer'],
        ]);

        $appointmentId = $this->appointmentFor($customer, $data['appointment_id'] ?? null);

        foreach ($data['photos'] as $file) {
            CustomerPhoto::withoutTenantScope()->create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'appointment_id' => $appointmentId,
                'kind' => $data['kind'],
                'path' => $file->store("customer-photos/{$customer->tenant_id}/{$customer->id}", 'local'),
                'caption' => $data['caption'] ?? null,
                'taken_on' => $data['taken_on'] ?? now()->toDateString(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return back()->with('status', count($data['photos']) . ' photo(s) added.');
    }

    public function photo(Customer $customer, CustomerPhoto $photo): StreamedResponse
    {
        $this->authorize('view', $customer);
        abort_unless((int) $photo->customer_id === (int) $customer->id && Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function destroyPhoto(Customer $customer, CustomerPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $customer);
        abort_unless((int) $photo->customer_id === (int) $customer->id, 404);

        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Photo deleted.');
    }

    public function createConsultation(Request $request, Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('pages/apps.customer-management.customers.consultation-form', [
            'customer' => $customer,
            'consultation' => new CustomerConsultation(['answers' => ['allergies' => $customer->allergies]]),
            'appointments' => $this->recentAppointments($customer),
            'selectedAppointment' => $request->integer('appointment_id') ?: null,
        ]);
    }

    public function storeConsultation(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $rules = [
            'appointment_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'consent_given' => ['accepted'],
            'consent_name' => ['required', 'string', 'max:150'],
            'update_allergies' => ['nullable', 'boolean'],
        ];

        foreach (CustomerConsultation::QUESTIONS as $key => [$label, $type]) {
            $rules["answers.{$key}"] = match ($type) {
                'yesno' => ['nullable', Rule::in(['yes', 'no'])],
                'select' => ['nullable', 'string', 'max:50'],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        $data = $request->validate($rules, [
            'consent_given.accepted' => 'The customer must agree to the treatment before the form can be saved.',
            'consent_name.required' => 'Type the customer\'s name as their signature.',
        ]);

        $consultation = CustomerConsultation::withoutTenantScope()->create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'appointment_id' => $this->appointmentFor($customer, $data['appointment_id'] ?? null),
            'answers' => collect(CustomerConsultation::QUESTIONS)->keys()->mapWithKeys(fn ($key) => [$key => $data['answers'][$key] ?? null])->all(),
            'consent_given' => true,
            'consent_name' => $data['consent_name'],
            'signed_at' => now(),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Keep the profile's allergy warning in step with what the customer told us today.
        if (! empty($data['update_allergies']) && filled($data['answers']['allergies'] ?? null)) {
            $customer->update(['allergies' => $data['answers']['allergies']]);
        }

        return redirect()->route('customer-management.consultations.show', [$customer, $consultation])->with('status', 'Consultation form saved.');
    }

    public function showConsultation(Customer $customer, CustomerConsultation $consultation): View
    {
        $this->authorize('view', $customer);
        abort_unless((int) $consultation->customer_id === (int) $customer->id, 404);

        $consultation->load(['appointment', 'createdBy']);

        return view('pages/apps.customer-management.customers.consultation-show', [
            'customer' => $customer,
            'consultation' => $consultation,
        ]);
    }

    private function appointmentFor(Customer $customer, mixed $appointmentId): ?int
    {
        if (! $appointmentId) {
            return null;
        }

        return Appointment::withoutTenantScope()->where('customer_id', $customer->id)->whereKey($appointmentId)->value('id')
            ?? abort(422, 'That appointment does not belong to this customer.');
    }

    private function recentAppointments(Customer $customer)
    {
        return Appointment::withoutTenantScope()
            ->where('customer_id', $customer->id)
            ->latest('starts_at')
            ->limit(15)
            ->get(['id', 'appointment_number', 'starts_at', 'status']);
    }
}
