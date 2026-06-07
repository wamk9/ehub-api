<?php

namespace App\Models\Organization;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationEventArticle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'org_event_articles';

    protected $fillable = [
        'organization_event_id', 'author_id', 'title', 'slug',
        'excerpt', 'content', 'cover_image', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(OrganizationEvent::class, 'organization_event_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
