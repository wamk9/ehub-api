<?php

namespace App\Models\Category;

use App\Models\EHub\Runmode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EventFormSchema extends Model
{
    public $timestamps = false;

    protected $table = 'event_form_schemas';

    protected $fillable = [
        'category_id',
        'subcategory_id',
        'runmode_id',
        'form_json',
    ];

    protected $casts = [
        'form_json' => 'array',
        'created_at' => 'datetime',
    ];

    use HasUuids;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function runmode()
    {
        return $this->belongsTo(Runmode::class);
    }

    public static function latestFor(string $categoryId, ?string $subcategoryId, string $runmodeId): ?self
    {
        return self::where('category_id', $categoryId)
            ->where('subcategory_id', $subcategoryId)
            ->where('runmode_id', $runmodeId)
            ->latest('created_at')
            ->first();
    }
}
