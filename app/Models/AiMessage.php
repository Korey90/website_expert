<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_messages';

    protected $guarded = ['*'];

    protected $hidden = ['content'];

    protected function casts(): array
    {
        return ['content' => 'encrypted', 'sources' => 'array'];
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
