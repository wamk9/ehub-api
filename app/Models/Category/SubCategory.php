<?php

namespace App\Models\Category;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    public $timestamps = false;

    protected $table = "subcategories";

    protected $fillable = [
        'name',
        'description'
    ];

    protected $hidden = [
        'subcategory_id'
    ];

    use HasFactory;
}
