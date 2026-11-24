<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistant extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'ai_assistants';

    // Only trusted services may assign tenant and lifecycle fields.
    protected $guarded = ['*'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
