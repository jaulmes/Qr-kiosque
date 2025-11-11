<?php

namespace App\Imports;

use App\Jobs\GenerateQrCodeJob;
use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KiosquesImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected $batch;

    public function __construct($batch)
    {
        $this->batch = $batch;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $jobs = [];
        foreach ($rows as $row) {
            $region = $row['region'] ?? null;
            $superAgentName = trim($row['sa_name'] ?? '');
            $distribPhone = trim($row['cia_dsm_md_msisdn'] ?? '');
            $distribName = trim($row['cia_dsm_md_name'] ?? '');
            $kiosquePhone = trim($row['pos_msisdn'] ?? '');
            $kiosqueCode = trim($row['pos_code'] ?? '');
            $kiosqueName = trim($row['pos_msisdn'] ?? '');
            $bv = trim($row['bv'] ?? '');

            if (!$superAgentName || !$distribName || !$kiosqueName) {
                continue;
            }

            $safeSuperAgent = preg_replace('/[^\w\-]/', '_', $superAgentName);
            $safeDistrib = preg_replace('/[^\w\-]/', '_', $distribName);

            $superAgent = Super_agent::firstOrCreate(
                ['name' => $superAgentName],
                ['region' => $region]
            );

            $distributeur = Distributeur::firstOrCreate(
                [
                    'name' => $distribName,
                    'super_agent_id' => $superAgent->id
                ],
                ['phone' => $distribPhone]
            );

            $kiosque = Kiosque::updateOrCreate(
                ['code' => $kiosqueCode . '@momopay'],
                [
                    'name' => $kiosqueName,
                    'phone' => $kiosquePhone,
                    'distributeur_id' => $distributeur->id,
                    'bv' => $bv,
                    'region' => $region
                ]
            );

            $relativePath = "qr_codes/{$safeSuperAgent}/{$safeDistrib}";

            $jobs[] = new GenerateQrCodeJob($kiosque, $relativePath);
        }
        $this->batch->add($jobs);
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
