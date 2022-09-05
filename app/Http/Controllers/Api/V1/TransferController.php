<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\TransferExport;
use App\Http\Controllers\Controller;
use PDF;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $originId = $request->origin;
        $destinationId = $request->destination;

        if ($originId && $destinationId) {
            $transfers = DB::table($user->company->prefix . "_transfers")->where('origin', $originId)->where('destination', $destinationId)->get();
        } else if ($originId) {
            $transfers = DB::table($user->company->prefix . "_transfers")->where('origin', $originId)->get();
        } else {
            $transfers = DB::table($user->company->prefix . "_transfers")->get();
        }

        foreach ($transfers as $transfer) {
            $originData = json_decode($transfer->origin_data);
            $transfer->origin_data = $originData;
            $destinationData = json_decode($transfer->destination_data);
            $transfer->destination_data = $destinationData;
            $fromData = json_decode($transfer->from_data);
            $transfer->from_data = $fromData;
            $toData = json_decode($transfer->to_data);
            $transfer->to_data = $toData;
        }

        return response()->json([
            'success' => true,
            'message' => 'data transfers',
            'data' => $transfers
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $transferDatas = DB::table($user->company->prefix . "_transfer_datas")->where('transfer_id', $id)->get();

        foreach ($transferDatas as $transferData) {
            $epcList = json_decode($transferData->epc_list);
            $transferData->epc_list = $epcList;
            $itemData = json_decode($transferData->item_data);
            $transferData->item_data = $itemData;
            $transferData->item_data->product = DB::table($user->company->prefix . "_products")->select('name', 'image')->where('id', $transferData->item_data->product_id)->first();
            $transferData->item_data->product->image = url(Storage::url('uploads/products/' . $transferData->item_data->product->image));
            $transferData->qty = count($epcList);
        }

        return response()->json([
            'success' => true,
            'message' => 'transfer data',
            'data' => $transferDatas
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'origin' => 'required',
            'destination' => 'required',
            'from' => 'required',
            'origin_data' => 'required',
            'destination_data' => 'required',
            'from_data' => 'required',
            'data' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        $transfer = DB::table($user->company->prefix . "_transfers")->insertGetId([
            'receipt_number' => "",
            'transfer_at' => Carbon::now(),
            'received_at' => null,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'from' => $request->from,
            'to' => null,
            'origin_data' => json_encode($request->origin_data),
            'destination_data' => json_encode($request->destination_data),
            'from_data' => json_encode($request->from_data),
            'to_data' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        if ($transfer) {
            $transferNumber = $this->transferNumbering(
                $user->company->prefix,
                $request->origin
            );

            DB::table($user->company->prefix . "_transfers")->where('id', $transfer)->update([
                'receipt_number' => $transferNumber
            ]);

            foreach ($request->data as $data) {
                DB::table($user->company->prefix . "_transfer_datas")->insert([
                    'transfer_id' => $transfer,
                    'item_id' => $data['item_id'],
                    'item_name' => $data['item_name'],
                    'item_data' => json_encode($data['item_data']),
                    'epc_list' => json_encode($data['epc_list'])
                ]);


                $newLocation = DB::table($user->company->prefix . "_locations")
                    ->where('name', 'default')->where('warehouse_id', $request->destination)->first();

                foreach ($data['epc_list'] as $epc) {


                    DB::table($user->company->prefix . "_items")->where('epc', $epc)->update([
                        'in_stock' => false,
                        'on_transfer' => true,
                        'warehouse_id' => $request->destination,
                        'location_id' => $newLocation->id,
                        'path' => $newLocation->path
                    ]);

                    DB::table($user->company->prefix . '_epc_logs')->insert([
                        'at' => Carbon::now(),
                        'epc' => $epc,
                        'note' => 'transfer from ' . $request->origin_data['name'] . ' to ' . $request->destination_data['name'] . ' by ' . $request->from_data['name'],
                        'activity' => 'transfer',
                        'user_id' => $request->from,
                        'user_data' => json_encode($request->from_data),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }
        }

        $newTransfer = DB::table($user->company->prefix . "_transfers")->where('receipt_number', $transferNumber)->first();

        if ($newTransfer) {
            return response()->json([
                'success' => true,
                'message' => 'Transfer success NO: ' . $transferNumber,
                'data' => $newTransfer
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Failed",
                'data' => null
            ]);
        }
    }

    public function printPdf(Request $request)
    {
        $user = $request->user();

        $originId = $request->origin;
        $destinationId = $request->destination;

        if ($originId && $destinationId) {
            $transfers = DB::table($user->company->prefix . "_transfers")->where('origin', $originId)->where('destination', $destinationId)->get();
        } else if ($originId) {
            $transfers = DB::table($user->company->prefix . "_transfers")->where('origin', $originId)->get();
        } else {
            $transfers = DB::table($user->company->prefix . "_transfers")->get();
        }

        foreach ($transfers as $transfer) {
            $originData = json_decode($transfer->origin_data);
            $transfer->origin_data = $originData;
            $destinationData = json_decode($transfer->destination_data);
            $transfer->destination_data = $destinationData;
            $fromData = json_decode($transfer->from_data);
            $transfer->from_data = $fromData;
            $toData = json_decode($transfer->to_data);
            $transfer->to_data = $toData;

            $transferDatas = DB::table($user->company->prefix . "_transfer_datas")->where('transfer_id', $transfer->id)->get();
            foreach ($transferDatas as $transferData) {
                $transferData->transfer = DB::table($user->company->prefix . "_transfers")->select('receipt_number')->where('id', $transferData->transfer_id)->first();
                $epcList = json_decode($transferData->epc_list);
                $transferData->epc_list = $epcList;
                $itemData = json_decode($transferData->item_data);
                $transferData->item_data = $itemData;
                $transferData->item_data->product = DB::table($user->company->prefix . "_products")->select('name')->where('id', $transferData->item_data->product_id)->first();
                $transferData->qty = count($epcList);
            }
            $transfer->transfer_datas = $transferDatas;
        }

        $pdf = PDF::loadview('pdf_transfer', ['transfers' => $transfers]);
        return $pdf->download('transfers.pdf');
    }

    public function printExcel(Request $request)
    {
        $user = $request->user();

        $originId = $request->origin;
        $destinationId = $request->destination;

        if ($originId && $destinationId) {
            $transfers = DB::table('id1' . "_transfers")->where('origin', $originId)->where('destination', $destinationId)->get();
        } else if ($originId) {
            $transfers = DB::table('id1' . "_transfers")->where('origin', $originId)->get();
        } else {
            $transfers = DB::table('id1' . "_transfers")->get();
        }

        foreach ($transfers as $transfer) {
            $originData = json_decode($transfer->origin_data);
            $transfer->origin_data = $originData;
            $destinationData = json_decode($transfer->destination_data);
            $transfer->destination_data = $destinationData;
            $fromData = json_decode($transfer->from_data);
            $transfer->from_data = $fromData;
            $toData = json_decode($transfer->to_data);
            $transfer->to_data = $toData;

            $transferDatas = DB::table($user->company->prefix . "_transfer_datas")->where('transfer_id', $transfer->id)->get();
            foreach ($transferDatas as $transferData) {
                $transferData->transfer = DB::table($user->company->prefix . "_transfers")->select('receipt_number')->where('id', $transferData->transfer_id)->first();
                $epcList = json_decode($transferData->epc_list);
                $transferData->epc_list = $epcList;
                $itemData = json_decode($transferData->item_data);
                $transferData->item_data = $itemData;
                $transferData->item_data->product = DB::table($user->company->prefix . "_products")->select('name')->where('id', $transferData->item_data->product_id)->first();
                $transferData->qty = count($epcList);
            }
            $transfer->transfer_datas = $transferDatas;
        }

        return Excel::download(new TransferExport($transfers), 'transfer.xlsx');
    }

    //for receiving transfer
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'to' => 'required',
            'to_data' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'data' => $validator->errors()
            ], 400);
        }


        $update = DB::table($user->company->prefix . '_transfers')->where('id', $id)->update([
            'to' => $request->to,
            'to_data' => json_encode($request->to_data),
            'received_at' => Carbon::now()
        ]);

        if ($update) {
            $datas = DB::table($user->company->prefix . "_transfer_datas")->where('transfer_id', $id)
                ->get();

            foreach ($datas as $data) {
                $epc_list = json_decode($data->epc_list);
                $data->epc_list = $epc_list;

                foreach ($epc_list as $epc) {
                    DB::table($user->company->prefix . "_items")->where('epc', $epc)->update([
                        'in_stock' => true,
                        'on_transfer' => false
                    ]);

                    DB::table($user->company->prefix . '_epc_logs')->insert([
                        'at' => Carbon::now(),
                        'epc' => $epc,
                        'note' => 'transfer successfully received by ' . $request->to_data['name'],
                        'activity' => 'transfer',
                        'user_id' => $request->to_data['id'],
                        'user_data' => json_encode($request->to_data),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }


            $data = DB::table($user->company->prefix . '_transfers')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => "ok",
                'data' => $data
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Update failed",
                'data' => null
            ], 400);
        }
    }

    function transferNumbering($prefix, $warehouseId)
    {
        $transfers = DB::table($prefix . "_transfers")
            ->where('origin', $warehouseId)
            ->whereYear('created_at', '=', date("Y"))
            ->whereMonth('created_at', '=', date("m"))->get();

        $numberingType = "TF";
        $serialize = 1000 + count($transfers);
        $year = date("ym");
        $suffix = preg_replace("/^1/", "0", $serialize);
        $number = $numberingType . $warehouseId . "." . $year . "." . $suffix;
        return $number;
    }

    public function waitingTransfer(Request $request, $warehouseId)
    {
        $user = $request->user();
        $transfer = DB::table($user->company->prefix . "_transfers")->where('destination', $warehouseId)
            ->whereNull('to')->get();

        $data = [
            count($transfer)
        ];

        return response()->json([
            'success' => true,
            'message' => "ok",
            'data' => $data
        ]);
    }

    public function pendingTransfer(Request $request, $warehouseId)
    {
        $user = $request->user();
        $transfers = DB::table($user->company->prefix . "_transfers")->where('destination', $warehouseId)
            ->whereNull('to')->get();

        foreach ($transfers as $trf) {
            $originData = json_decode($trf->origin_data);
            $destinationData = json_decode($trf->destination_data);
            $fromData = json_decode($trf->from_data);

            $trf->origin_data = $originData;
            $trf->destination_data = $destinationData;
            $trf->from_data = $fromData;
        }

        return response()->json([
            'success' => true,
            'message' => "ok",
            'data' => $transfers
        ]);
    }

    public function getById(Request $request, $id)
    {
        $user = $request->user();

        $transfer = DB::table($user->company->prefix . "_transfers")->where('id', $id)
            ->first();

        if ($transfer) {
            $originData = json_decode($transfer->origin_data);
            $transfer->origin_data = $originData;
            $destinationData = json_decode($transfer->destination_data);
            $transfer->destination_data = $destinationData;
            $fromData = json_decode($transfer->from_data);
            $transfer->from_data = $fromData;

            $datas = DB::table($user->company->prefix . "_transfer_datas")->where('transfer_id', $id)
                ->get();

            foreach ($datas as $data) {
                $epc_list = json_decode($data->epc_list);
                $data->epc_list = $epc_list;
                $itemData = json_decode($data->item_data);
                $data->item_data = $itemData;
            }

            $transfer->transfer_data = $datas;

            return response()->json([
                'success' => true,
                'message' => "Transfer details",
                'data' => $transfer
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Transfer data not found.",
                'data' => null
            ]);
        }
    }
}
