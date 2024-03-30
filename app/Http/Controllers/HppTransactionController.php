<?php

namespace App\Http\Controllers;

use App\Models\HppTransaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HppTransactionController extends Controller
{
    public function index(){
        HppTransactionController::recalculate(1);
        $data = HppTransaction::all();
        if (!$data) {
            return response()->json(['status' => 'error', 'messages' => 'Data tidak ditemukan'], 422);
        }

        return response()->json(['status' => 'success', 'messages' => 'Data berhasil didapat!', 'data' => $data], 200);
    }

    public function store(Request $request){
        try{
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'description' => 'required|in:Pembelian,Penjualan',
                'date' => 'required|date',
                'qty' => 'required|integer',
                'price' => 'required|numeric|min:0.01',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'messages' => $validator->errors()], 422);
            }
            $lastTransaction = HppTransaction::latest()->first();
            $qtyBalance = $lastTransaction ? $lastTransaction->qty_balance : 0;
            $valueBalance = $lastTransaction ? $lastTransaction->value_balance : 0;

            if ($request->description == 'Penjualan') {
                $costPrice = $lastTransaction ? $lastTransaction->hpp : 0;
            } else {
                $costPrice = $request->cost_price;
            }
            $qty = $request->description == 'Pembelian' ? $request->qty : -$request->qty;
            $totalCost = $qty * $costPrice;
            $qtyBalance += $qty;
            $valueBalance += $totalCost;

            if ($qtyBalance > 0) {
                $hpp = $valueBalance / $qtyBalance;
            } else {
                $hpp = 0;
                DB::rollback();

                return response()->json(['status' => 'error', 'messages' => 'Stok Kurang dari 0'], 500);
            }

            $hppTrans = New HppTransaction();
            $hppTrans->description = $request->description;
            $hppTrans->date = $request->date;
            $hppTrans->qty = $qty;
            $hppTrans->price = $request->price;
            $hppTrans->cost = $costPrice;
            $hppTrans->total_cost = $totalCost;
            $hppTrans->qty_balance = $qtyBalance;
            $hppTrans->value_balance = $valueBalance;
            $hppTrans->hpp = $hpp;

            $hppTrans->save();

            DB::commit();

            return response()->json(['status' => 'success', 'messages' => 'Data berhasil ditambah!', 'data' => $hppTrans], 200);
        } catch (\Exception  $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id){
        try{
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'qty' => 'required|integer',
                'price' => 'required|numeric|min:0.01',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'messages' => $validator->errors()], 422);
            }

            $hppTrans = HppTransaction::findOrFail($id);

            $lastTransaction = HppTransaction::where('id', '<', $hppTrans->id)->latest()->first();
            $qtyBalance = $lastTransaction ? $lastTransaction->qty_balance : 0;
            $valueBalance = $lastTransaction ? $lastTransaction->value_balance : 0;

            if ($hppTrans->description == 'Penjualan') {
                $costPrice = $lastTransaction ? $lastTransaction->hpp : 0;
            } else {
                $costPrice = $request->price;
            }
            $qty = $request->description == 'Pembelian' ? $request->qty : -$request->qty;

            $qtyChange = $hppTrans->qty - $qty;
            $qtyBalance -= $qtyChange;
            $totalCost = $qty * $costPrice;
            $valueBalance += ($qty * $costPrice) - ($qtyChange * $costPrice);

            if ($qtyBalance > 0) {
                $hpp = $valueBalance / $qtyBalance;
            } else {
                $hpp = 0;
                DB::rollback();

                return response()->json(['status' => 'error', 'messages' => 'Stok Kurang dari 0'], 500);
            }

            $hppTrans->qty = $qty;
            $hppTrans->price = $request->price;
            $hppTrans->cost = $costPrice;
            $hppTrans->total_cost = $totalCost;
            $hppTrans->qty_balance = $qtyBalance;
            $hppTrans->value_balance = $valueBalance;
            $hppTrans->hpp = $hpp;

            $hppTrans->save();

            DB::commit();

            return response()->json(['status' => 'success', 'messages' => 'Data berhasil diupdate!', 'data' => $hppTrans], 200);
        } catch (\Exception  $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }

    public function delete($id){
        try{
            DB::beginTransaction();

            $hppTrans = HppTransaction::findOrFail($id);

            $lastTransaction = HppTransaction::where('id', '<', $hppTrans->id)->latest()->first();
            $qtyBalance = $lastTransaction ? $lastTransaction->qty_balance : 0;
            $valueBalance = $lastTransaction ? $lastTransaction->value_balance : 0;

            if ($hppTrans->description == 'Pembelian') {
                $qtyBalance -= $hppTrans->qty;
                $valueBalance -= $hppTrans->total_cost;
            } else {
                $qtyBalance += $hppTrans->qty;
                $valueBalance += $hppTrans->total_cost;
            }

            if ($qtyBalance > 0) {
                $hpp = $valueBalance / $qtyBalance;
            } else {
                $hpp = 0;
                DB::rollback();

                return response()->json(['status' => 'error', 'messages' => 'Stok Kurang dari 0'], 500);
            }


            $transactions = HppTransaction::where('id', '>', $hppTrans->id)->get();
            $hppTrans->delete();

            foreach ($transactions as $transaction) {
                $transaction->qty_balance = $qtyBalance;
                $transaction->value_balance = $valueBalance;
                $transaction->hpp = $hpp;
                $transaction->save();
            }

            DB::commit();

            return response()->json(['status' => 'success', 'messages' => 'Data berhasil dihapus!'], 200);
        } catch (\Exception  $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }



}
