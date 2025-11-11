<?php

namespace App\Imports;

use App\Jobs\GenerateQrCodeJob;
use Illuminate\Support\Collection;
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
