<?php

namespace App\Http\Controllers;

use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UploadController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

public function upload(Request $request)
{
    DB::beginTransaction();

    try {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $path = $request->file('file')->getRealPath();
        $data = Excel::toArray([], $path);
        $rows = $data[0];
        $headers = array_map('trim', $rows[0]);
        unset($rows[0]);

        foreach ($rows as $row) {
            $rowData = array_combine($headers, $row);

            $region = $rowData['REGION'] ?? null;
            $superAgentName = trim($rowData['SA NAME'] ?? '');
            $distribPhone = trim($rowData['Cia/ DSM/MD MSISDN'] ?? '');
            $distribName = trim($rowData['Cia/ DSM/MD NAME'] ?? '');
            $kiosquePhone = trim($rowData['PoS MSISDN'] ?? '');
            $kiosqueCode = trim($rowData['PoS code'] ?? '');
            $kiosqueName = trim($rowData['PoS MSISDN'] ?? '');
            $bv = trim($rowData['bv'] ?? '');

            if (!$superAgentName || !$distribName || !$kiosqueName) continue;

            // Nettoyer les noms pour éviter les erreurs de fichier
            $safeSuperAgent = preg_replace('/[^\w\-]/', '_', $superAgentName);
            $safeDistrib = preg_replace('/[^\w\-]/', '_', $distribName);
            $safeKiosque = preg_replace('/[^\w\-]/', '_', $kiosqueName);

            // 1️⃣ Créer ou récupérer le Super Agent
            $superAgent = Super_agent::firstOrCreate(
                ['name' => $superAgentName],
                ['region' => $region]
            );

            // 2️⃣ Créer ou récupérer le Distributeur
            $distributeur = Distributeur::firstOrCreate(
                [
                    'name' => $distribName,
                    'super_agent_id' => $superAgent->id
                ],
                ['phone' => $distribPhone]
            );

            // 3️⃣ Créer le Kiosque
            $kiosque = Kiosque::updateOrCreate(
                ['code' => $kiosqueCode],
                [
                    'name' => $kiosqueName,
                    'phone' => $kiosquePhone,
                    'distributeur_id' => $distributeur->id,
                    'bv' => $bv,
                    'region' => $region
                ]
            );

            // 4️⃣ Générer le QR Code
            $folderPath = public_path("qr_codes/{$safeSuperAgent}/{$safeDistrib}");
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            $qrCodePath = "{$folderPath}/{$safeKiosque}.svg";

            try {
                // Générer en SVG (ne dépend pas d’Imagick)
                QrCode::format('svg')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($kiosqueCode, $qrCodePath);
            } catch (\Exception $e) {
                // Si SVG échoue, fallback vers PNG en mode GD
                $qrCodePath = "{$folderPath}/{$safeKiosque}.png";
                QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($kiosqueCode, $qrCodePath);
            }
        }

        DB::commit();
        return back()->with('success', 'Fichier importé et QR codes générés avec succès 🎉');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Une erreur est survenue : ' . $e->getMessage());
    }
}

    public function listKiosques(){
        $superAgents = Super_agent::all();
        return view('liste-qr-code', compact('superAgents'));
    }
}
