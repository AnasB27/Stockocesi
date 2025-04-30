function unlikeFromWishlist(button, offreId, event) {
    // Empêcher la propagation de l'événement
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Envoyer une requête AJAX pour unlike l'offre
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?route=toggle_like&offre_id=' + offreId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Animation de disparition
                    var offreItem = document.getElementById('offre-' + offreId);
                    if (offreItem) {
                        offreItem.style.transition = 'opacity 0.5s ease';
                        offreItem.style.opacity = '0';
                        
                        // Supprimer l'élément après l'animation
                        setTimeout(function() {
                            offreItem.remove();
                            
                            // Vérifier s'il reste des offres
                            var remainingOffres = document.querySelectorAll('.offre-item');
                            if (remainingOffres.length === 0) {
                                // Aucune offre restante, afficher un message
                                var container = document.querySelector('.offres-container');
                                if (container) {
                                    container.innerHTML = '<div class="message">Vous n\'avez pas encore aimé d\'offre.</div>';
                                }
                            }
                        }, 500);
                    }
                }
            } catch (e) {
                console.error('Erreur lors du parsing de la réponse:', e);
            }
        }
    };
    xhr.send();
    
    return false;
}
