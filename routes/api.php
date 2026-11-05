<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function(){
 Route::get('/health',fn()=>response()->json(['status'=>'ok','service'=>'examtesthub-api','version'=>'1.0.0']))->name('api.v1.health');
 Route::middleware('auth:sanctum')->get('/me',fn(Request $request)=>response()->json(['data'=>$request->user()]))->name('api.v1.me');
});
