<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeGraphEdge extends Model
{
    protected $fillable = ['from_node_id', 'to_node_id', 'relation', 'properties'];
    protected $casts = ['properties' => 'array'];
}
