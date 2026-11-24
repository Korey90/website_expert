<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeVersion extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_knowledge_versions';

    // Only trusted services may assign tenant and lifecycle fields.
    protected $guarded = ['*'];

    protected $hidden = ['content'];

    protected function casts(): array
    {
        return ['version_no' => 'integer', 'approved_at' => 'datetime', 'indexed_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
