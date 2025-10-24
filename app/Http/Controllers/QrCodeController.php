<?php

namespace App\Http\Controllers;

use App\Models\Super_agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use ZipArchive;

class QrCodeController extends Controller
{
    public function downloadAll()
    {
        $qrBasePath = public_path('qr_codes');
        $zipFileName = 'qr_codes_all.zip';
        $zipFilePath = storage_path("app/{$zipFileName}");

        // Supprimer le ZIP précédent s'il existe
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {

            // Parcourir tous les sous-dossiers
            $files = File::allFiles($qrBasePath);

            foreach ($files as $file) {
                // Ajouter le fichier dans le zip en gardant la hiérarchie des dossiers
                $relativePath = str_replace($qrBasePath . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $zip->addFile($file->getRealPath(), $relativePath);
            }

            $zip->close();
        } else {
            return back()->with('error', 'Impossible de créer le fichier ZIP.');
        }

        // Télécharger le zip
        return Response::download($zipFilePath)->deleteFileAfterSend(true);
    }
    
}
