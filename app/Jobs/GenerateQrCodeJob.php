<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQrCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $superAgent, $distributeur, $kiosque, $jobId, $total;

    public function __construct($superAgent, $distributeur, $kiosque, $jobId, $total)
    {
        $this->superAgent = $superAgent;
        $this->distributeur = $distributeur;
        $this->kiosque = $kiosque;
        $this->jobId = $jobId;
        $this->total = $total;
    }

    // 🛠️ Fonction Helper pour garantir le même nom partout (Vue et Job)
    private function formatName($name)
    {
        $name = preg_replace('/[^\w\-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        return trim($name, '_');
    }

    public function handle(): void
    {
        Log::info('Génération QR : ' . $this->kiosque->name);

        // 1. Construire le chemin dossier en utilisant le formatage sécurisé
        // Cela remplace $relativePath passé par le contrôleur pour être sûr que c'est propre
        $safeSa = $this->formatName($this->superAgent->name);
        $safeDist = $this->formatName($this->distributeur->name);
        
        $relativePath = "qr_codes/{$safeSa}/{$safeDist}";
        $folderPath = public_path($relativePath);

        // 2. Création du dossier
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true, true);
        }

        // 3. Nom du fichier Kiosque
        $safeKiosque = $this->formatName($this->kiosque->name);
        $qrCodePath = "{$folderPath}/{$safeKiosque}.svg";

        try {
            QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->kiosque->code, $qrCodePath);

        } catch (\Exception $e) {
            Log::error('Erreur SVG, tentative PNG : ' . $e->getMessage());
            $qrCodePath = "{$folderPath}/{$safeKiosque}.png";
            QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->kiosque->code, $qrCodePath);
        }

        // 4. Mise à jour Progression (Identique à votre code)
        $processed = Cache::increment("job_progress_{$this->jobId}_count");
        $progress = intval(($processed / $this->total) * 100);
        $status = ($processed >= $this->total) ? 'finished' : 'processing';
        
        Cache::put("job_progress_{$this->jobId}", [
            'total' => $this->total,
            'processed' => $processed,
            'progress' => $progress,
            'status' => $status,
            'message' => ($status === 'finished') ? "Terminé" : "Traitement {$processed} / {$this->total}"
        ]);
    }
}