<?php

use App\Services\AircallService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ns-conseil/aircall/recording/{callId}', function (string $callId) {
    $call = app(AircallService::class)->getCall($callId);
    return response()->json(['url' => $call['recording'] ?? null]);
})->middleware(['auth', 'web']);