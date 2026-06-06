<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function showCategories()
    {
        $categories = Category::with('runmodes:id,key')->get();
        return response()->json(['message' => $categories], 200);
    }

    public function showSubCategories(Request $request)
    {
        $category = Category::where('route', $request->route('categoryRoute'))->first();
        $subcategories = $category->subcategories;
        return response()->json(['message' => $subcategories], 200);
    }
}
