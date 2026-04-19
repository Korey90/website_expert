<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Briefing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'lead_id',
        'briefing_template_id',
        'conducted_by',
        'client_token',
        'client_submitted_at',
        'title',
        'type',
        'language',
        'status',
        'answers',
        'autosave_at',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'answers'              => 'array',
        'client_submitted_at'  => 'datetime',
        'autosave_at'          => 'datetime',
        'completed_at'         => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BriefingTemplate::class, 'briefing_template_id');
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $query, ?int $businessId = null): Builder
    {
        $id = $businessId ?? currentBusiness()?->id;

        if ($id) {
            $query->where('business_id', $id);
        }

        return $query;
    }

    // ── Status helpers ─────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'in_progress']);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function wasSubmittedByClient(): bool
    {
        return $this->client_submitted_at !== null;
    }

    // ── Progress ───────────────────────────────────────────────────────────

    /**
     * Returns percentage of required questions that have a non-empty answer.
     */
    public function getProgressPercentage(): int
    {
        if (!$this->template) {
            return 0;
        }

        $required = $this->template->requiredQuestionKeys();

        if (empty($required)) {
            return 100;
        }

        $answers = $this->answers ?? [];
        $filled  = 0;

        foreach ($required as $key) {
            // answers structure: { section_key: { question_key: value } }
            foreach ($answers as $sectionAnswers) {
                if (isset($sectionAnswers[$key]) && $sectionAnswers[$key] !== '' && $sectionAnswers[$key] !== null) {
                    $filled++;
                    break;
                }
            }
        }

        return (int) round(($filled / count($required)) * 100);
    }

    /**
     * Returns keys of required questions that have no answer yet.
     *
     * @return array<int, string>
     */
    public function missingRequiredKeys(): array
    {
        if (!$this->template) {
            return [];
        }

        $required = $this->template->requiredQuestionKeys();
        $answers  = $this->answers ?? [];
        $missing  = [];

        foreach ($required as $key) {
            $found = false;
            foreach ($answers as $sectionAnswers) {
                if (isset($sectionAnswers[$key]) && $sectionAnswers[$key] !== '' && $sectionAnswers[$key] !== null) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
