<?php

namespace App\Jobs;

use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
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

    protected $kiosqueData, $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $kiosqueData, $jobId)
    {
        $this->kiosqueData = $kiosqueData;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $processedInThisChunk = 0;
        foreach ($this->kiosqueData as $data) {
            if (empty($data['super_agent_name']) || empty($data['distributor_name']) || empty($data['kiosque_name'])) {
                continue;
            }

            $superAgent = Super_agent::firstOrCreate(
                ['name' => $data['super_agent_name']],
                ['region' => $data['region']]
            );

            $distributeur = Distributeur::firstOrCreate(
                ['name' => $data['distributor_name'], 'super_agent_id' => $superAgent->id],
                ['phone' => $data['distributor_phone']]
            );

            $kiosque = Kiosque::updateOrCreate(
                ['code' => $data['kiosque_code'] . '@momopay'],
                [
                    'name' => $data['kiosque_name'],
                    'phone' => $data['kiosque_phone'],
                    'distributeur_id' => $distributeur->id,
                    'bv' => $data['bv'],
                    'region' => $data['region'],
                ]
            );

            $this->generateQrCode($kiosque);
            $processedInThisChunk++;
        }

        $progressData = Cache::get("job_progress_{$this->jobId}");
        $total = $progressData['total'] ?? 0;
        $processed = ($progressData['processed'] ?? 0) + $processedInThisChunk;
        $progress = $total > 0 ? intval(($processed / $total) * 100) : 0;
        $status = ($processed >= $total) ? 'finished' : 'processing';
        $message = ($status === 'finished')
            ? "Tous les QR codes ont été générés"
            : "Génération du QR code {$processed} / {$total}";

        Cache::put("job_progress_{$this->jobId}", [
            'total' => $total,
            'processed' => $processed,
            'progress' => $progress,
            'status' => $status,
            'message' => $message
        ]);
    }

    private function generateQrCode(Kiosque $kiosque)
    {
        $distributeur = $kiosque->distributeur;
        if (!$distributeur) {
            Log::warning("Distributeur non trouvé pour le kiosque ID: {$kiosque->id} ('{$kiosque->name}'), skipping.");
            return;
        }

        $superAgent = $distributeur->super_agent;
        if (!$superAgent) {
            Log::warning("Super-agent non trouvé pour le distributeur ID: {$distributeur->id} ('{$distributeur->name}'), skipping.");
            return;
        }

        $safeSuperAgent = preg_replace('/[^\w\-]/', '_', $superAgent->name);
        $safeDistrib = preg_replace('/[^\w\-]/', '_', $distributeur->name);
        $safeKiosque = preg_replace('/[^\w\-]/', '_', $kiosque->name);

        $relativePath = "qr_codes/{$safeSuperAgent}/{$safeDistrib}";
        $folderPath = public_path($relativePath);

        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true, true);
        }

        $qrCodePath = "{$folderPath}/{$safeKiosque}.svg";

        try {
            QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($kiosque->code, $qrCodePath);
        } catch (\Exception $e) {
            Log::error('Erreur génération QR (SVG): ' . $e->getMessage());

            // Fallback to PNG
            try {
                $qrCodePath = "{$folderPath}/{$safeKiosque}.png";
                QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($kiosque->code, $qrCodePath);
            } catch (\Exception $pngException) {
                Log::error('Erreur génération QR (PNG): ' . $pngException->getMessage());
            }
        }
    }
}
