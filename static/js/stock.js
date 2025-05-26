document.addEventListener('DOMContentLoaded', function() {
    // Recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', filterProducts);
    }

    // Filtres
    const filterButton = document.getElementById('filterButton');
    const filterPanel = document.getElementById('filterPanel');
    if (filterButton) {
        filterButton.addEventListener('click', () => {
            if (filterPanel) {
                filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
            }
        });
    }

    const applyFilters = document.getElementById('applyFilters');
    if (applyFilters) {
        applyFilters.addEventListener('click', filterProducts);
    }
});

function filterProducts() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');

    const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
    const categoryValue = categoryFilter ? categoryFilter.value : '';
    const minPrice = minPriceInput ? parseFloat(minPriceInput.value) || 0 : 0;
    const maxPrice = maxPriceInput ? parseFloat(maxPriceInput.value) || Infinity : Infinity;

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

function showHistory(productId) {
    window.location.href = `/stockocesi/store/product-history/${productId}`;
}