<?php

namespace App\Jobs;

use App\Imports\KiosquesImport;
use App\Models\JobProgress;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportKiosquesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    protected $jobProgress;

    public function __construct($filePath, JobProgress $jobProgress)
    {
        $this->filePath = $filePath;
        $this->jobProgress = $jobProgress;
    }

    public function handle(): void
    {
        $import = new KiosquesImport();

        $batch = Bus::batch([
            $import,
        ])->then(function (Batch $batch) {
            JobProgress::find($this->jobProgress->id)->update([
                'status' => 'finished',
                'message' => 'Tous les QR codes ont été générés.'
            ]);
        })->catch(function (Batch $batch, Throwable $e) {
            JobProgress::find($this->jobProgress->id)->update([
                'status' => 'failed',
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ]);
        })->finally(function (Batch $batch) {
            Storage::delete($this->filePath);
        })->name('Import Kiosques')->dispatch();

        $this->jobProgress->update([
            'job_id' => $batch->id,
            'status' => 'running',
            'message' => 'Initialisation du traitement...'
        ]);

        Excel::import($import, $this->filePath);
    }
}
