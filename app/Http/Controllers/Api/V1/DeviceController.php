<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $devices = Device::get();

        foreach ($devices as $device) {
            $device->image = url(Storage::url('uploads/devices/' . $device->image));
        }

        return response()->json([
            'success' => true,
            'message' => 'date devices',
            'data' => $devices
        ]);
    }

    public function show($id)
    {
        $device = Device::where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'data device',
            'data' => $device
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'name' => 'required',
            'price' => 'required',
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
            $imagePath = '/uploads/devices/' . $imageName;
            Storage::disk('public')->put($imagePath, base64_decode($image));

            $device = Device::create([
                'type' => $request->type,
                'name' => $request->name,
                'image' => $imageName,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'data device berhasil dibuat',
                'data' => $device
            ]);
        } else {
            $device = Device::create([
                'type' => $request->type,
                'name' => $request->name,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'data device berhasil dibuat',
                'data' => $device
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $device = Device::where('id', $id)->first();

        if ($request->image === null) {
            $device->update([
                'type' => $request->type,
                'name' => $request->name,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'device berhasil diubah',
                'data' => $device
            ]);
        } else {
            Storage::disk('public')->delete('/uploads/devices/' . $device->image);

            $image_64 = $request->image;
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.' . $extension;
            $imagePath = '/uploads/devices/' . $imageName;
            Storage::disk('public')->put($imagePath, base64_decode($image));

            $device->update([
                'type' => $request->type,
                'name' => $request->name,
                'image' => $imageName,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'device berhasil diubah',
                'data' => $device
            ]);
        }
    }
}
