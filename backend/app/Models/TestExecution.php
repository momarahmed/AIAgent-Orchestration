<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestExecution extends Model
{
    protected $fillable = [
        'test_suite_id', 'deployment_id', 'status', 'results',
        'passed', 'failed', 'errors', 'duration_ms',
        'triggered_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function suite()
    {
        return $this->belongsTo(TestSuite::class, 'test_suite_id');
    }
}
