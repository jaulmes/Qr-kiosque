<?php

namespace App\Jobs;

use App\Jobs\GenerateQrCodeJob;
use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportKiosquesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

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
        if ($this->batching() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // --- SIMULATION D'IMPORTATION ---
            Log::info('--- DÉBUT DE LA SIMULATION D\'IMPORTATION ---');

            $jobs = [];
            // Créer des données de test
            $superAgent = Super_agent::firstOrCreate(['name' => 'Super Agent de Test']);
            $distributeur = Distributeur::firstOrCreate(['name' => 'Distributeur de Test', 'super_agent_id' => $superAgent->id]);
            $relativePath = "qr_codes/Super_Agent_de_Test/Distributeur_de_Test";

            // Simuler la création de 100 jobs
            for ($i = 0; $i < 100; $i++) {
                $kiosque = Kiosque::updateOrCreate(
                    ['code' => 'CODE_TEST_' . $i . '@momopay'],
                    [
                        'name' => 'Kiosque de Test ' . $i,
                        'phone' => '123456789',
                        'distributeur_id' => $distributeur->id,
                        'bv' => 'BV_TEST',
                        'region' => 'REGION_TEST'
                    ]
                );
                $jobs[] = new GenerateQrCodeJob($kiosque, $relativePath);
            }

            if ($this->batching()) {
                $this->batch()->add($jobs);
            }

            Log::info('--- FIN DE LA SIMULATION : 100 jobs ont été ajoutés au lot ---');
            // --- FIN DE LA SIMULATION ---

        } catch (Throwable $e) {
            Log::error('Erreur durant la SIMULATION : ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->batching()) {
                $this->batch()->fail($e);
            }

            throw $e;
        }
    }
}
