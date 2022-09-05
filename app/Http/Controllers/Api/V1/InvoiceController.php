<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $email = $request->email;

        $invoices = Invoice::where('email', $email)->orderBy('created_at', 'desc')->get();

        foreach ($invoices as $invoice) {
            $invoice->at = date("Y-m-d H:i:s", $invoice->at);
            $invoice->expired_at = date("Y-m-d H:i:s", $invoice->expired_at);
        }

        return response()->json([
            'success' => true,
            'message' => 'data invoices',
            'data' => $invoices
        ]);
    }
}
