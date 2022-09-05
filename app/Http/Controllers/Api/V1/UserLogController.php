<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $activity = $request->activity;
        $startAt = $request->start_at;
        $endAt = $request->end_at;

        if ($activity && $startAt && $endAt) {
            $userLogs = DB::table($user->company->prefix . "_user_logs")->where('user_id', $user->id)->where('activity', $activity)->where('at', '>=', $startAt)->where('at', '<=', $endAt)->orderBy('at', 'DESC')->get();
        } else if ($startAt && $endAt) {
            $userLogs = DB::table($user->company->prefix . "_user_logs")->where('user_id', $user->id)->where('at', '>=', $startAt)->where('at', '<=', $endAt)->orderBy('at', 'DESC')->get();
        } else if ($activity) {
            $userLogs = DB::table($user->company->prefix . "_user_logs")->where('user_id', $user->id)->where('activity', $activity)->orderBy('at', 'DESC')->get();
        } else {
            $userLogs = DB::table($user->company->prefix . "_user_logs")->where('user_id', $user->id)->orderBy('at', 'DESC')->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'data user_logs',
            'data' => $userLogs
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'device' => 'required',
            'version' => 'required',
            'activity' => 'required',
            'user_id' => 'required',
            'user_data' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $userLog = new UserLog();
        $userLog->prefixTableName($user->company->prefix);
        $userLog->create([
            'at' => Carbon::now(),
            'device' => $request->device,
            'version' => $request->version,
            'activity' => $request->activity,
            'user_id' => $request->user_id,
            'user_data' => $request->user_data
        ]);

        return response()->json([
            'success' => true,
            'message' => 'user log berhasil dibuat',
            'data' => $userLog
        ]);
    }
}
