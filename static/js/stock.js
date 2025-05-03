document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la recherche
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', filterProducts);

    // Gestion des filtres
    const filterButton = document.getElementById('filterButton');
    const filterPanel = document.getElementById('filterPanel');
    filterButton.addEventListener('click', () => {
        filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('applyFilters').addEventListener('click', filterProducts);
});

function filterProducts() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const categoryValue = document.getElementById('categoryFilter').value;
    const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;

    document.querySelectorAll('.stock-item').forEach(item => {
        const productName = item.querySelector('.product-info span:first-child').textContent.toLowerCase();
        const category = item.dataset.category;
        const price = parseFloat(item.querySelector('.product-info span:last-child').textContent.match(/[\d,]+/)[0].replace(',', '.'));

        const matchesSearch = productName.includes(searchValue);
        const matchesCategory = !categoryValue || category === categoryValue;
        const matchesPrice = price >= minPrice && price <= maxPrice;

        item.style.display = matchesSearch && matchesCategory && matchesPrice ? 'flex' : 'none';
    });
}

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

function showProductHistory(productId) {
    window.location.href = `/stockocesi/store/product-history/${productId}`;
}