<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrgFollow extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = 'org_follows';

    protected $fillable = ['organization_id', 'user_id'];
}
