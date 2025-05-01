document.addEventListener('DOMContentLoaded', function() {
    // Gestion du champ de fichier pour la candidature
    const cvInput = document.getElementById("cv");
    if (cvInput) {
        cvInput.addEventListener("change", function () {
            const fileName = this.files.length > 0 ? this.files[0].name : "Aucun fichier sélectionné";
            document.getElementById("file-name").textContent = fileName;
        });
    }

    // Gestion des modales
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    const editBtn = document.getElementById('editBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const closeEditBtn = document.querySelector('.close-edit');
    const closeDeleteBtn = document.querySelector('.close-delete');
    const cancelEditBtn = document.querySelector('.cancel-edit-btn');
    const cancelDeleteBtn = document.querySelector('.cancel-delete-btn');
    
    // Ouvrir le modal de modification
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            console.log('Bouton de modification cliqué');
            editModal.style.display = 'block';
        });
    }
    
    // Ouvrir le modal de suppression
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            console.log('Bouton de suppression cliqué');
            deleteModal.style.display = 'block';
        });
    }
    
    // Fermer le modal de modification
    if (closeEditBtn) {
        closeEditBtn.addEventListener('click', function() {
            editModal.style.display = 'none';
        });
    }
    
    // Fermer le modal de modification avec le bouton Annuler
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            editModal.style.display = 'none';
        });
    }
    
    // Fermer le modal de suppression
    if (closeDeleteBtn) {
        closeDeleteBtn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
    }
    
    // Fermer le modal de suppression avec le bouton Annuler
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
    }
    
    // Fermer les modals en cliquant en dehors
    window.addEventListener('click', function(event) {
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == deleteModal) {
            deleteModal.style.display = 'none';
        }
    });
    
    // Gestion du bouton pour afficher le formulaire de candidature
    const btnShowForm = document.getElementById("btn-show-form");
    const formCandidature = document.getElementById("form-candidature");
    const btnCancel = document.getElementById("btn-cancel");
    
    if (btnShowForm) {
        btnShowForm.addEventListener("click", function() {
            formCandidature.style.display = "block";
            this.style.display = "none";
        });
    }
    
    if (btnCancel) {
        btnCancel.addEventListener("click", function() {
            formCandidature.style.display = "none";
            btnShowForm.style.display = "block";
        });
    }
});
