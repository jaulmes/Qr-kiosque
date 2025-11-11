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
    <p id="eta"></p>

    <div id="done" class="alert alert-success d-none">
        ✅ Le traitement est terminé ! Vous pouvez consulter vos QR codes.
        <br>
        <a href="/download/all-qr" class="btn btn-primary mt-3">
            📥 Télécharger en zip
        </a>
    </div>
</div>

<script>
    const batchId = "{{ $batchId }}";
    let startTime = Date.now();

    async function checkProgress() {
        try {
            const response = await fetch(`/progress/${batchId}/json`);
            if (!response.ok) {
                console.error("Erreur lors de la récupération de la progression.");
                return;
            }
            
            const data = await response.json();

            const bar = document.getElementById('progress-bar');
            const message = document.getElementById('message');
            const details = document.getElementById('details');
            const eta = document.getElementById('eta');
            const done = document.getElementById('done');

            const progress = data.progress;
            bar.style.width = progress + '%';
            bar.innerText = progress + '%';
            
            details.innerText = `${data.processedJobs} / ${data.totalJobs} jobs traités`;

            const elapsedTime = (Date.now() - startTime) / 1000;
            const remainingJobs = data.totalJobs - data.processedJobs;
            const timePerJob = elapsedTime / data.processedJobs;
            const etaSeconds = remainingJobs * timePerJob;

            if (data.processedJobs > 0 && isFinite(etaSeconds)) {
                const minutes = Math.floor(etaSeconds / 60);
                const seconds = Math.floor(etaSeconds % 60);
                eta.innerText = `Temps restant estimé: ${minutes}m ${seconds}s`;
            }

            if (data.finishedAt || progress >= 100) {
                message.innerText = "Tous les QR codes ont été générés";
                done.classList.remove('d-none');
                bar.classList.remove('progress-bar-animated');
                bar.style.width = '100%';
                bar.innerText = '100%';
                eta.innerText = '';
                clearInterval(interval);
            } else if (data.failedJobs > 0) {
                message.innerText = "Une erreur est survenue lors du traitement.";
                bar.classList.add('bg-danger');
                bar.classList.remove('progress-bar-animated');
                eta.innerText = '';
                clearInterval(interval);
            }
        } catch (error) {
            console.error('Erreur fetch:', error);
            clearInterval(interval);
        }
    }

    const interval = setInterval(checkProgress, 1500);

    checkProgress(); 
</script>
@endsection
