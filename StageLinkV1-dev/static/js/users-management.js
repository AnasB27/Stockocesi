// Fonctions pour la gestion des utilisateurs dans le dashboard

// Fonction pour éditer un utilisateur
function editUser(userId) {
    // Récupérer les données de l'utilisateur via une requête AJAX
    fetch(`index.php?route=get_user&id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const user = data.user;
                
                // Marquer visuellement la ligne en cours d'édition
                document.querySelectorAll('.users-table tr').forEach(tr => {
                    tr.classList.remove('editing');
                });
                
                // Trouver la ligne correspondant à l'utilisateur
                const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
                
                if (userRow) {
                    userRow.classList.add('editing');
                }

                // Création du formulaire de modification
                const formHtml = `
                <div class="modal-overlay" id="editUserModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Modifier l'utilisateur</h3>
                            <button type="button" class="close-modal" onclick="closeEditModal()">&times;</button>
                        </div>
                        <form id="editUserForm" action="index.php?route=traiter_utilisateur" method="POST" class="form-utilisateur">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="${user.id}">
                            
                            <div class="form-group">
                                <label for="nom">Nom</label>
                                <input type="text" id="nom" name="nom" value="${user.nom}" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" name="prenom" value="${user.prenom}" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="${user.email}" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="mot_de_passe">Mot de passe <small>(Laissez vide pour conserver l'ancien)</small></label>
                                <input type="password" id="mot_de_passe" name="mot_de_passe">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Rôle</label>
                                <select id="role" name="role" required>
                                    <option value="ETUDIANT" ${user.role === 'ETUDIANT' ? 'selected' : ''}>Étudiant</option>
                                    <option value="PILOTE" ${user.role === 'PILOTE' ? 'selected' : ''}>Pilote</option>
                                </select>
                            </div>
                            
                            <div class="form-action-buttons">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>`;

                // Créer et ajouter le modal au document
                const modalContainer = document.createElement('div');
                modalContainer.innerHTML = formHtml;
                document.body.appendChild(modalContainer.firstElementChild);

                // Ajouter les écouteurs d'événements pour fermer le modal
                document.addEventListener('keydown', handleEscapeKey);
                document.querySelector('.modal-overlay').addEventListener('click', handleOutsideClick);

                // Ajouter la classe modal-open au body
                document.body.classList.add('modal-open');
            } else {
                addAlert('error', data.message || 'Une erreur est survenue lors de la récupération des données.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            addAlert('error', 'Une erreur est survenue lors de la communication avec le serveur.');
        });
}

// Fonction pour supprimer un utilisateur
function deleteUser(userId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.")) {
        fetch('index.php?route=traiter_utilisateur', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprimer la ligne du tableau
                const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (userRow) {
                    userRow.remove();
                }
                addAlert('success', 'Utilisateur supprimé avec succès.');
            } else {
                addAlert('error', data.message || 'Une erreur est survenue lors de la suppression.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            addAlert('error', 'Une erreur est survenue lors de la communication avec le serveur.');
        });
    }
}

// Fonctions utilitaires pour la gestion des modals
function createModalContainer() {
    const container = document.createElement('div');
    container.id = 'modalContainer';
    document.body.appendChild(container);
    return container;
}

function closeEditModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) {
        // Ajouter la classe d'animation de fermeture
        modal.classList.add('closing');
        
        // Retirer la classe modal-open du body
        document.body.classList.remove('modal-open');
        
        // Attendre la fin de l'animation avant de supprimer le modal
        setTimeout(() => {
            modal.remove();
            
            // Retirer les écouteurs d'événements
            document.removeEventListener('keydown', handleEscapeKey);
        }, 200);
        
        // Retirer la classe editing de toutes les lignes
        document.querySelectorAll('.users-table tr').forEach(tr => {
            tr.classList.remove('editing');
        });
    }
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
}

function handleOutsideClick(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeEditModal();
    }
}

// Fonction pour ajouter une alerte
function addAlert(type, message) {
    const alertsContainer = document.querySelector('.alerts-container') || createAlertsContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    alertsContainer.appendChild(alert);
    
    // Faire disparaître l'alerte après 5 secondes
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
            // Si c'était la dernière alerte, supprimer le conteneur
            if (alertsContainer.children.length === 0) {
                alertsContainer.remove();
            }
        }, 300);
    }, 5000);
}

function createAlertsContainer() {
    const container = document.createElement('div');
    container.className = 'alerts-container';
    document.body.appendChild(container);
    return container;
}

// Ajouter des données d'attribut user-id pour faciliter la sélection
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter data-user-id à chaque ligne d'utilisateur
    document.querySelectorAll('.users-table tbody tr').forEach(tr => {
        const userId = tr.querySelector('td:first-child').textContent;
        tr.setAttribute('data-user-id', userId);
    });
    
    // Polyfill pour Element.closest()
    if (!Element.prototype.closest) {
        Element.prototype.closest = function(s) {
            var el = this;
            do {
                if (el.matches(s)) return el;
                el = el.parentElement || el.parentNode;
            } while (el !== null && el.nodeType === 1);
            return null;
        };
    }
});
