@extends('dashboard.main')

@section('content')
<div class="container text-center py-5">
    <h3>📦 Traitement de votre fichier en cours...</h3>
    <p id="overall-message" class="text-muted">Initialisation...</p>

    <!-- Reading Progress -->
    <div class="mt-4">
        <h5>1. Lecture du fichier Excel</h5>
        <div class="progress" style="height: 25px;">
            <div id="reading-progress-bar"
                 class="progress-bar bg-info progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: 0%">0%</div>
        </div>
        <p id="reading-details" class="mt-2"></p>
    </div>

    <!-- Generating Progress -->
    <div class="mt-4">
        <h5>2. Génération des codes QR</h5>
        <div class="progress" style="height: 25px;">
            <div id="generating-progress-bar"
                 class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: 0%">0%</div>
        </div>
        <p id="generating-details" class="mt-2"></p>
    </div>

    <!-- Time Remaining -->
    <div id="time-remaining-container" class="mt-4" style="display: none;">
        <p><strong>Temps restant estimé :</strong> <span id="time-remaining">--</span></p>
    </div>

    <div id="done" class="alert alert-success d-none mt-4">
        ✅ Le traitement est terminé !
        <br>
        <a href="/telecharger-qr/{{ $jobId }}" class="btn btn-primary mt-3">
            📥 Télécharger tous les codes QR (.zip)
        </a>
    </div>
</div>

<script>
    const jobId = "{{ $jobId }}";

    function formatTime(seconds) {
        if (seconds === null || seconds < 0) return "--";
        if (seconds < 60) return `${seconds} sec`;
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes} min ${remainingSeconds} sec`;
    }

    async function checkProgress() {
        try {
            const response = await fetch(`/progress/${jobId}`);
            if (!response.ok) {
                console.error("Erreur lors de la récupération de la progression.");
                return;
            }
            
            const data = await response.json();

            // Selectors
            const overallMessage = document.getElementById('overall-message');
            const readingBar = document.getElementById('reading-progress-bar');
            const readingDetails = document.getElementById('reading-details');
            const generatingBar = document.getElementById('generating-progress-bar');
            const generatingDetails = document.getElementById('generating-details');
            const timeContainer = document.getElementById('time-remaining-container');
            const timeRemaining = document.getElementById('time-remaining');
            const done = document.getElementById('done');

            // Update UI
            overallMessage.innerText = data.message || "Chargement...";

            // Reading progress
            if (data.reading) {
                readingBar.style.width = `${data.reading.progress}%`;
                readingBar.innerText = `${data.reading.progress}%`;
                readingDetails.innerText = `${data.reading.processed} / ${data.reading.total} lignes lues`;
            }

            // Generating progress
            if (data.generating) {
                generatingBar.style.width = `${data.generating.progress}%`;
                generatingBar.innerText = `${data.generating.progress}%`;
                generatingDetails.innerText = `${data.generating.processed} / ${data.generating.total} codes QR générés`;
            }

            // Time remaining
            if (data.time && data.time.remaining !== null) {
                timeContainer.style.display = 'block';
                timeRemaining.innerText = formatTime(data.time.remaining);
            }

            // Status check
            if (data.status === 'finished') {
                done.classList.remove('d-none');
                readingBar.classList.remove('progress-bar-animated');
                generatingBar.classList.remove('progress-bar-animated');
                timeContainer.style.display = 'none';
                clearInterval(interval);
            } else if (data.status === 'failed') {
                overallMessage.innerText = "Une erreur est survenue: " + data.message;
                readingBar.classList.add('bg-danger');
                generatingBar.classList.add('bg-danger');
                readingBar.classList.remove('progress-bar-animated');
                generatingBar.classList.remove('progress-bar-animated');
                clearInterval(interval);
            }
        } catch (error) {
            console.error('Erreur fetch:', error);
            clearInterval(interval);
        }
    }

    const interval = setInterval(checkProgress, 1000); // Check every second
    checkProgress(); // Initial check
</script>
@endsection
