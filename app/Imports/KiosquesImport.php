<?php

namespace App\Imports;

use App\Jobs\GenerateQrCodeJob;
use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Bus\Batchable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KiosquesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    use Batchable;

    public function model(array $row)
    {
        $superAgentName = trim($row['sa_name'] ?? '');
        $distribName = trim($row['cia_dsm_md_name'] ?? '');
        $kiosqueName = trim($row['pos_msisdn'] ?? '');

        if (!$superAgentName || !$distribName || !$kiosqueName) {
            return null;
        }

        $superAgent = Super_agent::firstOrCreate(
            ['name' => $superAgentName],
            ['region' => $row['region'] ?? null]
        );

        $distributeur = Distributeur::firstOrCreate(
            [
                'name' => $distribName,
                'super_agent_id' => $superAgent->id
            ],
            ['phone' => trim($row['cia_dsm_md_msisdn'] ?? '')]
        );

        $kiosque = Kiosque::updateOrCreate(
            ['code' => trim($row['pos_code'] ?? '') . '@momopay'],
            [
                'name' => $kiosqueName,
                'phone' => trim($row['pos_msisdn'] ?? ''),
                'distributeur_id' => $distributeur->id,
                'bv' => trim($row['bv'] ?? ''),
                'region' => $row['region'] ?? null
            ]
        );

        $safeSuperAgent = preg_replace('/[^\w\-]/', '_', $superAgentName);
        $safeDistrib = preg_replace('/[^\w\-]/', '_', $distribName);
        $relativePath = "qr_codes/{$safeSuperAgent}/{$safeDistrib}";

        if ($this->batch()) {
            $this->batch()->add(new GenerateQrCodeJob($kiosque, $relativePath));
        }

        return null;
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
