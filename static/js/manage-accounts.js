document.addEventListener('DOMContentLoaded', function() {
    // Recherche en temps réel
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const accounts = document.querySelectorAll('.account-item');
        
        accounts.forEach(account => {
            const text = account.textContent.toLowerCase();
            account.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Filtrage
    let currentFilter = 'all';
    const filterButton = document.getElementById('filterButton');
    filterButton.addEventListener('click', function() {
        const filters = ['all', 'admin', 'manager', 'employee'];
        currentFilter = filters[(filters.indexOf(currentFilter) + 1) % filters.length];
        
        const accounts = document.querySelectorAll('.account-item');
        accounts.forEach(account => {
            const role = account.querySelector('.account-role').textContent.toLowerCase();
            if (currentFilter === 'all') {
                account.style.display = '';
            } else {
                account.style.display = role === currentFilter ? '' : 'none';
            }
        });
    });
});

function deleteAccount(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce compte ?')) {
        fetch(`/stockocesi/admin/delete-account/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const accountElement = document.querySelector(`[data-id="${id}"]`);
                if (accountElement) {
                    accountElement.remove();
                }
            } else {
                alert('Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression du compte');
        });
    }
}

function editAccount(id) {
    window.location.href = `/stockocesi/admin/edit-account/${id}`;
}