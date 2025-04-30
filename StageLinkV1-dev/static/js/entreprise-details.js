document.addEventListener('DOMContentLoaded', function() {
    // Cacher les modales au chargement
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.style.display = 'none';
    });
    
    // Formulaire de modification
    var editForm = document.getElementById('editEntrepriseForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            console.log('Soumission du formulaire de modification');
            // Le formulaire est soumis normalement
        });
    }
    
    // Formulaire de suppression
    var deleteForm = document.getElementById('deleteEntrepriseForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            console.log('Soumission du formulaire de suppression');
            // Le formulaire est soumis normalement
        });
    }
});

// Fonctions pour le modal de modification
function openEditModal() {
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Fonctions pour le modal de suppression
function confirmDelete() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Fermer les modals si l'utilisateur clique en dehors
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
    if (event.target == document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
}
