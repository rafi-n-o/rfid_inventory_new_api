<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $categoryId = $request->category;

        if ($categoryId) {
            $products = DB::table($user->company->prefix . "_products")->where('category_id', $categoryId)->get();
        } else {
            $products = DB::table($user->company->prefix . "_products")->get();
        }

        foreach ($products as $product) {
            $product->image = url(Storage::url('uploads/products/' . $product->image));
            $itemsInStock = DB::table($user->company->prefix . "_items")->where('product_id', $product->id)->where('in_stock', true)->get();
            $product->qty_item_in_stock = count($itemsInStock);
            $itemsOnTransfer = DB::table($user->company->prefix . "_items")->where('product_id', $product->id)->where('on_transfer', true)->get();
            $product->qty_item_on_transfer = count($itemsOnTransfer);
            $items = DB::table($user->company->prefix . "_items")->where('product_id', $product->id)->get();
            $product->qty_item = count($items);

            $product->category = DB::table($user->company->prefix . "_categories")->select('name')->where('id', $product->category_id)->first();
            $product->attribute1 = DB::table($user->company->prefix . "_attributes")->select('name', 'type', 'list')->where('id', $product->attribute1_id)->first();
            if ($product->attribute1) {
                if ($product->attribute1->type === "list") {
                    $product->attribute1->list = json_decode($product->attribute1->list);
                }
            }
            $product->attribute2 = DB::table($user->company->prefix . "_attributes")->select('name', 'type', 'list')->where('id', $product->attribute2_id)->first();
            if ($product->attribute2) {
                if ($product->attribute2->type === "list") {
                    $product->attribute2->list = json_decode($product->attribute2->list);
                }
            }
            $product->attribute3 = DB::table($user->company->prefix . "_attributes")->select('name', 'type', 'list')->where('id', $product->attribute3_id)->first();
            if ($product->attribute3) {
                if ($product->attribute3->type === "list") {
                    $product->attribute3->list = json_decode($product->attribute3->list);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'data produk',
            'data' => $products,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $product = DB::table($user->company->prefix . "_products")->where('id', $id)->first();
        $product->category = DB::table($user->company->prefix . "_categories")->select('name')->where('id', $product->category_id)->first();
        $product->attribute1 = DB::table($user->company->prefix . "_attributes")->select('name')->where('id', $product->attribute1_id)->first();
        $product->attribute2 = DB::table($user->company->prefix . "_attributes")->select('name')->where('id', $product->attribute2_id)->first();
        $product->attribute3 = DB::table($user->company->prefix . "_attributes")->select('name')->where('id', $product->attribute3_id)->first();

        return response()->json([
            'success' => true,
            'message' => 'data produk',
            'data' => $product
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        if ($request->image) {
            $image_64 = $request->image;
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.' . $extension;
            $imagePath = '/uploads/products/' . $imageName;
            Storage::disk('public')->put($imagePath, base64_decode($image));

            $product = new Product();
            $product->prefixTableName($user->company->prefix);
            $product->create([
                'name' => $request->name,
                'image' => $imageName,
                'category_id' => $request->category_id,
                'attribute1_id' => $request->attribute1_id,
                'attribute2_id' => $request->attribute2_id,
                'attribute3_id' => $request->attribute3_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'produk berhasil dibuat',
                'data' => $product,
            ]);
        } else {
            $product = new Product();
            $product->prefixTableName($user->company->prefix);
            $product->create([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'attribute1_id' => $request->attribute1_id,
                'attribute2_id' => $request->attribute2_id,
                'attribute3_id' => $request->attribute3_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'produk berhasil dibuat',
                'data' => $product,
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if ($request->image === null) {
            DB::table($user->company->prefix . '_products')->where('id', $id)->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'attribute1_id' => $request->attribute1_id,
                'attribute2_id' => $request->attribute2_id,
                'attribute3_id' => $request->attribute3_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'produk berhasil diubah',
                'data' => null
            ]);
        } else {
            $product = DB::table($user->company->prefix . '_products')->where('id', $id)->first();
            Storage::disk('public')->delete('/uploads/products/' . $product->image);

            $image_64 = $request->image;
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.' . $extension;
            $imagePath = '/uploads/products/' . $imageName;
            Storage::disk('public')->put($imagePath, base64_decode($image));

            DB::table($user->company->prefix . '_products')->where('id', $id)->update([
                'name' => $request->name,
                'image' => $imageName,
                'category_id' => $request->category_id,
                'attribute1_id' => $request->attribute1_id,
                'attribute2_id' => $request->attribute2_id,
                'attribute3_id' => $request->attribute3_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'produk berhasil diubah',
                'data' => null
            ]);
        }
    }

    public function inStock(Request $request, $id)
    {
        $user = $request->user();

        $items = DB::table($user->company->prefix . "_items")
            ->groupBy('warehouse_id')
            ->select('product_id', 'warehouse_id', DB::raw('count(*) as qty'))
            ->where('product_id', $id)
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
            $item->product->image = url(Storage::url('uploads/products/' . $item->product->image));

            $item->product->items = DB::table($user->company->prefix . "_items")
                ->groupBy('attribute1_value', 'attribute2_value', 'attribute3_value')
                ->select('attribute1_value', 'attribute2_value', 'attribute3_value', DB::raw('count(*) as qty'))
                ->where('warehouse_id', $item->warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('in_stock', true)
                ->get();

            foreach ($item->product->items as $pI) {
                $pI->items = DB::table($user->company->prefix . "_items")
                    ->select('epc', 'location_id')
                    ->where('warehouse_id', $item->warehouse_id)
                    ->where('attribute1_value', $pI->attribute1_value)
                    ->where('attribute2_value', $pI->attribute2_value)
                    ->where('attribute3_value', $pI->attribute3_value)
                    ->where('product_id', $item->product_id)
                    ->where('in_stock', true)
                    ->get();

                foreach ($pI->items as $pII) {
                    $pII->location = DB::table($user->company->prefix . "_locations")
                        ->select('name')->where('id', $pII->location_id)->first();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'product instock',
            'data' => $items
        ]);
    }
}
