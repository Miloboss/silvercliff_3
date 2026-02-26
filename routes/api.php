<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RoomController;

Route::get('/packages', [PackageController::class, 'index']);
Route::get('/packages/{slug}', [PackageController::class, 'show']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{slug}', [RoomController::class, 'show']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/check/{booking_code}', [BookingController::class, 'check']);
Route::post('/bookings/status', [BookingController::class, 'status']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
