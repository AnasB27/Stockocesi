document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addProductModal');
    const addButton = document.querySelector('.add-product-button');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('addProductForm');

    // Ouvrir le modal
    addButton.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'block';
    });

    // Fermer le modal
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Fermer le modal en cliquant à l'extérieur
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Gérer la soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        fetch('/stockocesi/stock/add', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page pour afficher le nouveau produit
                location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    });
});

// Fonction pour fermer le modal
function closeAddProductModal() {
    const modal = document.getElementById('addProductModal');
    modal.style.display = 'none';
}