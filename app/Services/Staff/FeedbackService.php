<?php

namespace App\Services\Staff;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * After a visit the customer gets a link to rate it (1–5 stars). Ratings feed the staff performance report.
 */
class FeedbackService
{
    public const LINK_DAYS = 14;

    public function __construct(
        private readonly MessagingService $messaging,
        private readonly SettingService $settings,
    ) {
    }

    public function url(AppointmentFeedback $feedback): string
    {
        return URL::temporarySignedRoute('feedback.show', now()->addDays(self::LINK_DAYS), ['feedback' => $feedback->id]);
    }

    /**
     * Creates the feedback slot and texts the link, once per appointment, when the salon has it switched on.
     */
    public function requestFor(Appointment $appointment, ?User $user = null): ?AppointmentFeedback
    {
        $appointment->loadMissing(['customer', 'tenant']);

        if (! $this->settings->get('notification.send_feedback_request', $appointment->tenant_id, $appointment->branch_id)
            || ! $appointment->customer?->phone) {
            return null;
        }

        $feedback = AppointmentFeedback::withoutTenantScope()->firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'tenant_id' => $appointment->tenant_id,
                'branch_id' => $appointment->branch_id,
                'customer_id' => $appointment->customer_id,
                'staff_id' => $appointment->staff_id,
            ]
        );

        if ($feedback->requested_at) {
            return $feedback;
        }

        $body = $this->messaging->render($this->settings->get('template.feedback_request', $appointment->tenant_id, $appointment->branch_id), [
            'customer_name' => $appointment->customer->first_name ?: $appointment->customer->full_name,
            'salon_name' => $appointment->tenant?->name,
            'feedback_url' => $this->url($feedback),
        ]);

        if ($this->messaging->send($appointment->tenant_id, $appointment->branch_id, 'feedback_request', $appointment->customer->phone, $body, $appointment, $user)) {
            $feedback->update(['requested_at' => now()]);
        }

        return $feedback;
    }

    public function submit(AppointmentFeedback $feedback, int $rating, ?string $comment): AppointmentFeedback
    {
        if ($feedback->submitted_at) {
            throw ValidationException::withMessages(['rating' => 'Thanks, we already have your feedback for this visit.']);
        }

        $feedback->update([
            'rating' => max(1, min(5, $rating)),
            'comment' => $comment ? mb_substr(trim($comment), 0, 2000) : null,
            'submitted_at' => now(),
        ]);

        return $feedback;
    }
}
