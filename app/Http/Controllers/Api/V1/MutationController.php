<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\MutationExport;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class MutationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->warehouse;
        $type = $request->type;
        $startAt = $request->start_at;
        $endAt = $request->end_at;

        if ($warehouseId && $type && $startAt && $endAt) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->where('at', '>=', $startAt)->where('at', '<=', $endAt)->get();
        } else if ($warehouseId && $type && $startAt) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->where('at', '>=', $startAt)->get();
        } else if ($warehouseId && $type) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->get();
        } else if ($warehouseId) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->get();
        } else {
            $mutations = DB::table($user->company->prefix . "_mutations")->get();
        }

        foreach ($mutations as $mutation) {
            $fromData = json_decode($mutation->from_data);
            $mutation->from_data = $fromData;
            $toData = json_decode($mutation->to_data);
            $mutation->to_data = $toData;
            $whData = json_decode($mutation->warehouse_data);
            $mutation->warehouse_data = $whData;
        }

        return response()->json([
            'success' => true,
            'message' => 'data mutasi',
            'data' => $mutations
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $mutationDatas = DB::table($user->company->prefix . "_mutation_datas")->where('mutation_id', $id)->get();

        foreach ($mutationDatas as $mutationData) {
            $epcList = json_decode($mutationData->epc_list);
            $mutationData->epc_list = $epcList;
            $itemData = json_decode($mutationData->item_data);
            $mutationData->item_data = $itemData;
            $mutationData->item_data->product = DB::table($user->company->prefix . "_products")->select('name', 'image')->where('id', $mutationData->item_data->product_id)->first();
            $mutationData->item_data->product->image = url(Storage::url('uploads/products/' . $mutationData->item_data->product->image));
            $mutationData->qty = count($epcList);
        }

        return response()->json([
            'success' => true,
            'message' => 'mutasi data',
            'data' => $mutationDatas
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'from' => 'required',
            'to' => 'required',
            'warehouse_id' => 'required',
            'from_data' => 'required',
            'to_data' => 'required',
            'warehouse_data' => 'required',
            'data' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $warehouse = Warehouse::where('id', $request->warehouse_id)->first();

        $mutation = DB::table($user->company->prefix . "_mutations")->insertGetId([
            'type' => $request->type,
            'receipt_number' => "",
            'at' => Carbon::now(),
            'from' => $request->from,
            'to' => $request->to,
            'warehouse_id' => $request->warehouse_id,
            'from_data' => json_encode($request->from_data),
            'to_data' => json_encode($request->to_data),
            'warehouse_data' => json_encode($request->warehouse_data),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        if ($mutation) {
            $mutationNumber = $this->mutationNumbering(
                $user->company->prefix,
                $request->type,
                $request->warehouse_id
            );

            DB::table($user->company->prefix . "_mutations")->where('id', $mutation)->update([
                'receipt_number' => $mutationNumber
            ]);

            foreach ($request->data as $data) {
                DB::table($user->company->prefix . "_mutation_datas")->insert([
                    'mutation_id' => $mutation,
                    'item_id' => $data['item_id'],
                    'item_name' => $data['item_name'],
                    'item_data' => json_encode($data['item_data']),
                    'epc_list' => json_encode($data['epc_list'])
                ]);

                $instock = $request->type === "inbound";

                foreach ($data['epc_list'] as $epc) {
                    DB::table($user->company->prefix . "_items")->where('epc', $epc)->update(['in_stock' => $instock]);

                    if ($instock) {
                        // epc_log
                        DB::table($user->company->prefix . '_epc_logs')->insert([
                            'at' => Carbon::now(),
                            'epc' => $epc,
                            'note' => 'inbound in ' . $warehouse->name . ' from ' . $request->from_data['name'],
                            'activity' => 'inbound',
                            'user_id' => $user->id,
                            'user_data' => json_encode($user)
                        ]);
                        // epc_log
                    } else {
                        // epc_log
                        DB::table($user->company->prefix . '_epc_logs')->insert([
                            'at' => Carbon::now(),
                            'epc' => $epc,
                            'note' => 'outbound in ' . $warehouse->name . ' to ' . $request->to_data['name'],
                            'activity' => 'outbound',
                            'user_id' => $user->id,
                            'user_data' => json_encode($user)
                        ]);
                        // epc_log
                    }
                }
            }
        }

        $newMutation = DB::table($user->company->prefix . "_mutations")->where('receipt_number', $mutationNumber)->first();

        if ($newMutation) {
            return response()->json([
                'success' => true,
                'message' => $request->type . ' berhasil dibuat NO: ' . $mutationNumber,
                'data' => $newMutation
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "gagal membuat inbound",
                'data' => null
            ]);
        }
    }

    public function printPdf(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->warehouse;
        $type = $request->type;
        $startAt = $request->start_at;
        $endAt = $request->end_at;

        if ($warehouseId && $type && $startAt && $endAt) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->where('at', '>=', $startAt)->where('at', '<=', $endAt)->get();
        } else if ($warehouseId && $type && $startAt) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->where('at', '>=', $startAt)->get();
        } else if ($warehouseId && $type) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->get();
        } else if ($warehouseId) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->get();
        } else {
            $mutations = DB::table($user->company->prefix . "_mutations")->get();
        }

        foreach ($mutations as $mutation) {
            $fromData = json_decode($mutation->from_data);
            $mutation->from_data = $fromData;
            $toData = json_decode($mutation->to_data);
            $mutation->to_data = $toData;
            $whData = json_decode($mutation->warehouse_data);
            $mutation->warehouse_data = $whData;

            $mutationDatas = DB::table($user->company->prefix . "_mutation_datas")->where('mutation_id', $mutation->id)->get();
            foreach ($mutationDatas as $mutationData) {
                $mutationData->mutation = DB::table($user->company->prefix . "_mutations")->select('receipt_number')->where('id', $mutationData->mutation_id)->first();
                $epcList = json_decode($mutationData->epc_list);
                $mutationData->epc_list = $epcList;
                $itemData = json_decode($mutationData->item_data);
                $mutationData->item_data = $itemData;
                $mutationData->item_data->product = DB::table($user->company->prefix . "_products")->select('name')->where('id', $mutationData->item_data->product_id)->first();
                $mutationData->qty = count($epcList);
            }

            $mutation->mutation_datas = $mutationDatas;
        }

        $pdf = PDF::loadview('pdf_mutation', ['mutations' => $mutations]);
        return $pdf->download('mutations.pdf');
    }

    public function printExcel(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->warehouse;
        $type = $request->type;
        $startAt = $request->start_at;
        $endAt = $request->end_at;

        if ($warehouseId && $type && $startAt && $endAt) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->where('at', '>=', $startAt)->where('at', '<=', $endAt)->get();
        } else if ($warehouseId && $type && $startAt) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->where('at', '>=', $startAt)->get();
        } else if ($warehouseId && $type) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->where('type', $type)->get();
        } else if ($warehouseId) {
            $mutations = DB::table($user->company->prefix . "_mutations")->where('warehouse_id', $warehouseId)->get();
        } else {
            $mutations = DB::table($user->company->prefix . "_mutations")->get();
        }

        foreach ($mutations as $mutation) {
            $fromData = json_decode($mutation->from_data);
            $mutation->from_data = $fromData;
            $toData = json_decode($mutation->to_data);
            $mutation->to_data = $toData;
            $whData = json_decode($mutation->warehouse_data);
            $mutation->warehouse_data = $whData;

            $mutationDatas = DB::table($user->company->prefix . "_mutation_datas")->where('mutation_id', $mutation->id)->get();
            foreach ($mutationDatas as $mutationData) {
                $mutationData->mutation = DB::table($user->company->prefix . "_mutations")->select('receipt_number')->where('id', $mutationData->mutation_id)->first();
                $epcList = json_decode($mutationData->epc_list);
                $mutationData->epc_list = $epcList;
                $itemData = json_decode($mutationData->item_data);
                $mutationData->item_data = $itemData;
                $mutationData->item_data->product = DB::table($user->company->prefix . "_products")->select('name')->where('id', $mutationData->item_data->product_id)->first();
                $mutationData->qty = count($epcList);
            }
            $mutation->mutation_datas = $mutationDatas;
        }

        return Excel::download(new MutationExport($mutations), 'mutation.xlsx');
    }

    function mutationNumbering($prefix, $type, $warehouseId)
    {
        $mutations = DB::table($prefix . "_mutations")
            ->where('warehouse_id', $warehouseId)->where('type', $type)
            ->whereYear('created_at', '=', date("Y"))
            ->whereMonth('created_at', '=', date("m"))->get();

        if ($type == "inbound") {
            $numberingType = "IN";
        } else {
            $numberingType = "OT";
        }

        $serialize = 1000 + count($mutations);
        $year = date("ym");
        $suffix = preg_replace("/^1/", "0", $serialize);
        $number = $numberingType . $warehouseId . "." . $year . "." . $suffix;
        return $number;
    }
}
