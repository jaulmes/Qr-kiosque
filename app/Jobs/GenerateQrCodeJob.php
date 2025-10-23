<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQrCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $superAgent, $distributeur, $kiosque, $relativePath ;

    /**
     * Create a new job instance.
     */
    public function __construct( $superAgent, $distributeur, $kiosque, $relativePath)
    {
        $this->superAgent = $superAgent;
        $this->distributeur = $distributeur;
        $this->kiosque = $kiosque;
        $this->relativePath  = $relativePath ;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Génération du QR Code pour le kiosque: ' . $this->kiosque->name);
        Log::info('Chemin relatif pour le QR Code: ' . $this->relativePath);
        $folderPath = public_path($this->relativePath) ;
        Log::info('Chemin du dossier QR Code: ' . $folderPath);
        // 4️⃣ Générer le QR Code
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true, true);
        }

                    // Nettoyer le nom du kiosque pour le nom de fichier
            $safeKiosque = preg_replace('/[^\w\-]/', '_', $this->kiosque->name);
            $safeKiosque = preg_replace('/_+/', '_', $safeKiosque);
            $safeKiosque = trim($safeKiosque, '_');

        $qrCodePath = "{$folderPath}/{$safeKiosque}.svg";

        try {
            // Générer en SVG (ne dépend pas d’Imagick)
            QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->kiosque->code, $qrCodePath);
        } catch (\Exception $e) {
            Log::error('Erreur génération QR: ' . $e->getMessage());

            // Si SVG échoue, fallback vers PNG en mode GD
            $qrCodePath = "{$folderPath}/{$safeKiosque}.png";
            QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->kiosque->code, $qrCodePath);
        }
    }
}
