document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterButton = document.getElementById('filterButton');
    const stores = document.querySelectorAll('.account-item');

    // Fonction de recherche
    function filterStores(searchValue) {
        const trimmedSearch = searchValue.trim().toLowerCase();
        
        stores.forEach(store => {
            const name = store.querySelector('.account-name').textContent.toLowerCase();
            const type = store.querySelector('.account-role').textContent.toLowerCase();
            const identifier = store.querySelector('.account-store').textContent.toLowerCase();

            const matches = name.includes(trimmedSearch) || 
                          type.includes(trimmedSearch) || 
                          identifier.includes(trimmedSearch);

            store.style.display = matches ? '' : 'none';
        });

        // Afficher un message si aucun résultat
        const visibleStores = document.querySelectorAll('.account-item[style=""]');
        const noResultsMessage = document.getElementById('no-results');
        
        if (visibleStores.length === 0 && trimmedSearch !== '') {
            if (!noResultsMessage) {
                const message = document.createElement('p');
                message.id = 'no-results';
                message.className = 'no-accounts';
                message.textContent = 'Aucun magasin trouvé pour cette recherche';
                document.querySelector('.account-list').appendChild(message);
            }
        } else if (noResultsMessage) {
            noResultsMessage.remove();
        }
    }

    // Événements de recherche
    searchInput.addEventListener('input', function() {
        filterStores(this.value);
    });

    filterButton.addEventListener('click', function(e) {
        e.preventDefault();
        filterStores(searchInput.value);
    });
});

function editStore(id) {
    if (!id) return;
    
    window.location.href = `/stockocesi/admin/edit-store/${id}`;
}

function deleteStore(id) {
    if (!id) return;

    if (confirm('Êtes-vous sûr de vouloir supprimer ce magasin ?')) {
        fetch(`/stockocesi/admin/delete-store/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const storeElement = document.querySelector(`[data-id="${id}"]`);
                if (storeElement) {
                    storeElement.remove();
                    
                    // Vérifier s'il reste des magasins
                    const remainingStores = document.querySelectorAll('.account-item');
                    if (remainingStores.length === 0) {
                        location.reload(); // Recharger si plus de magasins
                    } else {
                        // Afficher un message de succès temporaire
                        const message = document.createElement('div');
                        message.className = 'alert alert-success';
                        message.textContent = 'Magasin supprimé avec succès';
                        document.querySelector('main').insertBefore(message, document.querySelector('.search-container'));
                        setTimeout(() => message.remove(), 3000);
                    }
                }
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert(`Erreur lors de la suppression du magasin: ${error.message}`);
        });
    }
}