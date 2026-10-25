<x-default-layout>
    @section('title') Consultation · {{ $customer->full_name }} @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('customer-management.consultations.create', $customer) }} @endsection

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('customer-management.consultations.store', $customer) }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Consultation & Consent Form</h3>
                    <div class="text-muted fs-8">{{ $customer->full_name }} · {{ $customer->phone }}</div>
                </div>
            </div>
            <div class="card-body">
                @if ($customer->hasAllergies())
                    <div class="alert alert-danger d-flex align-items-center mb-6"><i class="bi bi-exclamation-octagon-fill fs-2 me-3"></i><div><strong>On file:</strong> {{ $customer->allergies }}</div></div>
                @endif

                <div class="mb-6">
                    <label class="form-label">For appointment</label>
                    <select name="appointment_id" class="form-select form-select-solid w-md-400px">
                        <option value="">Not linked to an appointment</option>
                        @foreach ($appointments as $appointment)
                            <option value="{{ $appointment->id }}" @selected((int) old('appointment_id', $selectedAppointment) === $appointment->id)>{{ $appointment->appointment_number }} · {{ $appointment->starts_at?->format('M d, Y H:i') }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-6">
                    @foreach (\App\Models\CustomerConsultation::QUESTIONS as $key => $question)
                        @php [$label, $type] = $question; $value = old("answers.{$key}", $consultation->answers[$key] ?? null); @endphp
                        <div class="{{ $type === 'text' ? 'col-12' : 'col-md-6' }}">
                            <label class="form-label fw-semibold">{{ $label }}</label>
                            @if ($type === 'select')
                                <select name="answers[{{ $key }}]" class="form-select form-select-solid">
                                    <option value="">—</option>
                                    @foreach ($question[2] as $option)
                                        <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'yesno')
                                <div class="d-flex gap-6">
                                    @foreach (['yes' => 'Yes', 'no' => 'No'] as $answer => $text)
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="answers[{{ $key }}]" value="{{ $answer }}" @checked($value === $answer)>
                                            <span class="form-check-label">{{ $text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <input type="text" name="answers[{{ $key }}]" value="{{ $value }}" class="form-control form-control-solid">
                            @endif
                        </div>
                    @endforeach
                    <div class="col-12">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="update_allergies" value="1" checked>
                            <span class="form-check-label">Update the allergy warning on the customer's profile with this answer</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Stylist notes</label>
                        <textarea name="notes" rows="3" class="form-control form-control-solid" placeholder="Formula, products used, technique...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="rounded-3 bg-light-primary p-6 mt-8">
                    <h4 class="fw-bold mb-3">Consent</h4>
                    <p class="text-gray-700 fs-7 mb-4">
                        I confirm the information above is correct. I understand the treatment, its possible risks and aftercare,
                        and I consent to it being carried out. I will tell the salon of any changes to my health.
                    </p>
                    <label class="form-check form-check-custom form-check-solid mb-4">
                        <input class="form-check-input" type="checkbox" name="consent_given" value="1" @checked(old('consent_given'))>
                        <span class="form-check-label fw-semibold">The customer agrees</span>
                    </label>
                    <label class="form-label required">Customer's name (signature)</label>
                    <input type="text" name="consent_name" value="{{ old('consent_name', $customer->full_name) }}" class="form-control form-control-solid w-md-400px">
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-3">
                <a href="{{ route('customer-management.customers.show', $customer) }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Form</button>
            </div>
        </form>
    </div>
</x-default-layout>
