<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeDocument extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_knowledge_documents';

    // Only trusted services may assign tenant and lifecycle fields.
    protected $guarded = ['*'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
