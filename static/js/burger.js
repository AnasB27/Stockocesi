document.addEventListener('DOMContentLoaded', function() {
    const burgerIcon = document.querySelector('.burger-icon');
    const navMenu = document.querySelector('.nav-menu');

    // Ouvre le menu
    burgerIcon.addEventListener('click', function() {
        navMenu.classList.add('active');
    });

    // Ferme le menu quand on clique sur la croix
    navMenu.addEventListener('click', function(e) {
        if (e.target === this) {
            navMenu.classList.remove('active');
        }
    });
});