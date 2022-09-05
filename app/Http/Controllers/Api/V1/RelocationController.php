<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RelocationController extends Controller
{
    public function index(Request $request)
    {
        echo "ok";
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'epc_list' => 'required',
            'note_list' => 'required',
            'ref' => 'required',
            'location_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $listEpc = $request->epc_list;
        $listNote = $request->note_list;

        $location = DB::table($user->company->prefix . "_locations")->where('id', $request->location_id)->first();
        foreach ($listEpc as $i => $epc) {

            DB::table($user->company->prefix . '_items')->where('epc', $epc)->update([
                'location_id' => $location->id,
                'path' => $location->path
            ]);


            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company_id' => $user->company_id
            ];

            // epc_log
            DB::table($user->company->prefix . '_epc_logs')->insert([
                'at' => Carbon::now(),
                'epc' => $epc,
                'note' => $listNote[$i],
                'ref' => $request->ref,
                'activity' => 'relocation',
                'user_id' => $user->id,
                'user_data' => json_encode($userData),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            // epc_log
        }

        return response()->json([
            'success' => true,
            'message' => 'relokasi berhasil',
            'data' => null
        ]);
    }
}
