<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $warehouses = Warehouse::select('id', 'name')->where('company_id', $user->company->id)->get();

        $resultInStock = [];
        foreach ($warehouses as $warehouse) {
            $items = DB::table($user->company->prefix . "_items")->where('warehouse_id', $warehouse->id)->where('in_stock', true)->count();
            $data = [
                "warehouse" => $warehouse->name,
                "qty" => $items
            ];
            $resultInStock[] = $data;
        }

        $resultOnTransfer = [];
        foreach ($warehouses as $warehouse) {
            $items = DB::table($user->company->prefix . "_items")->where('warehouse_id', $warehouse->id)->where('on_transfer', true)->count();
            $data = [
                "warehouse" => $warehouse->name,
                "qty" => $items
            ];
            $resultOnTransfer[] = $data;
        }

        $products = DB::table($user->company->prefix . "_products")->select('id', 'name')->get();

        foreach ($products as $product) {
            $itemsInStock = DB::table($user->company->prefix . "_items")->where('product_id', $product->id)->where('in_stock', true)->get();
            $product->qty_item_in_stock = count($itemsInStock);
            $itemsOnTransfer = DB::table($user->company->prefix . "_items")->where('product_id', $product->id)->where('on_transfer', true)->get();
            $product->qty_item_on_transfer = count($itemsOnTransfer);
        }

        $qtyStock = DB::table($user->company->prefix . '_items')->select('in_stock')->where('in_stock', true)->count();

        $inbounds = DB::table($user->company->prefix . "_mutations")->where('type', 'inbound')->where('at', '>=',  date('Y-m-d'))->where('at', '<=', date('Y-m-d', strtotime("+1 day")))->get();

        $qtyInbound = 0;
        foreach ($inbounds as $inbound) {
            $inboundDatas = DB::table($user->company->prefix . "_mutation_datas")->where('mutation_id', $inbound->id)->get();
            $qtyInboundData = 0;
            foreach ($inboundDatas as $inboundData) {
                $qtyInboundData += count(json_decode($inboundData->epc_list));
            }
            $qtyInbound += $qtyInboundData;
        }

        $transfers = DB::table($user->company->prefix . "_transfers")->where('transfer_at', '>=',  date('Y-m-d'))->where('transfer_at', '<=', date('Y-m-d', strtotime("+1 day")))->get();

        $qtyTransfer = 0;
        foreach ($transfers as $transfer) {
            $transferDatas = DB::table($user->company->prefix . "_transfer_datas")->where('transfer_id', $transfer->id)->get();
            $qtyTransferData = 0;
            foreach ($transferDatas as $transferData) {
                $qtyTransferData += count(json_decode($transferData->epc_list));
            }
            $qtyTransfer += $qtyTransferData;
        }

        $outbounds = DB::table($user->company->prefix . "_mutations")->where('type', 'outbound')->where('at', '>=',  date('Y-m-d'))->where('at', '<=', date('Y-m-d', strtotime("+1 day")))->get();

        $qtyOutbound = 0;
        foreach ($outbounds as $outbound) {
            $outboundDatas = DB::table($user->company->prefix . "_mutation_datas")->where('mutation_id', $outbound->id)->get();
            $qtyOutboundData = 0;
            foreach ($outboundDatas as $outboundData) {
                $qtyOutboundData += count(json_decode($outboundData->epc_list));
            }
            $qtyOutbound += $qtyOutboundData;
        }

        $result = new stdClass();
        $result->in_stock = $resultInStock;
        $result->on_transfer = $resultOnTransfer;
        $result->products = $products;
        $result->qty_stock = $qtyStock;
        $result->qty_inbound = $qtyInbound;
        $result->qty_transfer = $qtyTransfer;
        $result->qty_outbound = $qtyOutbound;

        return response()->json([
            'success' => true,
            'message' => 'dashboard data',
            'data' => $result
        ]);
    }
}
