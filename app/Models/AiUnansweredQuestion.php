<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUnansweredQuestion extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_unanswered_questions';

    protected $guarded = ['*'];

    protected $hidden = ['question'];

    protected function casts(): array
    {
        return ['question' => 'encrypted', 'reviewed_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

}
