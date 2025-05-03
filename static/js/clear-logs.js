function confirmClearLogs() {
    if (confirm("Êtes-vous sûr de vouloir effacer tout l'historique ? Cette action est irréversible.")) {
        fetch('/stockocesi/admin/log', {  // Changé ici
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'clear_logs' })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse non JSON');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'effacement de l\'historique.');
        });
    }
}