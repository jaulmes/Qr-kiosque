<?php

namespace App\Http\Controllers;

use App\Jobs\ImportKiosquesJob;
use App\Models\Distributeur;
use App\Models\JobProgress;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ], [
            'file.required' => 'Veuillez sélectionner un fichier',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV'
        ]);

        try {
            Log::info('Validation passed. Storing file...');
            $path = $request->file('file')->store('imports');
            Log::info('Fichier uploadé avec succès: ' . $path);

            Log::info('Creating batch...');
            $batch = Bus::batch([
                new ImportKiosquesJob($path),
            ])->then(function ($batch) {
                Log::info('Batch finished successfully.');
            })->catch(function ($batch, $e) {
                Log::error('Batch failed: ' . $e->getMessage());
            })->finally(function ($batch) {
                Log::info('Batch finished.');
            })->dispatch();
            Log::info('Batch dispatched with ID: ' . $batch->id);

            Log::info('Redirecting to progress page...');
            return redirect()->route('upload.progress', ['batchId' => $batch->id]);

        } catch (\Exception $e) {
            Log::error('Erreur upload: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    public function listKiosques()
    {
        $superAgents = Super_agent::all();
        $qrBasePath = public_path('qr_codes');
        $kiosques = Kiosque::all();
        return view('liste-qr-code', compact('superAgents', 'qrBasePath', 'kiosques'));
    }

    public function deleteAll()
    {
        try {
            // Supprimer d'abord les kiosques
            Kiosque::truncate();

            // Ensuite les distributeurs
            Distributeur::query()->delete();

            // Enfin les super agents
            Super_agent::query()->delete();

            // Supprimer les fichiers QR codes
            $qrBasePath = public_path('qr_codes');
            if (File::exists($qrBasePath)) {
                File::deleteDirectory($qrBasePath);
            }

            Log::info('Tous les kiosques et QR codes ont été supprimés avec succès.');
            return back()->with('success', 'Tous les kiosques et QR codes ont été supprimés avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des kiosques: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la suppression : ' . $e->getMessage());
        }
    }

    public function progress($batchId)
    {
        return Bus::findBatch($batchId);
    }
}
