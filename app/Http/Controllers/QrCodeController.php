<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class QrCodeController extends Controller
{
    public function downloadAll()
    {
        $rootPath = public_path('qr_codes');

        if (!File::exists($rootPath)) {
            Log::warning('Download all QR codes failed: root directory does not exist.', ['path' => $rootPath]);
            return back()->with('error', 'Le dossier des QR codes n\'existe pas encore. Veuillez patienter ou vérifier que la génération a bien eu lieu.');
        }

        $zipPath = storage_path('app/qrcodes_'.uniqid().'.zip');
        $zipDir = dirname($zipPath);

        if (!File::exists($zipDir)) {
            File::makeDirectory($zipDir, 0755, true, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        } else {
            Log::error('Failed to create zip archive.', ['path' => $zipPath]);
            return back()->with('error', 'Impossible de créer l\'archive ZIP.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
