<?php

namespace App\Imports;

use App\Jobs\GenerateQrCodeJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KiosquesImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
    }

    public function collection(Collection $rows)
    {
        $progressData = Cache::get("job_progress_{$this->jobId}");
        $processed = ($progressData['reading']['processed'] ?? 0) + $rows->count();
        $total = $progressData['reading']['total'] ?? 0;
        $progress = $total > 0 ? intval(($processed / $total) * 100) : 0;

        $progressData['reading']['processed'] = $processed;
        $progressData['reading']['progress'] = $progress;
        $progressData['message'] = "Lecture du fichier en cours ({$processed} / {$total} lignes)...";

        Cache::put("job_progress_{$this->jobId}", $progressData);

        $kiosqueData = $rows->map(function ($row) {
            return [
                'region' => $row['region'] ?? null,
                'super_agent_name' => trim($row['sa_name'] ?? ''),
                'distributor_phone' => trim($row['cia_dsm_md_msisdn'] ?? ''),
                'distributor_name' => trim($row['cia_dsm_md_name'] ?? ''),
                'kiosque_phone' => trim($row['pos_msisdn'] ?? ''),
                'kiosque_code' => trim($row['pos_code'] ?? ''),
                'kiosque_name' => trim($row['kiosque_name'] ?? ''),
                'bv' => trim($row['bv'] ?? ''),
            ];
        })->all();

        if (!empty($kiosqueData)) {
            GenerateQrCodeJob::dispatch($kiosqueData, $this->jobId);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
