<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $categories = DB::table($user->company->prefix . "_categories")->get();

        return response()->json([
            'success' => true,
            'message' => 'data kategori',
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $category = new Category();
        $category->prefixTableName($user->company->prefix);
        $category->create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'kategori berhasil dibuat',
            'data' => $category
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        DB::table($user->company->prefix . "_categories")->where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'kategori berhasil diubah'
        ]);
    }
}
