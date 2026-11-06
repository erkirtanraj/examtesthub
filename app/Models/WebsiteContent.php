<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteContent extends Model
{
    protected $fillable = ['type', 'key', 'title', 'slug', 'content', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return ['content' => 'array', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }
}
