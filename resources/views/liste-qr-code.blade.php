@extends('dashboard.main')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start mb-3 flex-column flex-md-row">
        
        <div>
            <h2 class="mb-1">📁 Gestion des QR Codes</h2>
            <p class="text-muted mb-0">Super Agent → Distributeur → Kiosque</p>
        </div>
        
        <div class="d-flex gap-2 flex-wrap justify-content-start justify-content-md-end mt-3 mt-md-0">
            
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-primary" onclick="expandAll()">
                    <i class="bi bi-arrows-expand"></i> Tout ouvrir
                </button>
                <button class="btn btn-outline-secondary" onclick="collapseAll()">
                    <i class="bi bi-arrows-collapse"></i> Tout fermer
                </button>
            </div>

            @php
                use Illuminate\Support\Facades\File;

                // Vérifier si le dossier "qr_codes" existe et contient des fichiers
                $qrBasePath = public_path('qr_codes');
                $qrFolderExists = File::exists($qrBasePath) && count(File::allFiles($qrBasePath)) > 0;

                // Vérifier s’il y a des données en base
                $hasData = !$superAgents->isEmpty();
            @endphp

            <div class="btn-group btn-group-sm" role="group">
                <!-- Bouton de téléchargement -->
                <a href="{{ route('download.all.qr') }}"
                class="btn btn-success {{ (!$hasData && !$qrFolderExists) ? 'disabled' : '' }}"
                title="Télécharger tous les QR Codes en ZIP"
                @if(!$hasData && !$qrFolderExists) aria-disabled="true" @endif>
                    <i class="bi bi-download"></i> Télécharger (ZIP)
                </a>

                <!-- Bouton de suppression -->
                <form action="{{ route('kiosques.deleteAll') }}" method="POST"
                    class="d-inline-block"
                    onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer TOUS les kiosques et leurs QR codes ? Cette action est irréversible !')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="btn btn-danger {{ (!$hasData && !$qrFolderExists) ? 'disabled' : '' }}"
                            title="Supprimer tous les kiosques"
                            @if(!$hasData && !$qrFolderExists) disabled @endif>
                        <i class="bi bi-trash"></i> Tout supprimer
                    </button>
                </form>
            </div>


        </div>
    </div> @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($superAgents->isEmpty())
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            Aucun Super Agent trouvé. Veuillez importer des données.
        </div>
    @else
        <div class="accordion" id="superAgentAccordion">
            
            @foreach ($superAgents as $index => $sa)
                <div class="accordion-item border rounded mb-3 shadow-sm">
                    <h2 class="accordion-header" id="heading-sa-{{ $sa->id }}">
                        <button class="accordion-button collapsed bg-light" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse-sa-{{ $sa->id }}" 
                                aria-expanded="false" 
                                aria-controls="collapse-sa-{{ $sa->id }}">
                            
                            <div class="d-flex align-items-center w-100">
                                <i class="bi bi-building text-primary me-3" style="font-size: 1.5rem;"></i>
                                <div class="flex-grow-1">
                                    <strong class="d-block">{{ $sa->name }}</strong>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> {{ $sa->region ?? 'N/A' }} • 
                                        <i class="bi bi-shop"></i> {{ $sa->distributeurs->count() }} distributeur(s) •
                                        <i class="bi bi-boxes"></i> {{ $sa->distributeurs->sum(fn($d) => $d->kiosques->count()) }} kiosque(s)
                                    </small>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-sa-{{ $sa->id }}" 
                         class="accordion-collapse collapse" 
                         aria-labelledby="heading-sa-{{ $sa->id }}" 
                         data-bs-parent="#superAgentAccordion">
                        
                        <div class="accordion-body bg-light">
                            @if($sa->distributeurs->isEmpty())
                                <p class="text-muted mb-0">
                                    <i class="bi bi-inbox"></i> Aucun distributeur
                                </p>
                            @else
                                <div class="accordion" id="distributeurAccordion-{{ $sa->id }}">
                                    @foreach ($sa->distributeurs as $dist)
                                        <div class="accordion-item border rounded mb-2">
                                            <h2 class="accordion-header" id="heading-dist-{{ $dist->id }}">
                                                <button class="accordion-button collapsed" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse-dist-{{ $dist->id }}" 
                                                        aria-expanded="false" 
                                                        aria-controls="collapse-dist-{{ $dist->id }}">
                                                    
                                                    <div class="d-flex align-items-center w-100">
                                                        <i class="bi bi-shop text-success me-3" style="font-size: 1.3rem;"></i>
                                                        <div class="flex-grow-1">
                                                            <strong class="d-block">{{ $dist->name }}</strong>
                                                            <small class="text-muted">
                                                                <i class="bi bi-telephone"></i> {{ $dist->phone ?? 'N/A' }} • 
                                                                <i class="bi bi-box"></i> {{ $dist->kiosques->count() }} kiosque(s)
                                                            </small>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse-dist-{{ $dist->id }}" 
                                                 class="accordion-collapse collapse" 
                                                 aria-labelledby="heading-dist-{{ $dist->id }}" 
                                                 data-bs-parent="#distributeurAccordion-{{ $sa->id }}">
                                                
                                                <div class="accordion-body">
                                                    @if($dist->kiosques->isEmpty())
                                                        <p class="text-muted mb-0">
                                                            <i class="bi bi-inbox"></i> Aucun kiosque
                                                        </p>
                                                    @else
                                                        <div class="row g-3">
                                                            @foreach ($dist->kiosques as $k)
                                                                @php
                                                                    $safeSa = \Illuminate\Support\Str::slug($sa->name, '_');
                                                                    $safeDist = \Illuminate\Support\Str::slug($dist->name, '_');
                                                                    $safeKio = \Illuminate\Support\Str::slug($k->name, '_');
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
                                                                                    <p class="card-text small text-muted mb-1">
                                                                                        <i class="bi bi-hash"></i> {{ $k->code }}
                                                                                    </p>
                                                                                    <p class="card-text small text-muted mb-1">
                                                                                        <i class="bi bi-telephone"></i> {{ $k->phone ?? 'N/A' }}
                                                                                    </p>
                                                                                    <p class="card-text small text-muted mb-0">
                                                                                        <i class="bi bi-pin-map"></i> BV: {{ $k->bv ?? 'N/A' }}
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="card-footer bg-transparent border-top-0 pt-0 pb-2 px-3">
                                                                            <small class="text-primary">
                                                                                <i class="bi bi-cursor"></i> Cliquez pour voir le QR Code
                                                                            </small>
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

