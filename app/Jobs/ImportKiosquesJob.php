<?php

namespace App\Jobs;

use App\Models\Distributeur;
use App\Models\JobProgress;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ImportKiosquesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $filePath, $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $jobId)
    {
        $this->filePath = $filePath;
        $this->jobId = $jobId;
    }

    /**
     * Exécute le job d'importation des kiosques à partir du fichier Excel.
     * 
     * Cette méthode lit le fichier Excel uploadé, crée ou met à jour les
     * enregistrements de Super Agents, Distributeurs et Kiosques dans la base
     * de données, puis déclenche la génération des QR codes pour chaque kiosque.
     */
    public function handle(): void
    {
        // 🔹 Récupère l'enregistrement du suivi de progression (JobProgress)
        $progress = JobProgress::where('job_id', $this->jobId)->first();

        // Si une entrée de progression existe, on met à jour son statut
        if ($progress) {
            $progress->update([
                'status' => 'running',
                'message' => 'Importation démarrée...'
            ]);
        }

        // 🔹 Récupère le chemin absolu du fichier Excel stocké (dans storage/app/imports)
        $absolutePath = Storage::path($this->filePath);

        // 🔹 Augmente la mémoire allouée à PHP pour gérer les fichiers Excel volumineux
        ini_set('memory_limit', '20G');

        // 🔹 Lit le contenu du fichier Excel sous forme de tableau (via Laravel Excel)
        // Le tableau contient toutes les lignes du fichier
        $data = Excel::toArray([], $absolutePath);

        // 🔹 On suppose que les données se trouvent dans la première feuille (index 0)
        $rows = $data[0];

        // 🔹 La première ligne du fichier contient les entêtes de colonnes
        $headers = array_map('trim', $rows[0]);

        // 🔹 Supprime la première ligne du tableau (les entêtes)
        unset($rows[0]);

        // 🔹 Nombre total de lignes à traiter (au moins 1 pour éviter une division par zéro)
        $total = max(count($rows), 1);

        // ✅ Initialisation du cache de progression
        // Ce cache permet de suivre le nombre de kiosques déjà traités par les workers
        Cache::put("job_progress_{$this->jobId}_count", 0, now()->addHours(1)); // compteur individuel
        Cache::put("job_progress_{$this->jobId}", [
            'total' => $total,        // nombre total de kiosques
            'processed' => 0,         // nombre déjà traités
            'progress' => 0,          // pourcentage d'avancement
            'status' => 'processing', // état global du job
            'message' => 'Initialisation...' // message affiché dans l’interface
        ]);

        // Chunk the rows into smaller arrays
        $chunks = array_chunk($rows, 1000);

        foreach ($chunks as $chunk) {
            $kiosquesData = [];
            // 🔁 Parcourt chaque ligne du fichier Excel (chaque ligne représente un kiosque)
            foreach ($chunk as $row) {

                // Associe chaque colonne à son entête correspondante
                $rowData = array_combine($headers, $row);

                // 🔹 Récupère les différentes colonnes nécessaires
                $region = $rowData['REGION'] ?? null;
                $superAgentName = trim($rowData['SA NAME'] ?? '');
                $distribPhone = trim($rowData['Cia/ DSM/MD MSISDN'] ?? '');
                $distribName = trim($rowData['Cia/ DSM/MD NAME'] ?? '');
                $kiosquePhone = trim($rowData['PoS MSISDN'] ?? '');
                $kiosqueCode = trim($rowData['PoS code'] ?? '');
                $kiosqueName = trim($rowData['PoS MSISDN'] ?? '');
                $bv = trim($rowData['bv'] ?? '');

                // Si certaines données essentielles manquent, on passe à la ligne suivante
                if (!$superAgentName || !$distribName || !$kiosqueName) continue;

                // 🔹 Nettoie les noms pour éviter les caractères spéciaux dans les noms de fichiers
                $safeSuperAgent = preg_replace('/[^\w\-]/', '_', $superAgentName);
                $safeDistrib = preg_replace('/[^\w\-]/', '_', $distribName);
                $safeKiosque = preg_replace('/[^\w\-]/', '_', $kiosqueName);

                // 1️⃣ Crée ou récupère le Super Agent correspondant à la ligne du fichier
                $superAgent = Super_agent::firstOrCreate(
                    ['name' => $superAgentName],
                    ['region' => $region]
                );

                // 2️⃣ Crée ou récupère le Distributeur lié à ce Super Agent
                $distributeur = Distributeur::firstOrCreate(
                    [
                        'name' => $distribName,
                        'super_agent_id' => $superAgent->id
                    ],
                    ['phone' => $distribPhone]
                );

                // 3️⃣ Crée ou met à jour le Kiosque correspondant
                $kiosque = Kiosque::updateOrCreate(
                    ['code' => $kiosqueCode . '@momopay'], // le code est unique
                    [
                        'name' => $kiosqueName,
                        'phone' => $kiosquePhone,
                        'distributeur_id' => $distributeur->id,
                        'bv' => $bv,
                        'region' => $region
                    ]
                );

                // 🔹 Définit le dossier de sauvegarde du QR code en fonction de l’arborescence
                // Exemple : qr_codes/SuperAgent/Distributeur/
                $relativePath = "qr_codes/{$safeSuperAgent}/{$safeDistrib}";

                $kiosquesData[] = [
                    'superAgent' => $superAgent,
                    'distributeur' => $distributeur,
                    'kiosque' => $kiosque,
                    'relativePath' => $relativePath,
                ];
            }
            // 🚀 Déclenche le job asynchrone de génération du QR code pour ce kiosque
            // Chaque QR code sera généré par un worker séparé
            GenerateQrCodeJob::dispatch($kiosquesData, $this->jobId, $total);
        }

        // 🧹 (Optionnel) Supprimer le fichier Excel après traitement
        // Storage::delete($this->filePath);

        // ✅ Log de fin de traitement (utile pour le débogage)
        Log::info(
            'Import terminé, fichier traité: ' . $this->filePath .
            ' | Nombre total de kiosques: ' . $total
        );
    }

}
