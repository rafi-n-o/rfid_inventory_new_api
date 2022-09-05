<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::get();

        foreach ($orders as $order) {
            $order->service_data = json_decode($order->service_data);
            $order->cart = json_decode($order->cart);
            $order->image = url(Storage::url('uploads/payment-proofs/' . $order->image));
        }

        return response()->json([
            'success' => true,
            'message' => 'data orders',
            'data' => $orders
        ]);
    }

    public function getByToken($token)
    {
        $order = Order::where('token', $token)->first();

        $order->service_data = json_decode($order->service_data);
        $order->cart = json_decode($order->cart);
        $order->image = url(Storage::url('uploads/payment-proofs/' . $order->image));

        return response()->json([
            'success' => true,
            'message' => 'data order',
            'data' => $order
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_data' => 'required',
            'cart' => 'required',
            'email' => 'required',
            'payment_type' => 'required',
            'checkbox' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        if ($request->checkbox === false) {
            return response()->json([
                'success' => false,
                'message' => 'checkbox belum dicentang',
            ], 400);
        }

        $token = Str::random(32);

        Order::create([
            'service_data' => $request->service_data,
            'cart' => $request->cart,
            'email' => $request->email,
            'payment_type' => $request->payment_type,
            'token' => $token
        ]);

        $order = [
            'service_data' => json_decode($request->service_data),
            'cart' => json_decode($request->cart),
            'email' => $request->email,
            'payment_type' => $request->payment_type,
            'token' => $token
        ];

        Mail::to($request->email)->send(new \App\Mail\OrderMail($order));

        return response()->json([
            'success' => true,
            'message' => 'berhasil dikirim, silahkan cek email untuk melanjutkan',
        ]);
    }

    public function uploadPaymentProof(Request $request, $token)
    {
        $order = Order::where('token', $token)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'order not found',
            ], 404);
        }

        $image_64 = $request->image;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $imageName = Str::random(10) . '.' . $extension;
        $imagePath = '/uploads/payment-proofs/' . $imageName;
        Storage::disk('public')->put($imagePath, base64_decode($image));

        $order->update([
            'image' => $imageName,
            'status' => 'wait'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'upload bukti pembayaran berhasil',
        ]);
    }

    public function approve($id)
    {
        $order = Order::where('id', $id)->first();

        $order->update([
            'status' => 'success'
        ]);

        $order->service_data = json_decode($order->service_data);
        $order->cart = json_decode($order->cart);

        $randomNumber = rand(1000, 9999);
        $invoice = Invoice::create([
            'email' => $order->email,
            'receipt_number' => "INV/" . date("ymd") . "/" . $randomNumber,
            'at' => strtotime(date("Y-m-d")),
            'expired_at' => strtotime(date('Y-m-d', strtotime('+' . $order->service_data->month . ' month', strtotime(date("Y-m-d"))))),
            'order_id' => $order->id,
        ]);

        $details = [
            'service_data' => $order->service_data,
            'cart' => $order->cart,
            'email' => $order->email,
            'payment_type' => $order->payment_type,
            'token' => $order->token,
            'status' => $order->status,
            'invoice' => $invoice,
        ];

        Mail::to($order->email)->send(new \App\Mail\OrderApproveMail($details));

        return response()->json([
            'success' => true,
            'message' => 'order berhasil di approve'
        ]);
    }
}
