<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // Fetch all categories with their related listing count
        $categories = Category::withCount('listings')->get();

        // Add a default image for each category (replace with actual image logic if available)
        $categories = $categories->map(function ($category) {
            $category->image = 'https://picsum.photos/200'; // Replace with your logic for category images
            return $category;
        });

        return response()->json($categories);
    }
}
