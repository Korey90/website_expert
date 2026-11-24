<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeChunk extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_knowledge_chunks';

    // Only trusted services may assign tenant and lifecycle fields.
    protected $guarded = ['*'];

    protected $hidden = ['embedding', 'text'];

    protected function casts(): array
    {
        return ['embedding' => 'array', 'dimensions' => 'integer', 'ordinal' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
