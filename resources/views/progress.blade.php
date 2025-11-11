@extends('dashboard.main')

@section('content')
<div class="container text-center py-5">
    <h3>📦 Génération des QR Codes en cours...</h3>
    <p id="message">Initialisation...</p>

    <div class="progress my-4" style="height: 25px;">
        <div id="progress-bar"
             class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar"
             style="width: 0%">0%</div>
    </div>

    <p id="details">
        <span id="processed">0</span> / <span id="total">0</span> Kiosques traités
    </p>
    <p id="time-remaining" class="text-muted">Temps restant estimé: N/A</p>

    <div id="done" class="alert alert-success d-none">
        ✅ Le traitement est terminé !
        <br>
        <a href="/telecharger-qr/{{ $jobId }}" class="btn btn-primary mt-3">
            📥 Télécharger le ZIP
        </a>
    </div>
</div>

<script>
    const batchId = "{{ $jobId }}";
    let startTime = Date.now();
    let initialTotal = 0;

    async function checkProgress() {
        try {
            const response = await fetch(`/batch-progress/${batchId}`);
            if (!response.ok) {
                console.error("Erreur lors de la récupération de la progression.");
                return;
            }
            
            const data = await response.json();

            const bar = document.getElementById('progress-bar');
            const message = document.getElementById('message');
            const details = document.getElementById('details');
            const processedSpan = document.getElementById('processed');
            const totalSpan = document.getElementById('total');
            const timeRemainingP = document.getElementById('time-remaining');
            const done = document.getElementById('done');

            if (data.totalJobs > 0 && initialTotal === 0) {
                initialTotal = data.totalJobs;
                totalSpan.innerText = initialTotal;
            }

            processedSpan.innerText = data.processedJobs;
            bar.style.width = data.progress + '%';
            bar.innerText = data.progress + '%';

            if (data.progress > 0) {
                const elapsedTime = (Date.now() - startTime) / 1000;
                const jobsPerSecond = data.processedJobs / elapsedTime;
                const remainingJobs = data.totalJobs - data.processedJobs;
                const remainingSeconds = Math.round(remainingJobs / jobsPerSecond);

                if (isFinite(remainingSeconds) && remainingSeconds > 0) {
                    const minutes = Math.floor(remainingSeconds / 60);
                    const seconds = remainingSeconds % 60;
                    timeRemainingP.innerText = `Temps restant estimé: ${minutes}m ${seconds}s`;
                } else {
                    timeRemainingP.innerText = "Temps restant estimé: Calcul en cours...";
                }
            }

            if (data.finished) {
                done.classList.remove('d-none');
                bar.classList.remove('progress-bar-animated');
                bar.style.width = '100%';
                bar.innerText = '100%';
                timeRemainingP.innerText = "Terminé !";
                message.innerText = "Tous les QR codes ont été générés.";
                clearInterval(interval);
            } else if (data.failed) {
                message.innerText = "Une erreur est survenue.";
                bar.classList.add('bg-danger');
                bar.classList.remove('progress-bar-animated');
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
