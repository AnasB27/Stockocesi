function toggleMenu() {
    const burgerMenu = document.querySelector('.burger-menu');
    const navLinks = document.querySelector('.nav-links');
    
    burgerMenu.classList.toggle('active');
    navLinks.classList.toggle('active');
}
