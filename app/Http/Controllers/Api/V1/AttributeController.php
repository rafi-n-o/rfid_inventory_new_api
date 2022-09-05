<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $attributes = DB::table($user->company->prefix . "_attributes")->get();
        foreach ($attributes as $attribute) {
            if ($attribute->type === "list") {
                $list = json_decode($attribute->list);
                $attribute->list = $list;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'data atribut',
            'data' => $attributes
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $attribute = new Attribute();
        $attribute->prefixTableName($user->company->prefix);
        $attribute->create([
            'name' => $request->name,
            'type' => $request->type,
            'list' => $request->list,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'atribut berhasil dibuat',
            'data' => $attribute
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        DB::table($user->company->prefix . "_attributes")->where('id', $id)->update([
            'name' => $request->name,
            'type' => $request->type,
            'list' => $request->list
        ]);

        return response()->json([
            'success' => true,
            'message' => 'atribut berhasil diubah',
        ]);
    }
}
