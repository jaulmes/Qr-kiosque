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
    // Validation des données
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:10240' // Max 10MB
    ], [
        'file.required' => 'Veuillez sélectionner un fichier',
        'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV',
        'file.max' => 'Le fichier ne doit pas dépasser 10MB'
    ]);

    DB::beginTransaction();

    try {
        // Lecture du fichier Excel
        $path = $request->file('file')->getRealPath();
        $data = Excel::toArray([], $request->file('file'));
        
        // Vérification que le fichier contient des données
        if (empty($data) || empty($data[0])) {
            throw new \Exception('Le fichier est vide ou mal formaté');
        }

        $rows = $data[0];
        
        // Vérification qu'il y a au moins 2 lignes (header + données)
        if (count($rows) < 2) {
            throw new \Exception('Le fichier ne contient pas de données à importer');
        }

        // Récupère et normalise les en-têtes
        $headers = array_map(function($header) {
            return trim(strtoupper($header));
        }, $rows[0]);
        
        // Vérification des colonnes requises
        $requiredColumns = [
            'REGION', 
            'SA NAME', 
            'CIA/ DSM/MD MSISDN', 
            'CIA/ DSM/MD NAME', 
            'POS MSISDN', 
            'POS CODE',
            'BV'
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            $columnUpper = strtoupper($column);
            if (!in_array($columnUpper, $headers)) {
                $missingColumns[] = $column;
            }
        }
        
        if (!empty($missingColumns)) {
            throw new \Exception('Colonnes manquantes dans le fichier : ' . implode(', ', $missingColumns));
        }

        unset($rows[0]); // Supprime la ligne des en-têtes
        
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        foreach ($rows as $index => $row) {
            try {
                // Vérifier que la ligne n'est pas vide
                if (empty(array_filter($row))) {
                    $skippedCount++;
                    continue;
                }

                $rowData = array_combine($headers, $row);
                
                // Extraction et nettoyage des données
                $region = trim($rowData['REGION'] ?? '');
                $superAgentName = trim($rowData['SA NAME'] ?? '');
                $distribPhone = $this->cleanPhone($rowData['CIA/ DSM/MD MSISDN'] ?? '');
                $distribName = trim($rowData['CIA/ DSM/MD NAME'] ?? '');
                $kiosquePhone = $this->cleanPhone($rowData['POS MSISDN'] ?? '');
                $kiosqueCode = trim($rowData['POS CODE'] ?? '');
                $kiosqueName = trim($rowData['POS NAME'] ?? $rowData['POS MSISDN'] ?? '');
                $bv = trim($rowData['BV'] ?? '');
                
                // Validation des données obligatoires
                if (empty($superAgentName)) {
                    throw new \Exception("Nom du Super Agent manquant");
                }
                if (empty($distribName)) {
                    throw new \Exception("Nom du Distributeur manquant");
                }
                if (empty($kiosqueCode)) {
                    throw new \Exception("Code du Kiosque manquant");
                }
                if (empty($kiosqueName)) {
                    $kiosqueName = $kiosquePhone ?: $kiosqueCode;
                }

                // 1️⃣ Créer ou récupérer le Super Agent
                $superAgent = Super_agent::firstOrCreate(
                    ['name' => $superAgentName],
                    ['region' => $region]
                );

                // Mettre à jour la région si nécessaire
                if ($region && $superAgent->region !== $region) {
                    $superAgent->update(['region' => $region]);
                }

                // 2️⃣ Créer ou récupérer le Distributeur
                $distributeur = Distributeur::firstOrCreate(
                    [
                        'name' => $distribName,
                        'super_agent_id' => $superAgent->id
                    ],
                    ['phone' => $distribPhone]
                );
                
                // Mise à jour du téléphone si nécessaire
                if ($distribPhone && $distributeur->phone !== $distribPhone) {
                    $distributeur->update(['phone' => $distribPhone]);
                }

                // 3️⃣ Créer ou mettre à jour le Kiosque
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
                $this->generateQRCode(
                    $superAgent->name, 
                    $distributeur->name, 
                    $kiosque->name, 
                    $kiosque->code
                );
                
                $successCount++;
                
            } catch (\Exception $e) {
                $errorCount++;
                $lineNumber = $index + 1;
                $errors[] = "Ligne {$lineNumber}: " . $e->getMessage();
                
                // Limiter le nombre d'erreurs affichées
                if (count($errors) >= 10) {
                    $remainingErrors = $errorCount - 10;
                    if ($remainingErrors > 0) {
                        $errors[] = "... et {$remainingErrors} autre(s) erreur(s)";
                    }
                    break;
                }
            }
        }

        DB::commit();
        
        // Messages de retour détaillés
        $message = $this->formatImportMessage($successCount, $errorCount, $skippedCount, $errors);
        
        if ($successCount > 0 && $errorCount === 0) {
            return back()->with('success', $message);
        } elseif ($successCount > 0 && $errorCount > 0) {
            return back()->with('error', $message);
        } else {
            throw new \Exception($message);
        }
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        // Log de l'erreur pour le débogage
        \Log::error('Erreur lors de l\'importation Excel', [
            'message' => $e->getMessage(),
            'file' => $request->file('file')->getClientOriginalName(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
    }
}

/**
 * Nettoie et formate un numéro de téléphone
 */
private function cleanPhone($phone)
{
    if (empty($phone)) {
        return null;
    }
    
    // Supprime tous les caractères non numériques sauf le +
    $phone = preg_replace('/[^0-9+]/', '', trim($phone));
    
    // Retourne null si vide après nettoyage
    return empty($phone) ? null : $phone;
}

/**
 * Génère un QR Code pour un kiosque
 */
private function generateQRCode($superAgentName, $distribName, $kiosqueName, $kiosqueCode)
{
    // Nettoyer les noms pour les chemins de fichiers
    $superAgentName = $this->sanitizeFileName($superAgentName);
    $distribName = $this->sanitizeFileName($distribName);
    $kiosqueName = $this->sanitizeFileName($kiosqueName);
    
    // Créer le dossier si nécessaire
    $folderPath = storage_path("qr_codes/{$superAgentName}/{$distribName}");
    if (!File::exists($folderPath)) {
        File::makeDirectory($folderPath, 0755, true);
    }

    try {
        // Essayer d'abord avec SVG (pas besoin d'extension)
        $qrCodePath = "{$folderPath}/{$kiosqueName}.svg";
        QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($kiosqueCode, $qrCodePath);
            
    } catch (\Exception $e) {
        // Si SVG échoue, essayer PNG avec GD
        try {
            $qrCodePath = "{$folderPath}/{$kiosqueName}.png";
            QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($kiosqueCode, $qrCodePath);
        } catch (\Exception $e2) {
            // Log de l'erreur mais ne pas bloquer l'import
            \Log::warning("Impossible de générer le QR Code pour {$kiosqueName}", [
                'code' => $kiosqueCode,
                'error' => $e2->getMessage()
            ]);
            throw new \Exception("Erreur de génération du QR Code: " . $e2->getMessage());
        }
    }
}

/**
 * Nettoie un nom de fichier pour le système de fichiers
 */
private function sanitizeFileName($name)
{
    // Remplace les caractères spéciaux par des underscores
    $name = preg_replace('/[^A-Za-z0-9\-_\s]/', '_', $name);
    // Remplace les espaces par des underscores
    $name = str_replace(' ', '_', $name);
    // Supprime les underscores multiples
    $name = preg_replace('/_+/', '_', $name);
    // Supprime les underscores au début et à la fin
    $name = trim($name, '_');
    
    // Si le nom est vide après nettoyage, utiliser un nom par défaut
    return empty($name) ? 'unnamed' : $name;
}

/**
 * Formate le message final d'importation
 */
private function formatImportMessage($successCount, $errorCount, $skippedCount, $errors)
{
    $message = '';
    
    if ($successCount > 0) {
        $message .= "✅ {$successCount} kiosque(s) importé(s) avec succès";
        if ($successCount > 0) {
            $message .= " et QR codes générés";
        }
        $message .= ".\n\n";
    }
    
    if ($skippedCount > 0) {
        $message .= "⚠️ {$skippedCount} ligne(s) vide(s) ignorée(s).\n\n";
    }
    
    if ($errorCount > 0) {
        $message .= "❌ {$errorCount} erreur(s) détectée(s) :\n";
        $message .= implode("\n", $errors);
    }
    
    if ($successCount === 0 && $errorCount === 0) {
        $message = "Aucune donnée n'a été importée.";
    }
    
    return trim($message);
}

    public function listKiosques(){
        $superAgents = Super_agent::all();
        return view('liste-qr-code', compact('superAgents'));
    }
}
