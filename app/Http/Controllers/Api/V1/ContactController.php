<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $contacts = DB::table($user->company->prefix . "_contacts")->get();

        return response()->json([
            'success' => true,
            'message' => 'data kontak',
            'data' => $contacts
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $contact = new Contact();
        $contact->prefixTableName($user->company->prefix);
        $contact->create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone
        ]);

        return response()->json([
            'success' => true,
            'message' => 'kontak berhasil dibuat',
            'data' => $contact
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        DB::table($user->company->prefix . "_contacts")->where('id', $id)->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone
        ]);

        return response()->json([
            'success' => true,
            'message' => 'kotak berhasil diubah',
        ]);
    }
}
