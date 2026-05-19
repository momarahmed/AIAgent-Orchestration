<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeGraphNode extends Model
{
    protected $fillable = ['tenant_id', 'node_type', 'ref_type', 'ref_id', 'label', 'properties'];
    protected $casts = ['properties' => 'array'];
}
