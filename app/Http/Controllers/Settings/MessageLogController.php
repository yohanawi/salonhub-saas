<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Services\Messaging\MessagingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * What was sent to customers and whether the provider accepted it, plus a test send to check the
 * SMS/WhatsApp settings before going live.
 */
class MessageLogController extends Controller
{
    public function index(Request $request, MessagingService $messaging): View
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can('settings.notification.manage'), 403);

        return view('pages/apps.settings.messages', [
            'messages' => MessageLog::withoutTenantScope()
                ->where('tenant_id', $user->tenant_id)
                ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
                ->when($request->filled('event'), fn (Builder $query) => $query->where('event', $request->string('event')->toString()))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'channels' => $messaging->channels($user->tenant_id, $user->branch_id),
        ]);
    }

    public function test(Request $request, MessagingService $messaging): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can('settings.notification.manage'), 403);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $log = $messaging->send(
            $user->tenant_id,
            $user->branch_id,
            MessageLog::EVENT_TEST,
            $data['phone'],
            'Test message from ' . $user->tenant->name . '. Your SMS/WhatsApp settings are working.',
            null,
            $user
        );

        if (! $log) {
            throw ValidationException::withMessages([
                'phone' => $messaging->enabled($user->tenant_id, $user->branch_id)
                    ? 'Enter a valid mobile number, e.g. 0771234567.'
                    : 'Turn on SMS or WhatsApp and add the provider details in Settings first.',
            ]);
        }

        $log->refresh();

        return back()->with('status', $log->status === MessageLog::STATUS_FAILED
            ? 'The provider rejected the test message: ' . $log->error
            : 'Test message sent to ' . $log->recipient . '.');
    }
}
