document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addProductModal');
    const addButton = document.getElementById('openAddProductModal');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('addProductForm');

    // Vérifier si les éléments existent
    if (!modal || !addButton || !closeBtn || !form) {
        console.error('Éléments manquants dans le DOM');
        return;
    }

    // Ouvrir le modal
    addButton.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'block';
        form.reset(); // Réinitialiser le formulaire
    });

    // Fermer le modal avec le bouton X
    closeBtn.addEventListener('click', function() {
        closeAddProductModal();
    });

    // Fermer le modal en cliquant à l'extérieur
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeAddProductModal();
        }
    });

    // Validation des champs du formulaire
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateField(this);
        });
    });

    // Gérer la soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Vérifier tous les champs avant l'envoi
        let isValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            return;
        }

        const formData = new FormData(form);
        
        // Ajout de l'ID du magasin
        formData.append('store_id', document.body.dataset.storeId);

        fetch('/stockocesi/stock/add', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Produit ajouté avec succès', 'success');
                closeAddProductModal();
                location.reload();
            } else {
                showNotification(data.message || 'Erreur lors de l\'ajout du produit', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Une erreur est survenue lors de l\'ajout du produit', 'error');
        });
    });
});

// Fonction de validation des champs
function validateField(field) {
    const errorDiv = field.nextElementSibling?.classList.contains('error-message') 
        ? field.nextElementSibling 
        : document.createElement('div');
    
    if (!errorDiv.classList.contains('error-message')) {
        errorDiv.classList.add('error-message');
        field.parentNode.insertBefore(errorDiv, field.nextSibling);
    }

    let isValid = true;
    errorDiv.textContent = '';

    if (!field.value.trim()) {
        errorDiv.textContent = 'Ce champ est requis';
        isValid = false;
    } else if (field.type === 'number') {
        const value = parseFloat(field.value);
        if (value < 0) {
            errorDiv.textContent = 'La valeur doit être positive';
            isValid = false;
        }
    }

    field.classList.toggle('invalid', !isValid);
    errorDiv.style.display = isValid ? 'none' : 'block';
    return isValid;
}

// Fonction pour fermer le modal
function closeAddProductModal() {
    const modal = document.getElementById('addProductModal');
    if (modal) {
        modal.style.display = 'none';
        const form = document.getElementById('addProductForm');
        if (form) {
            form.reset();
            // Supprimer les messages d'erreur
            form.querySelectorAll('.error-message').forEach(error => {
                error.style.display = 'none';
            });
            form.querySelectorAll('.invalid').forEach(field => {
                field.classList.remove('invalid');
            });
        }
    }
}

// ...existing code...

// Fonction pour afficher les notifications
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.textContent = message;
    document.body.appendChild(notification);

    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // Suppression automatique après 3 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Mise à jour de la gestion des erreurs dans le gestionnaire de soumission
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
            showNotification('Produit ajouté avec succès', 'success');
            closeAddProductModal();
            location.reload();
        } else {
            showNotification(data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Une erreur est survenue', 'error');
    });
});