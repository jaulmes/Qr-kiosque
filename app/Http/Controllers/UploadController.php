<?php

namespace App\Http\Controllers;

use App\Jobs\ImportKiosquesJob;
use App\Models\Distributeur;
use App\Models\JobProgress;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ], [
            'file.required' => 'Veuillez sélectionner un fichier',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV'
        ]);
        
        try {
            
            $path = $request->file('file')->store('imports');
            Log::info('Fichier uploadé avec succès: ' . $path);
            $jobId = (string) Str::uuid();

            // Créer une entrée de suivi du job
            JobProgress::create([
                'job_id' => $jobId,
                'status' => 'pending',
                'progress' => 0,
                'message' => 'En attente de traitement...'
            ]);

            // Dispatch du job
            dispatch(new ImportKiosquesJob($path, $jobId));

            
            // Redirige vers la vue de progression
            return view('progress', compact('jobId'));
            

        } catch (\Exception $e) {
            Log::error('Erreur upload: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    public function listKiosques(){
        $superAgents = Super_agent::all();
        return view('liste-qr-code', compact('superAgents'));
    }
}
