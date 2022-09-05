<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->warehouse;
        $productId = $request->product;

        if ($warehouseId && $productId) {
            $items = DB::table($user->company->prefix . "_items")->where('warehouse_id', $warehouseId)->where('product_id', $productId)->get();
        } else if ($warehouseId) {
            $items = DB::table($user->company->prefix . "_items")->where('warehouse_id', $warehouseId)->get();
        } else {
            $items = DB::table($user->company->prefix . "_items")->get();
        }

        foreach ($items as $item) {
            $item->product = DB::table($user->company->prefix . "_products")->select('name', 'image')->where('id', $item->product_id)->first();
            $item->product->image = url(Storage::url('uploads/products/' . $item->product->image));
            $item->location = DB::table($user->company->prefix . "_locations")->select('name')->where('id', $item->location_id)->first();
            $item->warehouse = DB::table("warehouses")->select('name')->where('id', $item->warehouse_id)->first();

            if ($item->in_stock === 0) {
                $item->in_stock = false;
            } else {
                $item->in_stock = true;
            }

            if ($item->on_transfer === 0) {
                $item->on_transfer = false;
            } else {
                $item->on_transfer = true;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'data item',
            'data' => $items,
        ]);
    }

    public function getByEpc(Request $request, $epc)
    {
        $user = $request->user();

        $item = DB::table($user->company->prefix . "_items")->where('epc', $epc)->first();

        if ($item) {
            $item->product = DB::table($user->company->prefix . "_products")->select('name')->where('id', $item->product_id)->first();
            $item->location = DB::table($user->company->prefix . "_locations")->select('name')->where('id', $item->location_id)->first();
            if ($item->in_stock === 0) {
                $item->in_stock = false;
            } else {
                $item->in_stock = true;
            }

            if ($item->on_transfer === 0) {
                $item->on_transfer = false;
            } else {
                $item->on_transfer = true;
            }

            $paths = explode(".", $item->path);

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

            $item->location->path_name = $pathName;

            return response()->json([
                'success' => true,
                'message' => 'data item by epc',
                'data' => $item
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'item by epc not found',
                'data' => null
            ], 400);
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'epc' => 'required',
            'in_stock' => 'required',
            'on_transfer' => 'required',
            'product_id' => 'required',
            'warehouse_id' => 'required',
            'location_id' => 'required',
            'path' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $product = DB::table($user->company->prefix . '_products')->where('id', $request->product_id)->first();

        $listEpc = $request->epc;

        $item = new Item();
        $item->prefixTableName($user->company->prefix);

        foreach ($listEpc as $epc) {
            $item->create([
                'epc' => $epc,
                'in_stock' => $request->in_stock,
                'on_transfer' => $request->on_transfer,
                'attribute1_value' => $request->attribute1_value,
                'attribute2_value' => $request->attribute2_value,
                'attribute3_value' => $request->attribute3_value,
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'location_id' => $request->location_id,
                'path' => $request->path
            ]);

            // epc_log
            DB::table($user->company->prefix . '_epc_logs')->insert([
                'at' => Carbon::now(),
                'epc' => $epc,
                'note' => 'tag registered as ' . $product->name . " " . $request->attribute1_value . " " . $request->attribute2_value . " " . $request->attribute3_value,
                'activity' => 'tag-registration',
                'user_id' => $user->id,
                'user_data' => json_encode($user)
            ]);
            // epc_log
        }

        return response()->json([
            'success' => true,
            'message' => 'produk berhasil dibuat',
            'data' => $item
        ]);
    }

    public function inStock(Request $request, $wid)
    {
        $user = $request->user();
        $items = DB::table($user->company->prefix . "_items")->where('warehouse_id', $wid)
            ->where('in_stock', true)->get()->groupBy('product_id');

        $data = array();
        foreach ($items as $i => $item) {
            $epcs = array();
            foreach ($item as $a) {
                $fullAttr = $a->attribute1_value . $a->attribute2_value . $a->attribute3_value;
                $a->full_attr = $fullAttr;
                $epcs[] = $a->epc;
            }

            $groupedItem = array();
            foreach ($item as $b) {
                $groupedItem[$b->full_attr][] = $b;
                unset($b->full_attr);
            }

            $cleanedGroupedItem = array();
            $productQty = 0;
            foreach ($groupedItem as $gi) {

                $epcList = array();
                $locList = array();
                foreach ($gi as $gItem) {

                    $paths = explode(".", $gItem->path);
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

                    $loc = [
                        'id' => $gItem->location_id,
                        'path' => $gItem->path,
                        'path_name' => $pathName
                    ];

                    $epcList[] = [
                        'epc' => $gItem->epc,
                        'location' => $loc
                    ];
                }

                $cleanedGroupedItem[] = [
                    'attribute1_value' => $gi[0]->attribute1_value,
                    'attribute2_value' => $gi[0]->attribute2_value,
                    'attribute3_value' => $gi[0]->attribute3_value,
                    'item_qty' => count($epcList),
                    'epc_list' => $epcList
                ];
                $productQty += count($epcList);
            }

            $product = DB::table($user->company->prefix . '_products')->select('id', 'name', 'image', 'category_id')
                ->where('id', $i)->first();

            $category = DB::table($user->company->prefix . "_categories")->select('id', 'name')
                ->where('id', $product->category_id)->first();
            $product->category = $category;

            $product->image = url(Storage::url('uploads/products/' . $product->image));

            $product->product_data = $cleanedGroupedItem;
            $product->product_qty = $productQty;
            $data[] = $product;
        }

        return response()->json([
            'success' => true,
            'message' => 'warehouse instock',
            'data' => $data
        ]);
    }

    public function inStockByPath(Request $request, $wid)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'path' => 'required',
        ]);

        $path = $request->path;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $items = DB::table($user->company->prefix . "_items")->where('warehouse_id', $wid)
            ->where('in_stock', true)->where('path', 'like', $path.'%')->orderBy('path')->get();

        foreach($items as $item){
            $product = DB::table($user->company->prefix . '_products')->select('name')
                ->where('id', $item->product_id)->first();
            $item->in_stock = $item->in_stock == 1; 
            $item->on_transfer = $item->on_transfer == 1; 
            $item->product = $product;

            $paths = explode(".", $item->path);
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
            $loc = [
                'id' => $item->location_id,
                'path' => $item->path,
                'path_name' => $pathName
            ];
            $item->location = $loc;
        }

        if(count($items) > 0){
            return response()->json([
                'success' => true,
                'message' => 'Stok By Location.',
                'data' => $items
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'No Data.',
                'data' => []
            ]);
        }
    }
}
