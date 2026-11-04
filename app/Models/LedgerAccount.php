<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class LedgerAccount extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations;

    public const TYPES = ['asset', 'liability', 'equity', 'income', 'expense'];

    public const NORMAL_BALANCES = ['debit', 'credit'];

    public array $translatable = ['name'];

    protected $fillable = [
        'business_id', 'code', 'name', 'type', 'subtype', 'normal_balance',
        'parent_id', 'is_postable', 'is_system', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_postable' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function expenseItems(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function recurringExpenseItems(): HasMany
    {
        return $this->hasMany(RecurringExpenseTemplateItem::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'default_ledger_account_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function scopeActivePostable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_postable', true);
    }

    public function isReferenced(): bool
    {
        return $this->children()->exists()
            || $this->expenseItems()->exists()
            || $this->recurringExpenseItems()->exists()
            || $this->suppliers()->exists()
            || $this->journalLines()->exists();
    }
}
