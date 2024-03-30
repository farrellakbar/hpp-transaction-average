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

            $hpp = New HppTransaction();
            $hpp->description = $request->description;
            $hpp->date = $request->date;
            $hpp->qty = $request->qty;
            $hpp->price = $request->price;

            $lastQtyBalance = DB::table('hpp_transactions')->orderBy('date', 'desc')->orderBy('id', 'desc')->value('qty_balance');
            $lastValueBalance = DB::table('hpp_transactions')->orderBy('date', 'desc')->orderBy('id', 'desc')->value('value_balance');
            if($request->description == 'Pembelian'){
                $hpp->cost = $request->price;

            } elseif($request->description == 'Penjualan'){
                $lastHpp = DB::table('hpp_transactions')->orderBy('date', 'desc')->orderBy('id', 'desc')->value('hpp');
                $hpp->cost = $lastHpp;
            }

            $hpp->total_cost = $request->qty * $hpp->cost;
            $hpp->qty_balance = $lastQtyBalance + $request->qty;
            $hpp->value_balance = $lastValueBalance + $hpp->total_cost;
            $hpp->hpp = number_format($hpp->value_balance / $hpp->qty_balance, 4, '.', '');

            $hpp->save();

            DB::commit();

            return response()->json(['status' => 'success', 'messages' => 'Data berhasil ditambah!', 'data' => $hpp], 200);
        } catch (\Exception  $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'messages' => $validator->errors()], 500);
        }
    }
    private function recalculate($id)
    {

            $hppTransactions = HppTransaction::where('id', '>=', $id)->orderBy('id', 'asc')->get();
            foreach ($hppTransactions as $hppTransaction) {
                $hppTransaction->total_cost = $hppTransaction->qty * $hppTransaction->cost;
                $hppTransaction->save();

                $hppTransaction->qty_balance = $hppTransaction->previousTransactions()->sum('qty') + $hppTransaction->qty;
                if($hppTransaction->qty_balance <= 0){
                    return 'false';
                }
                $hppTransaction->value_balance = $hppTransaction->previousTransactions()->sum('value_balance') + $hppTransaction->total_cost;
                $hppTransaction->hpp = number_format($hppTransaction->value_balance / $hppTransaction->qty_balance, 4, '.', '');
                $hppTransaction->save();
            }
            return 'true';
    }
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'qty' => 'required|integer',
                'price' => 'required|numeric|min:0.01',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'messages' => $validator->errors()], 422);
            }

            $hpp = HppTransaction::find($id);

            if (!$hpp) {
                return response()->json(['status' => 'error', 'messages' => 'Data not found'], 404);
            }

            if ($hpp->description == 'Pembelian') {
                $hpp->cost = $request->price;
            }
            elseif ($hpp->description == 'Penjualan') {
                $lastHpp = HppTransaction::where('id', '<', $hpp->id)->orderBy('id', 'desc')->value('hpp');
                $hpp->cost = $lastHpp;
            }

            $hpp->qty = $request->qty;
            $hpp->price = $request->price;
            $hpp->total_cost = $request->qty * $hpp->cost;
            $hpp->save();

            if ($this->recalculate($id) === 'false') {
                DB::rollback();
                return response()->json(['status' => 'error', 'messages' => 'Kurang dari 0'], 500);
            }
            DB::commit();

            return response()->json(['status' => 'success', 'messages' => 'Data berhasil diupdate', 'data' => $hpp], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }
    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $hppTransaction = HppTransaction::find($id);

            if (!$hppTransaction) {
                return response()->json(['status' => 'error', 'messages' => 'Data tidak ditemukan'], 404);
            }

            $hppTransaction->delete();

            if ($this->recalculate($id) === 'false') {
                DB::rollback();
                return response()->json(['status' => 'error', 'messages' => 'Kurang dari 0'], 500);
            }
            DB::commit();

            return response()->json(['status' => 'success', 'messages' => 'Data berhasil dihapus'], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }


}
