<x-default-layout>
    @section('title')
        Message Log
    @endsection

    @php
        $statusColors = ['queued' => 'warning', 'sent' => 'success', 'failed' => 'danger'];
    @endphp

    <div id="kt_app_content_container">
        <div class="row g-8 mb-8">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-8">
                        <h1 class="fw-bolder text-gray-900 mb-2">SMS / WhatsApp Messages</h1>
                        <div class="text-muted mb-5">Receipts, booking confirmations and reminders sent to customers, and what the provider said.</div>
                        @if ($channels === [])
                            <div class="alert alert-warning border-0 fs-7 mb-0">
                                Messaging is off. Turn on SMS or WhatsApp in
                                <a href="{{ route('settings.edit', 'communications') }}">Communications</a>
                                and add the provider keys in
                                <a href="{{ route('settings.edit', 'integrations') }}">Integrations</a>.
                            </div>
                        @else
                            <div class="fs-7">
                                Sending by:
                                @foreach ($channels as $channel)
                                    <span class="badge badge-light-success">{{ $channel === 'sms' ? 'SMS' : 'WhatsApp' }}</span>@if (! $loop->last) <span class="text-muted">then</span> @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-8">
                        <h3 class="fw-bold text-gray-900 mb-4">Send a Test</h3>
                        <form method="POST" action="{{ route('settings.messages.test') }}">
                            @csrf
                            <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="07X XXX XXXX"
                                class="form-control form-control-solid mb-3 @error('phone') is-invalid @enderror" aria-label="Mobile number">
                            @error('phone')
                                <div class="text-danger fs-8 mb-3">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-light-primary w-100">Send Test Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <form method="GET" class="d-flex flex-wrap gap-3">
                    <select name="event" class="form-select form-select-solid w-200px">
                        <option value="">All messages</option>
                        @foreach (['receipt' => 'Receipts', 'booking_confirmation' => 'Booking confirmations', 'reminder' => 'Reminders', 'test' => 'Tests'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-solid w-150px">
                        <option value="">Any status</option>
                        @foreach (array_keys($statusColors) as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-light-primary">Filter</button>
                </form>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-4">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>When</th>
                                <th>Type</th>
                                <th>To</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr>
                                    <td class="text-nowrap">
                                        <div>{{ $message->created_at?->format('M d, h:i A') }}</div>
                                        <div class="text-muted fs-8">{{ $message->channel === 'sms' ? 'SMS' : 'WhatsApp' }}{{ $message->provider ? ' · ' . $message->provider : '' }}</div>
                                    </td>
                                    <td>{{ $message->event_label }}</td>
                                    <td class="text-nowrap">+{{ $message->recipient }}</td>
                                    <td class="fs-7 mw-400px">{{ Str::limit($message->body, 140) }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $statusColors[$message->status] ?? 'secondary' }}">{{ ucfirst($message->status) }}</span>
                                        @if ($message->error)
                                            <div class="text-danger fs-9 mt-1">{{ Str::limit($message->error, 120) }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-10">No messages sent yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $messages->links() }}
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
