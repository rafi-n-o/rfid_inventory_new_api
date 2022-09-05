<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->category;
        $groupCategory = $request->group_category;

        if ($category) {
            $services = Service::where('category', $category)->get();
            foreach ($services as $service) {
                $discount = (($service->price * $service->discount) / 100);
                $total = ($service->price - $discount);
                $service->total = $total;
            }
        } else if ($groupCategory) {
            $services = Service::groupBy('category')->get();
            foreach ($services as $service) {
                $discount = (($service->price * $service->discount) / 100);
                $total = ($service->price - $discount);
                $service->total = $total;
            }
        } else {
            $services = Service::get();
            foreach ($services as $service) {
                $discount = (($service->price * $service->discount) / 100);
                $total = ($service->price - $discount);
                $service->total = $total;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'data services',
            'data' => $services
        ]);
    }

    public function groupByCategory()
    {
        $services = Service::groupBy('category')->get();

        return response()->json([
            'success' => true,
            'message' => 'data services',
            'data' => $services
        ]);
    }

    public function show($id)
    {
        $service = Service::where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'data service',
            'data' => $service
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required',
            'month' => 'required',
            'price' => 'required',
            'discount' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $service = Service::create([
            'category' => $request->category,
            'month' => $request->month,
            'price' => $request->price,
            'discount' => $request->discount
        ]);

        return response()->json([
            'success' => true,
            'message' => 'service created',
            'data' => $service
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required',
            'month' => 'required',
            'price' => 'required',
            'discount' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $service = Service::where('id', $id)->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'service not found',
            ], 404);
        }

        $service->update([
            'category' => $request->category,
            'month' => $request->month,
            'price' => $request->price,
            'discount' => $request->discount
        ]);

        return response()->json([
            'success' => true,
            'message' => 'service updated',
            'data' => $service
        ]);
    }
}
