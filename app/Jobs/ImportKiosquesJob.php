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
        $totalRows = Excel::toArray([], $absolutePath)[0];
        $totalRows = count($totalRows) - 1; // Subtract header row

        Cache::put("job_progress_{$this->jobId}", [
            'total' => $totalRows,
            'processed' => 0,
            'progress' => 0,
            'status' => 'processing',
            'message' => 'Initialisation...'
        ]);

        Excel::import(new KiosquesImport($this->jobId), $absolutePath);

        Log::info('Import job finished processing file: ' . $this->filePath);
    }
}
