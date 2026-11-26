<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLead extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_leads';

    protected $guarded = ['*'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

}
