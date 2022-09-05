<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $locations = DB::table($user->company->prefix . "_locations")->get();

        return response()->json([
            'success' => true,
            'message' => 'data locations',
            'data' => $locations
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'warehouse_id' => 'required',
            'root_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $locationPath = DB::table($user->company->prefix . "_locations")->where('id', $request->root_id)->first();

        $location = DB::table($user->company->prefix . "_locations")->insertGetId([
            'name' => $request->name,
            'warehouse_id' => $request->warehouse_id,
            'path' => ""
        ]);

        if ($location) {
            DB::table($user->company->prefix . "_locations")->where('id', $location)->update([
                'path' => $locationPath->path . "." . $location
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'lokasi berhasil dibuat',
            'data' => $location
        ]);
    }

    public function getByWarehouseId(Request $request, $id)
    {
        $user = $request->user();

        $locations = DB::table($user->company->prefix . "_locations")->where('warehouse_id', $id)->get();
        foreach($locations as $loc){
            $paths = explode(".", $loc->path);
            $pathName = "";
            foreach ($paths as $key => $path) {
                if ($key > 0) {
                    $pathLocation = DB::table($user->company->prefix . "_locations")->where('id', $path)->first();
                    if ($key < count($paths)) {
                        $pathName .=  "/" . $pathLocation->name;
                    } else {
                        $pathName .= $pathLocation->name;
                    }
                }
            }
            
            $loc->full_name = $pathName;
        }

        return response()->json([
            'success' => true,
            'message' => 'data location by warehouse_id',
            'data' => $locations
        ]);
    }
}
