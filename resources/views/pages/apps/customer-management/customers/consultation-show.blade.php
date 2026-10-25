<x-default-layout>
    @section('title') Consultation · {{ $customer->full_name }} @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('customer-management.consultations.show', $customer, $consultation) }} @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Consultation Form</h3>
                    <div class="text-muted fs-8">
                        {{ $customer->full_name }} · {{ $consultation->created_at?->format('M d, Y H:i') }} · recorded by {{ $consultation->createdBy?->name }}
                        @if ($consultation->appointment) · {{ $consultation->appointment->appointment_number }} @endif
                    </div>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                </div>
            </div>
            <div class="card-body">
                @if ($consultation->warnings())
                    <div class="alert alert-warning mb-6">
                        @foreach ($consultation->warnings() as $warning)
                            <div><i class="bi bi-exclamation-triangle me-1"></i>{{ $warning }}</div>
                        @endforeach
                    </div>
                @endif

                <table class="table table-row-dashed gy-3 fs-6">
                    @foreach (\App\Models\CustomerConsultation::QUESTIONS as $key => $question)
                        <tr>
                            <td class="text-muted w-50">{{ $question[0] }}</td>
                            <td class="fw-semibold">{{ filled($consultation->answers[$key] ?? null) ? ucfirst($consultation->answers[$key]) : '—' }}</td>
                        </tr>
                    @endforeach
                    @if ($consultation->notes)
                        <tr><td class="text-muted">Stylist notes</td><td style="white-space: pre-line;">{{ $consultation->notes }}</td></tr>
                    @endif
                </table>

                <div class="rounded-3 bg-light p-5 mt-6">
                    <i class="bi bi-pen me-2"></i>Consent given and signed by <strong>{{ $consultation->consent_name }}</strong> on {{ $consultation->signed_at?->format('M d, Y \a\t H:i') }}.
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('customer-management.customers.show', $customer) }}" class="btn btn-light">Back to customer</a>
            </div>
        </div>
    </div>
</x-default-layout>
