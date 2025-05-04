document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.stock-item').forEach(row => {

                if (row && typeof row.textContent === 'string') {
                    row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
                }
            });
        });
    }
});

function confirmDelete(productId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        fetch(`/stockocesi/store/delete-product/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-product-id="${productId}"]`).remove();
            } else {
                alert('Erreur lors de la suppression du produit');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la suppression du produit');
        });
    }
}

function editProduct(productId) {
    window.location.href = `/stockocesi/store/edit-product/${productId}`;
}

function showHistory(productId) {
    window.location.href = `/stockocesi/store/product-history/${productId}`;
}