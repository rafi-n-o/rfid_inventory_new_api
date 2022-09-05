<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $warehouses = Warehouse::where('company_id', $user->company->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'data warehouses',
            'data' => $warehouses
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $warehouse = Warehouse::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'company_id' => $user->company->id,
        ]);

        $location = DB::table($user->company->prefix . "_locations")->insertGetId([
            'name' => 'default',
            'warehouse_id' => $warehouse->id,
            'path' => ""
        ]);

        if ($location) {
            DB::table($user->company->prefix . "_locations")->where('id', $location)->update([
                'path' => $warehouse->id .  "." . $location
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'warehouse berhasil dibuat',
            'data' => $warehouse,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $warehouse = Warehouse::where('id', $id)->first();
        $locations = DB::table($user->company->prefix . "_locations")->where('warehouse_id', $id)->get();
        $warehouse->locations = $locations;

        foreach ($locations as $location) {
            $paths = explode(".", $location->path);

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

            $location->path_name = $pathName;
        }

        return response()->json([
            'success' => true,
            'message' => 'data warehouse',
            'data' => $warehouse
        ]);
    }

    public function inStock(Request $request, $id)
    {
        $user = $request->user();

        $items = DB::table($user->company->prefix . "_items")
            ->groupBy('product_id')
            ->select('product_id', 'warehouse_id')
            ->where('warehouse_id', $id)
            ->where('in_stock', true)
            ->get();

        foreach ($items as $item) {
            $item->warehouse = Warehouse::select('name')
                ->where('id', $item->warehouse_id)
                ->first();
            $item->product = DB::table($user->company->prefix . "_products")
                ->select('name', 'image')
                ->where('id', $item->product_id)
                ->first();
            $item->product->image = url(Storage::url('uploads/products/' .  $item->product->image));


            $item->product->items = DB::table($user->company->prefix . "_items")
                ->groupBy('attribute1_value', 'attribute2_value', 'attribute3_value')
                ->select('attribute1_value', 'attribute2_value', 'attribute3_value', DB::raw('count(*) as qty'))
                ->where('warehouse_id', $id)
                ->where('product_id', $item->product_id)
                ->where('in_stock', true)
                ->get();

            $qty = 0;
            foreach ($item->product->items as $pi) {
                $pi->items = DB::table($user->company->prefix . "_items")
                    ->select('epc', 'location_id')
                    ->where('warehouse_id', $id)
                    ->where('attribute1_value', $pi->attribute1_value)
                    ->where('attribute2_value', $pi->attribute2_value)
                    ->where('attribute3_value', $pi->attribute3_value)
                    ->where('product_id', $item->product_id)
                    ->where('in_stock', true)
                    ->get();

                $qty += $pi->qty;

                foreach ($pi->items as $pii) {
                    $pii->location = DB::table($user->company->prefix . "_locations")
                        ->select('name')->where('id', $pii->location_id)
                        ->first();
                }
            }

            $item->product->qty = $qty;
        }

        return response()->json([
            'success' => true,
            'message' => 'product instock',
            'data' => $items
        ]);
    }

    public function inStockByLocation(Request $request, $path)
    {
    }
}
