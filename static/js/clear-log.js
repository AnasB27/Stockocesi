function confirmClearLogs() {
    if (confirm("Êtes-vous sûr de vouloir effacer tout l'historique ? Cette action est irréversible.")) {
        fetch('/stockocesi/admin/clear-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.log-list').innerHTML = '<p class="no-logs">Aucune activité enregistrée.</p>';
            } else {
                alert('Une erreur est survenue lors de l\'effacement de l\'historique.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'effacement de l\'historique.');
        });
    }
}