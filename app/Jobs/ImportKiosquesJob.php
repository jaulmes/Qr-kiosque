<?php

namespace App\Jobs;

use App\Imports\KiosquesImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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
     * Execute the job.
     */
    public function handle(): void
    {
        $absolutePath = Storage::path($this->filePath);

        // Get total rows for progress tracking
        $totalRows = count(Excel::toArray([], $absolutePath)[0]) - 1; // Subtract header row

        Cache::put("job_progress_{$this->jobId}", [
            'status' => 'processing',
            'message' => 'Initialisation de la lecture du fichier...',
            'reading' => [
                'processed' => 0,
                'total' => $totalRows,
                'progress' => 0,
            ],
            'generating' => [
                'processed' => 0,
                'total' => $totalRows,
                'progress' => 0,
            ],
            'time' => [
                'start' => time(),
                'remaining' => null,
            ],
        ]);

        Excel::import(new KiosquesImport($this->jobId), $absolutePath);

        Log::info('Import job finished processing file: ' . $this->filePath);
    }
}
