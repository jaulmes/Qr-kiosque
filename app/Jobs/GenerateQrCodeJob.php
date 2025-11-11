<?php

namespace App\Jobs;

use App\Models\Kiosque;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQrCodeJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $kiosque;
    protected $relativePath;

    public function __construct(Kiosque $kiosque, $relativePath)
    {
        $this->kiosque = $kiosque;
        $this->relativePath = $relativePath;
    }

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        Log::info('Génération du QR Code pour le kiosque: ' . $this->kiosque->name);
        $folderPath = public_path($this->relativePath);

        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true, true);
        }

        $safeKiosque = preg_replace('/[^\w\-]/', '_', $this->kiosque->name);
        $safeKiosque = preg_replace('/_+/', '_', $safeKiosque);
        $safeKiosque = trim($safeKiosque, '_');

        $qrCodePath = "{$folderPath}/{$safeKiosque}.svg";

        try {
            QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->kiosque->code, $qrCodePath);
        } catch (\Exception $e) {
            Log::error('Erreur génération QR: ' . $e->getMessage());

            $qrCodePath = "{$folderPath}/{$safeKiosque}.png";
            QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($this->kiosque->code, $qrCodePath);
        }
    }
}
