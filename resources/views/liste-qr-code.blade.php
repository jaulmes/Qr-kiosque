@extends('dashboard.main')

@section('content')
<div class="container py-4">

    {{-- En-tête (Pas de changement ici) --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-column flex-md-row">
        <div>
            <h2 class="mb-1">📁 Gestion des QR Codes</h2>
            <p class="text-muted mb-0">Super Agent → Distributeur → Kiosque</p>
        </div>
        
        <div class="d-flex gap-2 flex-wrap justify-content-start justify-content-md-end mt-3 mt-md-0">
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-primary" onclick="expandAll()"><i class="bi bi-arrows-expand"></i> Tout ouvrir</button>
                <button class="btn btn-outline-secondary" onclick="collapseAll()"><i class="bi bi-arrows-collapse"></i> Tout fermer</button>
            </div>

            @php
                use Illuminate\Support\Facades\File;
                $qrBasePath = public_path('qr_codes');
                $qrFolderExists = File::exists($qrBasePath) && count(File::allFiles($qrBasePath)) > 0;
                $hasData = !$superAgents->isEmpty();
            @endphp

            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('download.all.qr') }}" class="btn btn-success {{ (!$hasData && !$qrFolderExists) ? 'disabled' : '' }}">
                    <i class="bi bi-download"></i> Télécharger (ZIP)
                </a>
                <form action="{{ route('kiosques.deleteAll') }}" method="POST" class="d-inline-block" onsubmit="return confirm('⚠️ Irréversible ! Tout supprimer ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger {{ (!$hasData && !$qrFolderExists) ? 'disabled' : '' }}">
                        <i class="bi bi-trash"></i> Tout supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Gestion des messages flash (Pas de changement) --}}
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @if($superAgents->isEmpty())
        <div class="alert alert-info mt-3">Aucun Super Agent trouvé.</div>
    @else
        <div class="accordion" id="superAgentAccordion">
            
            @foreach ($superAgents as $sa)
                <div class="accordion-item border rounded mb-3 shadow-sm">
                    <h2 class="accordion-header" id="heading-sa-{{ $sa->id }}">
                        <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-sa-{{ $sa->id }}">
                            <div class="d-flex align-items-center w-100">
                                <i class="bi bi-building text-primary me-3" style="font-size: 1.5rem;"></i>
                                <div class="flex-grow-1">
                                    <strong class="d-block">{{ $sa->name }}</strong>
                                    <small class="text-muted">{{ $sa->region ?? 'N/A' }} • {{ $sa->distributeurs->count() }} distributeur(s)</small>
                                </div>
                            </div>
                        </button>
                    </h2>
                    
                    <div id="collapse-sa-{{ $sa->id }}" class="accordion-collapse collapse" data-bs-parent="#superAgentAccordion">
                        <div class="accordion-body bg-light">
                            @if($sa->distributeurs->isEmpty())
                                <p class="text-muted mb-0">Aucun distributeur</p>
                            @else
                                <div class="accordion" id="distributeurAccordion-{{ $sa->id }}">
                                    @foreach ($sa->distributeurs as $dist)
                                        <div class="accordion-item border rounded mb-2">
                                            <h2 class="accordion-header" id="heading-dist-{{ $dist->id }}">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-dist-{{ $dist->id }}">
                                                    <div class="d-flex align-items-center w-100">
                                                        <i class="bi bi-shop text-success me-3" style="font-size: 1.3rem;"></i>
                                                        <div class="flex-grow-1">
                                                            <strong class="d-block">{{ $dist->name }}</strong>
                                                            <small class="text-muted">{{ $dist->kiosques->count() }} kiosque(s)</small>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            
                                            <div id="collapse-dist-{{ $dist->id }}" class="accordion-collapse collapse" data-bs-parent="#distributeurAccordion-{{ $sa->id }}">
                                                <div class="accordion-body">
                                                    @if($dist->kiosques->isEmpty())
                                                        <p class="text-muted mb-0">Aucun kiosque</p>
                                                    @else
                                                        <div class="row g-3">
                                                            @foreach ($dist->kiosques as $k)
                                                                @php
                                                                    // 🔥 CORRECTION CRITIQUE ICI 🔥
                                                                    // On remplace Str::slug par la même logique que le JOB
                                                                    // pour conserver la Casse (Majuscules/Minuscules)
                                                                    
                                                                    $formatName = function($name) {
                                                                        $safe = preg_replace('/[^\w\-]/', '_', $name);
                                                                        $safe = preg_replace('/_+/', '_', $safe);
                                                                        return trim($safe, '_');
                                                                    };

                                                                    $safeSa = $formatName($sa->name);
                                                                    $safeDist = $formatName($dist->name);
                                                                    $safeKio = $formatName($k->name);

                                                                    $svgPath = asset("qr_codes/{$safeSa}/{$safeDist}/{$safeKio}.svg");
                                                                    $pngPath = asset("qr_codes/{$safeSa}/{$safeDist}/{$safeKio}.png");
                                                                @endphp

                                                                <div class="col-md-6 col-lg-4">
                                                                    <div class="card h-100 shadow-sm kiosque-card" 
                                                                         onclick="showQRCode('{{ $k->code }}', '{{ $k->name }}', '{{ $svgPath }}', '{{ $pngPath }}')"
                                                                         style="cursor: pointer;">
                                                                        <div class="card-body">
                                                                            <div class="d-flex align-items-start">
                                                                                <i class="bi bi-qr-code text-info me-2" style="font-size: 2rem;"></i>
                                                                                <div class="flex-grow-1">
                                                                                    <h6 class="card-title mb-1">{{ $k->name }}</h6>
                                                                                    <p class="card-text small text-muted mb-0">
                                                                                        <i class="bi bi-hash"></i> {{ $k->code }}
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="card-footer bg-transparent border-top-0 pt-0 pb-2 px-3">
                                                                            <small class="text-primary"><i class="bi bi-cursor"></i> Voir QR Code</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div> 

