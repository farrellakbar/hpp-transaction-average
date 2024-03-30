<?php

use App\Http\Controllers\HppTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// API LIST DATA
Route::get('/data', [HppTransactionController::class, 'index']);
// API CREATE DATA
Route::post('/data', [HppTransactionController::class, 'store']);
// API UPDATE DATA
Route::put('/data/{id}', [HppTransactionController::class, 'update']);
// API delete DATA
Route::delete('/data/{id}', [HppTransactionController::class, 'delete']);
