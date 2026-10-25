<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AppointmentFeedback;
use App\Services\Staff\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * The customer-facing rating page. No login: the signed, expiring URL is the access check.
 */
class PublicFeedbackController extends Controller
{
    public function show(int $feedback): View
    {
        return view('pages/apps.appointment-management.feedback', [
            'feedback' => $this->find($feedback),
            'submitUrl' => URL::temporarySignedRoute('feedback.store', now()->addDay(), ['feedback' => $feedback]),
        ]);
    }

    public function store(Request $request, int $feedback, FeedbackService $feedbackService): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], ['rating.required' => 'Tap a star to rate your visit.']);

        $record = $this->find($feedback);
        $feedbackService->submit($record, (int) $data['rating'], $data['comment'] ?? null);

        return redirect()->to(URL::temporarySignedRoute('feedback.show', now()->addDay(), ['feedback' => $feedback]));
    }

    private function find(int $id): AppointmentFeedback
    {
        return AppointmentFeedback::withoutTenantScope()->with(['tenant', 'staff', 'appointment.branch'])->findOrFail($id);
    }
}
