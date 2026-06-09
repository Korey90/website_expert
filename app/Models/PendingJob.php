<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $queue
 * @property string $payload
 * @property int $attempts
 * @property int|null $reserved_at
 * @property int $available_at
 * @property int $created_at
 */
class PendingJob extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $fillable = [
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
        'created_at',
    ];

    public function decodedPayload(): array
    {
        return json_decode($this->payload, true) ?? [];
    }

    public function jobClass(): string
    {
        $data = $this->decodedPayload();

        return data_get($data, 'displayName', data_get($data, 'job', 'Unknown'));
    }

    public function isReserved(): bool
    {
        return $this->reserved_at !== null;
    }

    public function createdAtCarbon(): Carbon
    {
        return Carbon::createFromTimestamp($this->created_at);
    }

    public function availableAtCarbon(): Carbon
    {
        return Carbon::createFromTimestamp($this->available_at);
    }
}