{{-- Modal et Scripts (Identiques à votre code, je les inclus pour être complet) --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i>QR Code - <span id="kiosqueName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3"><strong>Code :</strong> <span id="kiosqueCode" class="badge bg-primary"></span></div>
                <div class="qr-container p-4 bg-light rounded" id="qrContainer">
                    <img id="qrImage" src="" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                </div>
                <div class="alert alert-danger d-none mt-3" id="qrError">
                    <i class="bi bi-exclamation-triangle me-2"></i> Fichier introuvable sur le serveur.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="downloadBtn"><i class="bi bi-download"></i></button>
                <button type="button" class="btn btn-primary" id="printBtn"><i class="bi bi-printer"></i></button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
.kiosque-card { transition: all 0.3s ease; border: 2px solid transparent; }
.kiosque-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important; border-color: #0d6efd; }
.accordion-button:not(.collapsed) { background-color: #e7f1ff; color: #0d6efd; }
.qr-container { min-height: 320px; display: flex; align-items: center; justify-content: center; }
</style>

<script>
const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
function showQRCode(code, name, svgPath, pngPath) {
    document.getElementById('kiosqueName').textContent = name;
    document.getElementById('kiosqueCode').textContent = code;
    const qrImage = document.getElementById('qrImage');
    const qrContainer = document.getElementById('qrContainer');
    const qrError = document.getElementById('qrError');

    qrError.classList.add('d-none');
    qrContainer.classList.remove('d-none');

    // Essayer SVG, si échec essayer PNG, si échec afficher erreur
    qrImage.src = svgPath;
    qrImage.onerror = function() {
        qrImage.src = pngPath;
        qrImage.onerror = function() {
            qrContainer.classList.add('d-none');
            qrError.classList.remove('d-none');
        };
    };

    // Configuration boutons (Download / Print) identique à votre code...
    document.getElementById('downloadBtn').onclick = () => {
        const link = document.createElement('a');
        link.href = qrImage.src;
        link.download = `QR_${code}.png`; 
        link.click();
    };
    document.getElementById('printBtn').onclick = () => {
        const win = window.open('', '_blank');
        win.document.write(`<html><body style="text-align:center;"><h2>${name}</h2><img src="${qrImage.src}" style="max-width:300px"/></body></html>`);
        win.document.close();
        setTimeout(() => { win.print(); win.close(); }, 250);
    };
    qrModal.show();
}
function expandAll() { document.querySelectorAll('.accordion-collapse').forEach(e => new bootstrap.Collapse(e, { show: true })); }
function collapseAll() { document.querySelectorAll('.accordion-collapse.show').forEach(e => new bootstrap.Collapse(e, { hide: true })); }
</script>
@endsection