<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;


Route::get('/', [UploadController::class, 'index'])->name('upload.form');
Route::post('upload', [UploadController::class, 'upload'])->name('upload.validate');
Route::get('kiosques', [UploadController::class, 'listKiosques'])->name('kiosques.list');
