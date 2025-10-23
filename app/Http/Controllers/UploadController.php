<?php

namespace App\Http\Controllers;

use App\Jobs\ImportKiosquesJob;
use App\Models\Distributeur;
use App\Models\Kiosque;
use App\Models\Super_agent;
use Illuminate\Http\Request;
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

            // Dispatch du job
            dispatch(new ImportKiosquesJob($path));

            //ImportKiosquesJob::dispatch($path);
            

            return back()->with('success', 'Fichier en cours de traitement. Les QR codes seront générés dans quelques instants.');

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
