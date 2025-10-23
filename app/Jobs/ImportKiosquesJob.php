<?php

namespace App\Jobs;

use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ImportKiosquesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $absolutePath = Storage::path($this->filePath);

        $data = Excel::toArray([], $absolutePath);
        $rows = $data[0];
        $headers = array_map('trim', $rows[0]);
        unset($rows[0]);

        foreach ($rows as $row) {
            $rowData = array_combine($headers, $row);

            $region = $rowData['REGION'] ?? null;
            $superAgentName = trim($rowData['SA NAME'] ?? '');
            $distribPhone = trim($rowData['Cia/ DSM/MD MSISDN'] ?? '');
            $distribName = trim($rowData['Cia/ DSM/MD NAME'] ?? '');
            $kiosquePhone = trim($rowData['PoS MSISDN'] ?? '');
            $kiosqueCode = trim($rowData['PoS code'] ?? '');
            $kiosqueName = trim($rowData['PoS MSISDN'] ?? '');
            $bv = trim($rowData['bv'] ?? '');

            if (!$superAgentName || !$distribName || !$kiosqueName) continue;

            // Nettoyer les noms pour éviter les erreurs de fichier
            $safeSuperAgent = preg_replace('/[^\w\-]/', '_', $superAgentName);
            $safeDistrib = preg_replace('/[^\w\-]/', '_', $distribName);
            $safeKiosque = preg_replace('/[^\w\-]/', '_', $kiosqueName);

            // 1️⃣ Créer ou récupérer le Super Agent
            $superAgent = Super_agent::firstOrCreate(
                ['name' => $superAgentName],
                ['region' => $region]
            );

            // 2️⃣ Créer ou récupérer le Distributeur
            $distributeur = Distributeur::firstOrCreate(
                [
                    'name' => $distribName,
                    'super_agent_id' => $superAgent->id
                ],
                ['phone' => $distribPhone]
            );

            // 3️⃣ Créer le Kiosque
            $kiosque = Kiosque::updateOrCreate(
                ['code' => $kiosqueCode],
                [
                    'name' => $kiosqueName,
                    'phone' => $kiosquePhone,
                    'distributeur_id' => $distributeur->id,
                    'bv' => $bv,
                    'region' => $region
                ]
            );

            $relativePath  = "qr_codes/{$safeSuperAgent}/{$safeDistrib}";
            GenerateQrCodeJob::dispatch($superAgent, $distributeur, $kiosque, $relativePath);

        }
        //Storage::delete($this->filePath);
        Log::info('Import terminé, fichier supprimé: ' . $this->filePath);
    }
}
