<?php

use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;


Route::get('/', [UploadController::class, 'index'])->name('upload.form');
Route::post('upload', [UploadController::class, 'upload'])->name('upload.validate');
Route::get('kiosques', [UploadController::class, 'listKiosques'])->name('kiosques.list');
Route::delete('/kiosques/delete-all', [UploadController::class, 'deleteAll'])->name('kiosques.deleteAll');

Route::get('/progress/{batchId}', function ($batchId) {
    return view('progress', ['batchId' => $batchId]);
})->name('upload.progress');

Route::get('/progress/{batchId}/json', [UploadController::class, 'progress'])->name('upload.progress.json');
Route::get('/download/all-qr', [QrCodeController::class, 'downloadAll'])->name('download.all.qr');
