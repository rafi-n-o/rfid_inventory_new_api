<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EpcLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $epcLogs = DB::table($user->company->prefix . "_epc_logs")->where('epc', $request->epc)->get();

        foreach ($epcLogs as $epcLog) {
            $epcLog->user_data = json_decode($epcLog->user_data);
        }

        if (!$request->epc || count($epcLogs) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'data not found',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'data epc_logs',
            'data' => $epcLogs
        ]);
    }

    public function getByEpc(Request $request, $epc)
    {
        $user = $request->user();

        $epcLogs = DB::table($user->company->prefix . "_epc_logs")
            ->select('at', 'epc', 'note', 'ref', 'activity', 'user_data')->where('epc', $epc)
            ->orderBy('at', 'desc')->get();

        foreach ($epcLogs as $epcLog) {
            $userData = json_decode($epcLog->user_data);
            $user = [
                'id' => $userData->id,
                'name' => $userData->name,
                'email' => $userData->email
            ];
            $epcLog->user_data = $user;
        }

        if (count($epcLogs) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'data not found',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'data epc_logs',
            'data' => $epcLogs
        ]);
    }
}
