<?php

namespace App\Jobs;

use App\Imports\KiosquesImport;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

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

        Excel::import(new KiosquesImport($this->batch()), $this->filePath);
    }
}
