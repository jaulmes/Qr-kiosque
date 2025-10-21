@extends('dashboard.main')
@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark-excel" style="font-size: 3rem; color: #198754;"></i>
                            </div>
                            <h2 class="h4 fw-bold mb-2">Importer un fichier Excel</h2>
                            <p class="text-muted">Sélectionnez un fichier contenant les données des kiosques</p>
                        </div>

                        {{-- Messages de succès --}}
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Succès !</strong> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        {{-- Messages d'erreur globaux --}}
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Erreur !</strong> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        {{-- Erreurs de validation --}}
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Erreur de validation :</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('upload.validate') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                            @csrf
                            
                            <div class="mb-4">
                                <label for="fileInput" class="form-label fw-semibold">
                                    <i class="bi bi-paperclip me-1"></i>Fichier Excel
                                </label>
                                <input 
                                    type="file" 
                                    name="file" 
                                    id="fileInput"
                                    class="form-control @error('file') is-invalid @enderror" 
                                    accept=".xlsx,.xls,.csv"
                                    required
                                >
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted mt-1 d-block">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Formats acceptés : .xlsx, .xls, .csv (Max: 10MB)
                                </small>
                            </div>

                            <div id="fileInfo" class="alert alert-info d-none mb-4">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                <strong>Fichier sélectionné :</strong> <span id="fileName"></span>
                                <br>
                                <small><strong>Taille :</strong> <span id="fileSize"></span></small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="bi bi-upload me-2"></i>Importer le fichier
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="window.history.back()">
                                    <i class="bi bi-arrow-left me-2"></i>Retour
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-lightbulb text-warning me-2"></i>Instructions
                            </h6>
                            <ul class="small mb-0 text-muted">
                                <li>Assurez-vous que votre fichier contient les colonnes requises</li>
                                <li>Vérifiez que les données sont au bon format</li>
                                <li>Le fichier ne doit pas dépasser 10MB</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                fileInfo.classList.remove('d-none');
            } else {
                fileInfo.classList.add('d-none');
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importation en cours...';
        });
    </script>
@endsection