<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $table = 'organizations';

    protected $fillable = [
        'name', 'route', 'description', 'logo_image',
        'about', 'instagram', 'facebook', 'x_twitter', 'website', 'phone', 'contact_email',
    ];

    use HasFactory, HasUuids;

    public function members()
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function events()
    {
        return $this->hasMany(OrganizationEvent::class);
    }
}
