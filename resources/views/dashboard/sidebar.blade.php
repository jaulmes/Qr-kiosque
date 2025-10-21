<div>
    <!-- Logo et nom de l'application -->
    <a href="#" class="brand-link d-flex align-items-center p-3" style="background-color: #004f6f; color: #ffcb00;">
        <img src="../logo.png" alt="MTN" class="brand-image img-circle elevation-3 me-2" style="opacity: .9; width: 40px; height: 40px;">
        <span class="brand-text fw-bold">Qr-Kiosque</span>
    </a>

    <!-- Contenu de la sidebar -->
    <div class="sidebar" style="background-color: #004f6f; color: #ffcb00;">
        <!-- Menu de navigation de la sidebar -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Upload File -->
                <li class="nav-item">
                    <a href="{{ route('upload.form') }}" class="nav-link {{ Request::is('/') ? 'active' : '' }}" 
                       style="color: #ffcb00;">
                        <i class="fas fa-upload me-2"></i>
                        <p>Upload File</p>
                    </a>
                </li>

                <!-- Afficher les kiosques -->
                <li class="nav-item">
                    <a href="{{ route('kiosques.list') }}" class="nav-link {{ Request::is('kiosques') ? 'active' : '' }}" 
                       style="color: #ffcb00;">
                        <i class="fas fa-list-alt me-2"></i>
                        <p>Afficher les kiosques</p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- Fin du menu -->
    </div>
    <!-- /.sidebar -->
</div>

<!-- Styles personnalisés pour améliorer l'UI -->
<style>
    /* Sidebar links hover */
    .nav-sidebar .nav-link:hover {
        background-color: #00607f; /* bleu un peu plus clair */
        color: #ffffff !important;
    }

    /* Sidebar link active */
    .nav-sidebar .nav-link.active {
        background-color: #ffcb00; /* jaune vif */
        color: #004f6f !important; /* texte en bleu foncé */
        font-weight: bold;
        border-radius: 0.35rem;
    }

    /* Icons dans la sidebar */
    .nav-sidebar .nav-link i {
        font-size: 1.1rem;
    }

    /* Ajustement du texte */
    .nav-sidebar .nav-link p {
        display: inline-block;
        margin: 0;
    }
</style>
