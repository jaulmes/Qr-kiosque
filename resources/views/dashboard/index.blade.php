@extends('dashboard.main')

@section('content')
<div class="container-fluid my-3">

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('transaction.index') }}" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
            <h5 class="mb-0 ms-2">Tableau de bord</h5>
        </div>

    </div>

    {{-- KPI Row --}}
    <div class="row g-3">
        {{-- Solde comptes --}}
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted">Montant disponible</small>
                            <h3 class="mt-1 mb-0">
                                <span class="badge bg-warning text-dark">
                                    {{ $montant  }}
                                </span>
                                <small class="text-muted ms-2">FCFA</small>
                            </h3>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-wallet fa-2x text-info"></i>
                        </div>
                    </div>

                    <p class="text-muted small mt-3 mb-2">Montant total disponible dans tous les comptes</p>

                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <a href="{{ route('dashboard.compte') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-circle-right me-1"></i> Voir comptes
                        </a>
                        <small class="text-muted">Mise à jour : {{ now()->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Valeur stock --}}
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted">Valeur totale produits en stock</small>
                            <h5 class="mt-1 mb-1 fw-bold">Solergy - Stock</h5>
                        </div>
                        <div>
                            <i class="fas fa-boxes fa-2x text-success"></i>
                        </div>
                    </div>

                    <ul class="list-unstyled mt-3 mb-2">
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Total achats :</span>
                            <strong>{{ $total_achat_formate ?? number_format($total_achat ?? 0, 0, ',', ' ') }}</strong>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Total ventes :</span>
                            <strong>{{ $total_vente_formate ?? number_format($total_vente ?? 0, 0, ',', ' ') }}</strong>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Total technicien :</span>
                            <strong>{{ $total_technicien_formate ?? number_format($total_technicien ?? 0, 0, ',', ' ') }}</strong>
                        </li>
                    </ul>

                    <div class="mt-3 d-flex justify-content-between">
                        <small class="text-muted">Calculé sur le stock courant</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bénéfice du mois --}}
        <div class="col-12 col-md-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted">Bénéfice du mois (produits)</small>
                            <h4 class="mt-1 mb-0 text-success">
                                {{ $benefice_formate ?? number_format($benefice ?? 0, 0, ',', ' ') }} <small class="text-muted">FCFA</small>
                            </h4>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-2x text-primary"></i>
                        </div>
                    </div>

                    <p class="text-muted small mt-3 mb-2">Bénéfice net calculé sur les ventes de produits pour le mois en cours.</p>

                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark">Mois courant</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

   

</div>
@endsection

@section('javascript')

@endsection
