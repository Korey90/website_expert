<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BriefingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'service_slug',
        'type',
        'language',
        'title',
        'description',
        'sections',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'sections'  => 'array',
        'is_active' => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function briefings(): HasMany
    {
        return $this->hasMany(Briefing::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForService(Builder $query, string $slug): Builder
    {
        return $query->where('service_slug', $slug);
    }

    public function scopeForLanguage(Builder $query, string $lang): Builder
    {
        return $query->where('language', $lang);
    }

    /**
     * Returns templates visible to the current business:
     * - templates belonging to the current business, OR
     * - global templates (business_id = null)
     */
    public function scopeForBusiness(Builder $query, ?int $businessId = null): Builder
    {
        $id = $businessId ?? currentBusiness()?->id;

        return $query->where(function (Builder $q) use ($id) {
            $q->whereNull('business_id');
            if ($id) {
                $q->orWhere('business_id', $id);
            }
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isGlobal(): bool
    {
        return $this->business_id === null;
    }

    /**
     * Returns flat list of all questions across all sections.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allQuestions(): array
    {
        $questions = [];
        foreach ($this->sections ?? [] as $section) {
            foreach ($section['questions'] ?? [] as $question) {
                $questions[] = $question;
            }
        }
        return $questions;
    }

    /**
     * Returns keys of all required questions.
     *
     * @return array<int, string>
     */
    public function requiredQuestionKeys(): array
    {
        return array_values(
            array_map(
                fn ($q) => $q['key'],
                array_filter($this->allQuestions(), fn ($q) => !empty($q['required']))
            )
        );
    }
}