</div> <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">
                    <i class="bi bi-qr-code me-2"></i>QR Code - <span id="kiosqueName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <strong>Code :</strong> <span id="kiosqueCode" class="badge bg-primary"></span>
                </div>
                <div class="qr-container p-4 bg-light rounded" id="qrContainer">
                    <img id="qrImage" src="" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                </div>
                <div class="alert alert-danger d-none mt-3" id="qrError">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    QR Code introuvable. Veuillez vérifier que le fichier existe.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="downloadBtn">
                    <i class="bi bi-download me-2"></i>Télécharger
                </button>
                <button type="button" class="btn btn-primary" id="printBtn">
                    <i class="bi bi-printer me-2"></i>Imprimer
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
.kiosque-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.kiosque-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
    border-color: #0d6efd;
}
.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0d6efd;
}
.qr-container {
    min-height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
}
/* Aligner le formulaire de suppression dans le groupe de boutons */
.btn-group-sm > .btn-danger {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}
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

    qrImage.src = svgPath;
    qrImage.onerror = function() {
        qrImage.src = pngPath;
        qrImage.onerror = function() {
            qrContainer.classList.add('d-none');
            qrError.classList.remove('d-none');
        };
    };

    document.getElementById('downloadBtn').onclick = function() {
        const link = document.createElement('a');
        link.href = qrImage.src;
        link.download = `QR_${code}_${name}.${qrImage.src.endsWith('.svg') ? 'svg' : 'png'}`;
        link.click();
    };

    document.getElementById('printBtn').onclick = function() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>QR Code - ${name}</title>
                    <style>
                        body { text-align: center; font-family: Arial; padding: 20px; }
                        img { max-width: 300px; margin: 20px auto; display: block; }
                    </style>
                </head>
                <body>
                    <h2>${name}</h2>
                    <p><strong>Code:</strong> ${code}</p>
                    <img src="${qrImage.src}" alt="QR Code">
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    };

    qrModal.show();
}

function expandAll() {
    document.querySelectorAll('.accordion-collapse').forEach(e => new bootstrap.Collapse(e, { show: true }));
}
function collapseAll() {
    document.querySelectorAll('.accordion-collapse.show').forEach(e => new bootstrap.Collapse(e, { hide: true }));
}
</script>
@endsection