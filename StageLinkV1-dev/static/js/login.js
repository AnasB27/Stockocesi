document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les icônes Feather
    feather.replace();
    
    // Gestion de l'affichage/masquage du mot de passe
    const eye = document.querySelector(".feather-eye");
    const eyeoff = document.querySelector(".feather-eye-off");
    const passwordField = document.querySelector("input[type=password]");
    
    if (eye) {
        eye.addEventListener("click", () => {
            eye.style.display = "none";
            eyeoff.style.display = "block";
            passwordField.type = "text";
        });
    }

    if (eyeoff) {
        eyeoff.addEventListener("click", () => {
            eyeoff.style.display = "none";
            eye.style.display = "block";
            passwordField.type = "password";
        });
    }
});
