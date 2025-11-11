<?php

use App\Http\Controllers\BatchProgressController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\UploadController;
use App\Models\JobProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;


Route::get('/', [UploadController::class, 'index'])->name('upload.form');
Route::post('upload', [UploadController::class, 'upload'])->name('upload.validate');
Route::get('kiosques', [UploadController::class, 'listKiosques'])->name('kiosques.list');
Route::delete('/kiosques/delete-all', [UploadController::class, 'deleteAll'])->name('kiosques.deleteAll');


Route::get('/batch-progress/{batchId}', BatchProgressController::class);
Route::get('/download/all-qr', [QrCodeController::class, 'downloadAll'])->name('download.all.qr');

Route::get('/telecharger-qr/{jobId}', function ($jobId) {
    // Dossier racine des QR codes pour ce job
    $rootPath = public_path("qr_codes");
    $jobFolder = storage_path("app/qrcodes/{$jobId}");
    $zipPath = storage_path("app/qrcodes/{$jobId}.zip");

    // Vérifie que le dossier racine existe
    if (!File::exists($rootPath)) {
        return back()->with('error', 'Aucun QR code trouvé à compresser.');
    }

    // 🔹 Créer un dossier temporaire pour ce job (si pas déjà créé)
    if (!File::exists(dirname($zipPath))) {
        File::makeDirectory(dirname($zipPath), 0755, true, true);
    }

    // 🔹 Créer le ZIP
    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

        // Parcourir tous les sous-dossiers Super Agent / Distributeur / Kiosque
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                // Chemin relatif à la racine des QR codes (pour garder l’arborescence)
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    } else {
        return back()->with('error', 'Impossible de créer le fichier ZIP.');
    }

    // 🔹 Téléchargement et suppression après envoi
    return response()->download($zipPath)->deleteFileAfterSend(true);
});

