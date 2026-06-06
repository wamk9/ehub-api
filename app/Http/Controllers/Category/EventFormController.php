<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category\Category;
use App\Models\Category\EventFormSchema;
use App\Models\EHub\Runmode;
use Database\Seeders\EventFormSchemaSeeder;
use Illuminate\Http\Request;

class EventFormController extends Controller
{
    public function getForm(Request $request)
    {
        $categoryRoute = $request->route('categoryRoute');
        $runmodeKey    = $request->route('runmodeKey');

        $category = Category::where('route', $categoryRoute)->first();
        if (!$category)
            return response()->json(['message' => 'category_not_found'], 404);

        $runmode = Runmode::where('key', $runmodeKey)->first();
        if (!$runmode)
            return response()->json(['message' => 'runmode_not_found'], 404);

        $subcategoryId = $request->query('subcategory')
            ? \App\Models\Category\SubCategory::where('route', $request->query('subcategory'))
                ->where('category_id', $category->id)
                ->value('id')
            : null;

        $schema = EventFormSchema::latestFor($category->id, $subcategoryId, $runmode->id);

        $advancedForm = $schema
            ? $schema->form_json
            : ['form' => [[]], 'data' => []];

        return response()->json([
            'message' => [
                'schema_id'         => $schema?->id,
                'schema_updated_at' => $schema?->created_at,
                'basic'             => EventFormSchemaSeeder::basicForm(),
                'advanced'          => $advancedForm,
            ]
        ], 200);
    }
}
