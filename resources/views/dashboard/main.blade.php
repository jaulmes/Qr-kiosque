
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @yield("extra-meta")
  <title>Solergy Solutions</title>
  
  <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
  <link rel="manifest" href="../favicon/site.webmanifest">
  <meta name="theme-color" content="#ffffff">
  
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
  
  @yield('head')
  @livewireStyles
</head>
<style>
  body {
    padding-top: 56px; /* Ajustez cette valeur selon la hauteur de votre navbar */
  }

  /* --- Styles MoMo Ajoutés --- */
  :root {
    --momo-blue: #004f6f; /* Bleu foncé du logo MoMo */
    --momo-yellow: #ffcb00; /* Jaune vif du logo MoMo */
  }

  /* 1. Navbar */
  .main-header.navbar-dark {
    background-color: var(--momo-blue) !important;
    border-bottom: 1px solid var(--momo-yellow);
  }
  .main-header .navbar-nav .nav-link {
    color: var(--momo-yellow) !important;
  }
  .main-header .navbar-nav .nav-link[data-widget="pushmenu"] i {
    color: var(--momo-yellow) !important;
  }
  .main-header .navbar-nav .nav-link:hover {
    color: #ffffff !important; /* Blanc au survol */
  }

  /* 2. Sidebar */
  .main-sidebar {
     background-color: var(--momo-blue) !important;
  }

  /* Liens de la sidebar (texte blanc par défaut) */
  .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link {
     color: #ffffff;
  }

  /* Lien actif (Fond jaune, texte noir) */
  .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active {
     background-color: var(--momo-yellow) !important;
     color: #000000 !important;
  }
  .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active .nav-icon {
     color: #000000 !important;
  }
  
  /* Lien au survol (Texte jaune) */
  .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link:hover {
     background-color: rgba(255, 255, 255, 0.1); /* Léger fond blanc transparent */
     color: var(--momo-yellow);
  }
  .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link:hover .nav-icon {
     color: var(--momo-yellow);
  }

  /* 3. Footer */
  .main-footer {
    background-color: var(--momo-blue) !important;
    color: #ffffff !important;
    border-top: 1px solid var(--momo-yellow);
  }
  /* --- Fin des Styles MoMo --- */

</style>
<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-dark shadow-sm fixed-top">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item">
          <a href="{{ route('upload.form') }}" class="nav-link">Accueil</a>
        </li>
      </ul>       
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      @include('dashboard.sidebar')
    </aside>
    
    <div class="content-wrapper">
      <section class="content">
        <div class="container-fluid">
             <livewire:notification/>
          @yield('content')
        </div>
      </section>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../plugins/select2/js/select2.full.min.js"></script>
  <script src="../../dist/js/adminlte.min.js"></script>
  
  <script>
    $(function () {
      $('.select2').select2({
        theme: 'bootstrap4'
      });
    });

    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  </script>

  @yield('javascript')
  @livewireScripts
</body>
</html>