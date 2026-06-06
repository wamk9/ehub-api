<?php

namespace App\Models\Category;

use App\Models\EHub\Runmode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description', 'route'];

    use HasFactory, HasUuids;

    public function subcategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id');
    }

    public function runmodes()
    {
        return $this->belongsToMany(Runmode::class, 'category_runmode', 'category_id', 'runmode_id');
    }
}
