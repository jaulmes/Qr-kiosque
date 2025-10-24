@extends('dashboard.main')

@section('content')
<div class="container text-center py-5">
    <h3>📦 Génération des QR Codes</h3>
    <p id="message">Initialisation...</p>

    <div class="progress my-4" style="height: 25px;">
        <div id="progress-bar"
             class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar"
             style="width: 0%">0%</div>
    </div>

    <p id="details"></p>

    <div id="done" class="alert alert-success d-none">
        ✅ Le traitement est terminé ! Vous pouvez consulter vos QR codes.
        <br>
        <a href="/telecharger-qr/{{ $jobId }}" class="btn btn-primary mt-3">
            📥 Télécharger en zip(cela téléchargera avec les anciens)
        </a>
    </div>
</div>

<script>
    const jobId = "{{ $jobId }}";

    async function checkProgress() {
        try {
            const response = await fetch(`/progress/${jobId}`);
            if (!response.ok) {
                // Gérer les erreurs serveur
                console.error("Erreur lors de la récupération de la progression.");
                return;
            }
            
            const data = await response.json();

            const bar = document.getElementById('progress-bar');
            const message = document.getElementById('message');
            const details = document.getElementById('details');
            const done = document.getElementById('done');

            bar.style.width = data.progress + '%';
            bar.innerText = data.progress + '%';
            message.innerText = data.message;
            
            // Assurer que les valeurs existent avant de les afficher
            details.innerText = `${data.processed ?? 0} / ${data.total ?? 0} kiosques traités`;

            if (data.status === 'finished' || data.progress >= 100) {
                done.classList.remove('d-none');
                bar.classList.remove('progress-bar-animated');
                bar.style.width = '100%'; // Forcer 100% à la fin
                bar.innerText = '100%';
                clearInterval(interval); // Arrêter l'intervalle
            } else if (data.status === 'failed') {
                 // Gérer un échec
                message.innerText = "Une erreur est survenue: " + data.message;
                bar.classList.add('bg-danger');
                bar.classList.remove('progress-bar-animated');
                clearInterval(interval);
            }
        } catch (error) {
            console.error('Erreur fetch:', error);
            // Arrêter si le fetch échoue (ex: page rechargée, serveur mort)
            clearInterval(interval);
        }
    }

    // ✅ CHANGER L'INTERVALLE
    // 2ms est beaucoup trop rapide et va saturer votre serveur.
    // Mettez 1000ms (1 seconde) ou 1500ms.
    const interval = setInterval(checkProgress, 300); 

    // Lancer une première fois immédiatement
    checkProgress(); 
</script>
@endsection
