<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A consultation / consent form filled in before a treatment.
 */
class CustomerConsultation extends Model
{
    use BelongsToTenant;

    /** Questions on the form: key => [label, type]. */
    public const QUESTIONS = [
        'hair_type' => ['Hair type', 'select', ['Straight', 'Wavy', 'Curly', 'Coily', 'Not applicable']],
        'skin_type' => ['Skin type', 'select', ['Normal', 'Dry', 'Oily', 'Combination', 'Sensitive', 'Not applicable']],
        'scalp_or_skin_conditions' => ['Scalp or skin conditions (eczema, psoriasis, wounds...)', 'text'],
        'medical_conditions' => ['Medical conditions we should know about', 'text'],
        'medications' => ['Current medication (incl. retinoids, blood thinners)', 'text'],
        'pregnant_or_breastfeeding' => ['Pregnant or breastfeeding', 'yesno'],
        'recent_chemical_treatments' => ['Colour, perm or chemical treatments in the last 3 months', 'text'],
        'patch_test_done' => ['Patch test done (for colour / lash / brow tint)', 'yesno'],
        'allergies' => ['Allergies or reactions (products, latex, fragrance...)', 'text'],
        'goals' => ['What would you like to achieve today?', 'text'],
    ];

    protected $guarded = [];

    protected $casts = [
        'answers' => 'array',
        'consent_given' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Answers worth flagging to the stylist at a glance.
     *
     * @return array<int, string>
     */
    public function warnings(): array
    {
        $answers = $this->answers ?? [];

        return array_values(array_filter([
            ($answers['pregnant_or_breastfeeding'] ?? null) === 'yes' ? 'Pregnant or breastfeeding' : null,
            ($answers['patch_test_done'] ?? null) === 'no' ? 'No patch test' : null,
            filled($answers['allergies'] ?? null) ? 'Allergies: ' . $answers['allergies'] : null,
            filled($answers['medical_conditions'] ?? null) ? 'Medical: ' . $answers['medical_conditions'] : null,
        ]));
    }
}
