<?php

namespace App\Models\Category;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    public $timestamps = false;

    protected $table = 'subcategories';

    protected $fillable = ['name', 'description', 'route', 'category_id'];

    protected $hidden = ['category_id'];

    use HasFactory, HasUuids;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
