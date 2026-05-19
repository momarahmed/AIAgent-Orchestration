<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'project_id', 'user_id',
        'event_type', 'subject_type', 'subject_id',
        'action', 'payload', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
