<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_conversations';

    protected $guarded = ['*'];

    protected $hidden = ['visitor_hash'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(AiAssistant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

}
