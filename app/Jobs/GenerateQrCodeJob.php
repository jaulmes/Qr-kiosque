<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQrCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $kiosquesData, $jobId, $total;

    /**
     * Create a new job instance.
     */
    public function __construct( $kiosquesData, $jobId, $total)
    {
        $this->kiosquesData = $kiosquesData;
        $this->jobId = $jobId;
        $this->total = $total;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->kiosquesData as $data) {
            $kiosque = $data['kiosque'];
            $relativePath = $data['relativePath'];
            //  Journaliser le début du traitement pour ce kiosque
            Log::info('Génération du QR Code pour le kiosque: ' . $kiosque->name);

            //  Définir le chemin absolu vers le dossier où seront enregistrés les QR codes
            $folderPath = public_path($relativePath);

            //  Vérifier si le dossier existe, sinon le créer avec les permissions nécessaires
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true, true);
            }

            //  Nettoyer le nom du kiosque pour éviter les caractères spéciaux dans le nom du fichier
            $safeKiosque = preg_replace('/[^\w\-]/', '_', $kiosque->name); // Remplace tout caractère non autorisé par "_"
            $safeKiosque = preg_replace('/_+/', '_', $safeKiosque); // Évite les doubles underscores
            $safeKiosque = trim($safeKiosque, '_'); // Supprime les underscores au début/fin du nom

            //  Définir le chemin complet du fichier SVG à générer
            $qrCodePath = "{$folderPath}/{$safeKiosque}.svg";

            try {
                //  Générer le QR Code au format SVG (format léger et indépendant d’ImageMagick)
                QrCode::format('svg')
                    ->size(300)               // Taille du QR code
                    ->margin(1)               // Petite marge autour
                    ->errorCorrection('H')    // Niveau de correction d’erreur élevé (H = 30%)
                    ->generate($kiosque->code, $qrCodePath); // Génère le fichier dans le chemin défini

            } catch (\Exception $e) {
                //  En cas d’échec de génération SVG, journaliser l’erreur
                Log::error('Erreur génération QR: ' . $e->getMessage());

                //  Repli (fallback) : générer le QR Code au format PNG avec la librairie GD
                $qrCodePath = "{$folderPath}/{$safeKiosque}.png";
                QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($kiosque->code, $qrCodePath);
            }

        }
        $processed = Cache::increment("job_progress_{$this->jobId}_count", count($this->kiosquesData));

        // 📈 Calculer le pourcentage d’avancement du traitement
        $progress = intval(($processed / $this->total) * 100);

        //  Déterminer le statut global du job
        $status = ($processed >= $this->total) ? 'finished' : 'processing';

        // Définir un message lisible pour l’utilisateur selon le statut
        $message = ($status === 'finished')
                ? " Tous les QR codes ont été générés"
                : "Génération du QR code {$processed} / {$this->total}";

        //  Mettre à jour les informations de progression dans le cache (utilisées pour la barre de chargement)
        Cache::put("job_progress_{$this->jobId}", [
            'total' => $this->total,        // Nombre total de kiosques à traiter
            'processed' => $processed,      // Nombre de kiosques déjà traités
            'progress' => $progress,        // Pourcentage d’avancement
            'status' => $status,            // Statut actuel : "processing" ou "finished"
            'message' => $message           // Message d’état pour l’affichage sur la vue
        ]);
        //  Fin du job pour ce kiosque — à ce stade, la barre de progression peut se mettre à jour automatiquement via AJAX
    }

}
