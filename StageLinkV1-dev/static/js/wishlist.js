function toggleLikeSimple(button, offreId, event) {
    // Empêcher la propagation de l'événement
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // Référence au SVG
    const svg = button.querySelector('svg');
    
    // Vérifier si le bouton a déjà la classe "liked"
    const isLiked = button.classList.contains('liked');
    
    // Changer visuellement l'état immédiatement (pour une réponse instantanée)
    if (isLiked) {
        button.classList.remove('liked');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', '#000000');
    } else {
        button.classList.add('liked');
        svg.setAttribute('fill', 'red');
        svg.setAttribute('stroke', 'red');
    }

    // Utiliser fetch pour une requête AJAX plus fiable
    fetch(`index.php?route=toggle_like&offre_id=${offreId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Like/unlike action response:', data);
        
        // Si nous sommes sur la page wishlist et que nous avons unliké une offre, la supprimer de l'affichage
        if (window.location.href.includes('route=wishlist') && isLiked) {
            const offreContainer = button.closest('.offre-link');
            if (offreContainer) {
                // Animation de disparition
                offreContainer.style.transition = 'opacity 0.5s ease';
                offreContainer.style.opacity = '0';
                
                // Supprimer l'élément après l'animation
                setTimeout(() => {
                    offreContainer.remove();
                    
                    // Vérifier s'il reste des offres
                    const remainingOffres = document.querySelectorAll('.offre-link');
                    if (remainingOffres.length === 0) {
                        // Aucune offre restante, afficher un message
                        const container = document.querySelector('.offres-container');
                        if (container) {
                            container.innerHTML = '<div class="message">Vous n\'avez pas encore aimé d\'offre.</div>';
                        }
                    }
                }, 500);
            }
        }
    })
    .catch(error => {
        console.error('Error toggling like:', error);
        // En cas d'erreur, revenir à l'état précédent
        if (isLiked) {
            button.classList.add('liked');
            svg.setAttribute('fill', 'red');
            svg.setAttribute('stroke', 'red');
        } else {
            button.classList.remove('liked');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', '#000000');
        }
    });
    
    return false; // Important pour bloquer la propagation
}

// Fonction alternative si la première échoue
function toggleLike(event, button, offreId) {
    event.preventDefault();
    event.stopPropagation();
    
    return toggleLikeSimple(button, offreId, event);
}
