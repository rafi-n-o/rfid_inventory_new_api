<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use stdClass;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'company_name' => 'required',
            'company_address' => 'required',
            'company_phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'role' => 'owner',
            'password' => Hash::make($request->password)
        ]);

        if ($user) {
            $company = Company::create([
                'name' => $request->company_name,
                'address' => $request->company_address,
                'phone' => $request->company_phone
            ]);

            if ($company->id) {
                $prefix = strtolower(substr($request->company_name, -2) . $company->id);

                $company->update([
                    'prefix' => $prefix
                ]);

                $user->update([
                    'company_id' => $company->id
                ]);

                Schema::create($prefix . "_locations", function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->foreignId('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
                    $table->char('path');
                    $table->timestamps();
                });

                Schema::create($prefix . "_contacts", function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('address');
                    $table->char('phone');
                    $table->timestamps();
                });


                Schema::create($prefix . "_categories", function (Blueprint $table) {
                    $table->id();
                    $table->string('name')->unique();
                    $table->timestamps();
                });

                Schema::create($prefix . "_attributes", function (Blueprint $table) {
                    $table->id();
                    $table->string('name')->unique();
                    $table->enum('type', ['text', 'date', 'number', 'list']);
                    $table->text('list')->nullable();
                    $table->timestamps();
                });

                Schema::create($prefix . "_products", function (Blueprint $table) use ($prefix) {
                    $table->id();
                    $table->string('name');
                    $table->string('image')->nullable();
                    $table->foreignId('category_id')->references('id')->on($prefix . "_categories")->onDelete('cascade');
                    $table->foreignId('attribute1_id')->nullable()->references('id')->on($prefix . "_attributes")->onDelete('cascade');
                    $table->foreignId('attribute2_id')->nullable()->references('id')->on($prefix . "_attributes")->onDelete('cascade');
                    $table->foreignId('attribute3_id')->nullable()->references('id')->on($prefix . "_attributes")->onDelete('cascade');
                    $table->timestamps();
                });

                Schema::create($prefix . "_items", function (Blueprint $table) use ($prefix) {
                    $table->id();
                    $table->string('epc')->unique();
                    $table->boolean('in_stock');
                    $table->boolean('on_transfer');
                    $table->string('attribute1_value')->nullable();
                    $table->string('attribute2_value')->nullable();
                    $table->string('attribute3_value')->nullable();
                    $table->foreignId('product_id')->references('id')->on($prefix . "_products")->onDelete('cascade');
                    $table->foreignId('location_id')->nullable()->references('id')->on($prefix . "_locations")->onDelete('cascade');
                    $table->foreignId('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
                    $table->char('path');
                    $table->timestamps();
                });

                Schema::create($prefix . "_mutations", function (Blueprint $table) {
                    $table->id();
                    $table->dateTime('at');
                    $table->enum('type', ['inbound', 'outbound']);
                    $table->char('receipt_number')->nullable();
                    $table->integer('from');
                    $table->integer('to');
                    $table->foreignId('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
                    $table->text('from_data');
                    $table->text('to_data');
                    $table->text('warehouse_data');
                    $table->timestamps();
                });

                Schema::create($prefix . "_mutation_datas", function (Blueprint $table) use ($prefix) {
                    $table->id();
                    $table->foreignId('mutation_id')->references('id')->on($prefix . "_mutations")->onDelete('cascade');
                    $table->text('epc_list');
                    $table->integer('item_id');
                    $table->string('item_name');
                    $table->text('item_data');
                    $table->timestamps();
                });

                Schema::create($prefix . "_transfers", function (Blueprint $table) {
                    $table->id();
                    $table->char('receipt_number');
                    $table->dateTime('transfer_at');
                    $table->dateTime('received_at')->nullable();
                    $table->integer('origin');
                    $table->integer('destination');
                    $table->integer('from');
                    $table->integer('to')->nullable();
                    $table->text('origin_data');
                    $table->text('destination_data');
                    $table->text('from_data');
                    $table->text('to_data')->nullable();
                    $table->timestamps();
                });

                Schema::create($prefix . "_transfer_datas", function (Blueprint $table) use ($prefix) {
                    $table->id();
                    $table->foreignId('transfer_id')->references('id')->on($prefix . "_transfers")->onDelete('cascade');
                    $table->text('epc_list');
                    $table->integer('item_id');
                    $table->string('item_name');
                    $table->text('item_data');
                    $table->timestamps();
                });

                Schema::create($prefix . "_epc_logs", function (Blueprint $table) {
                    $table->id();
                    $table->dateTime('at');
                    $table->string('epc');
                    $table->text('note');
                    $table->string('ref');
                    $table->enum('activity', ['tag-registration', 'inbound', 'relocation', 'transfer', 'outbound', 'disposal']);
                    $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->text('user_data');
                    $table->timestamps();
                });

                Schema::create($prefix . "_user_logs", function (Blueprint $table) {
                    $table->id();
                    $table->dateTime('at');
                    $table->string('device');
                    $table->string('version');
                    $table->enum('activity', ['register', 'login', 'tag-registration', 'inbound', 'relocation', 'transfer', 'outbound', 'disposal']);
                    $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->text('user_data');
                    $table->timestamps();
                });
            }

            $result = new stdClass();
            $result->user = $user;
            $result->company = $company;

            return response()->json([
                'success' => true,
                'message' => 'registrasi berhasil',
                'data' => $result
            ]);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'email atau password salah'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        $token = $user->createToken($user->id)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'login berhasil',
            'data' => $token
        ]);
    }

    public function uploadImageUser(Request $request)
    {
        $user = $request->user();

        $dataUser = User::where('id', $user->id)->first();

        $validator = Validator::make($request->all(), [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $image_64 = $request->image;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $imageName = Str::random(10) . '.' . $extension;
        $imagePath = '/uploads/users/' . $imageName;
        Storage::disk('public')->put($imagePath, base64_decode($image));

        $dataUser->update([
            'image' => $imageName
        ]);

        return response()->json([
            'success' => true,
            'message' => 'image user berhasil diupload',
            'data' => null
        ]);
    }

    public function uploadImageCompany(Request $request)
    {
        $user = $request->user();

        $company = Company::where('id', $user->company->id)->first();

        $validator = Validator::make($request->all(), [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $image_64 = $request->image;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $imageName = Str::random(10) . '.' . $extension;
        $imagePath = '/uploads/companies/' . $imageName;
        Storage::disk('public')->put($imagePath, base64_decode($image));

        $company->update([
            'image' => $imageName
        ]);

        return response()->json([
            'success' => true,
            'message' => 'image company berhasil diupload',
            'data' => null
        ]);
    }

    public function getAll(Request $request)
    {
        $users = User::where('name', 'like', '%' . $request->search . '%')->where('email', 'like', '%' . $request->search . '%')->get();

        return response()->json([
            'success' => true,
            'message' => 'data users',
            'data' => $users
        ]);
    }
}
