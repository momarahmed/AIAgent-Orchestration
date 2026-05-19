<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BridgeExecution extends Model
{
    protected $fillable = [
        'bridge_connection_id', 'workflow_run_id', 'action',
        'input', 'output', 'status', 'duration_ms', 'cost_usd', 'error',
    ];
    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'cost_usd' => 'float',
    ];

    public function connection(): BelongsTo { return $this->belongsTo(BridgeConnection::class, 'bridge_connection_id'); }
}
