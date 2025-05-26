document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

function initializeApp() {
    initializeModal();
    initializeCategoryHandlers();
    setupGlobalErrorHandler();
}

function initializeCategoryHandlers() {
    const categorySelect = document.getElementById('categorySelect');
    const subcategorySelect = document.getElementById('subcategorySelect');
    const storeType = document.body.dataset.storeType;
    const userRole = document.body.dataset.role;

    if (!subcategorySelect) return;

    const allSubcategories = Array.from(subcategorySelect.querySelectorAll('option[data-main-category]'));

    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            const selectedCategory = categorySelect.options[categorySelect.selectedIndex];
            const mainCategory = selectedCategory.dataset.storeType || selectedCategory.dataset.mainCategory || storeType;
            updateSubcategories(subcategorySelect, allSubcategories, mainCategory, userRole, storeType);
        });

        // Mise à jour initiale si une valeur existe
        if (categorySelect.value) {
            categorySelect.dispatchEvent(new Event('change'));
        }
    } else {
        // Si pas de select catégorie (Manager), filtrer selon storeType
        updateSubcategories(subcategorySelect, allSubcategories, storeType, userRole, storeType);
    }
}

function updateSubcategories(select, allOptions, mainCategory, userRole, storeType) {
    select.innerHTML = '<option value="">Sélectionner une sous-catégorie</option>';
    const relevantOptions = allOptions.filter(option => {
        const optionCategory = option.dataset.mainCategory;
        return userRole === 'Admin' || optionCategory === mainCategory || optionCategory === storeType;
    });
    relevantOptions.forEach(option => select.appendChild(option.cloneNode(true)));
}

function initializeModal() {
    const modal = document.getElementById('addProductModal');
    const addButton = document.getElementById('openAddProductModal');
    const form = document.getElementById('addProductForm');

    if (!modal || !addButton || !form) {
        console.error('Éléments du modal manquants');
        return;
    }

    let closeBtn = modal.querySelector('.close');
    if (!closeBtn) {
        closeBtn = document.createElement('span');
        closeBtn.className = 'close';
        closeBtn.innerHTML = '&times;';
        modal.querySelector('.modal-content').prepend(closeBtn);
    }

    addButton.addEventListener('click', (e) => {
        e.preventDefault();
        openModal(modal, form);
    });

    closeBtn.addEventListener('click', () => closeAddProductModal());

    window.addEventListener('click', (e) => {
        if (e.target === modal) closeAddProductModal();
    });

    form.addEventListener('submit', handleFormSubmit);
    setupFormValidation(form);
}

function setupFormValidation(form) {
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        ['input', 'blur'].forEach(eventType => {
            input.addEventListener(eventType, () => validateField(input));
        });
    });
}

function openModal(modal, form) {
    modal.style.display = 'block';
    form.reset();
    clearFormErrors(form);
    requestAnimationFrame(() => {
        modal.classList.add('show');
        const firstInput = form.querySelector('input, select');
        if (firstInput) firstInput.focus();
    });
}

function handleFormSubmit(e) {
    e.preventDefault();
    const form = e.target;

    if (!validateForm(form)) {
        showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
        return;
    }

    submitForm(form);
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    return Array.from(requiredFields).every(field => validateField(field));
}

function validateField(field) {
    const validations = {
        text: value => value.trim().length > 0,
        number: value => !isNaN(parseFloat(value)) && parseFloat(value) >= 0,
        'select-one': value => value !== ''
    };

    const errorMessages = {
        text: 'Ce champ est requis',
        number: 'Veuillez entrer un nombre positif',
        'select-one': 'Veuillez sélectionner une option'
    };

    const value = field.value;
    const isValid = validations[field.type]?.(value) ?? true;
    const errorMessage = isValid ? '' : errorMessages[field.type] || 'Champ invalide';

    updateFieldValidationUI(field, isValid, errorMessage);
    return isValid;
}

function updateFieldValidationUI(field, isValid, errorMessage) {
    const errorDiv = field.nextElementSibling?.classList.contains('error-message')
        ? field.nextElementSibling
        : createErrorDiv(field);

    field.classList.toggle('invalid', !isValid);
    errorDiv.textContent = errorMessage;
    errorDiv.style.display = isValid ? 'none' : 'block';
}

function createErrorDiv(field) {
    const errorDiv = document.createElement('div');
    errorDiv.classList.add('error-message');
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
    return errorDiv;
}

function submitForm(form) {
    const formData = new FormData(form);

    fetch('/stockocesi/stock/add', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(handleFormResponse)
    .catch(handleFormError);
}

function handleResponse(response) {
    if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
    }
    return response.json();
}

function handleFormResponse(data) {
    if (data.success) {
        showNotification('Produit ajouté avec succès', 'success');
        closeAddProductModal();
        setTimeout(() => location.reload(), 1500);
    } else {
        showNotification(data.message || 'Erreur lors de l\'ajout du produit', 'error');
    }
}

function handleFormError(error) {
    console.error('Erreur:', error);
    showNotification('Une erreur est survenue lors de l\'ajout du produit', 'error');
}

function closeAddProductModal() {
    const modal = document.getElementById('addProductModal');
    if (!modal) return;

    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        const form = document.getElementById('addProductForm');
        if (form) {
            form.reset();
            clearFormErrors(form);
        }
    }, 300);
}

function clearFormErrors(form) {
    form.querySelectorAll('.error-message').forEach(error => error.remove());
    form.querySelectorAll('.invalid').forEach(field => field.classList.remove('invalid'));
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.textContent = message;
    
    // Supprimer les anciennes notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    document.body.appendChild(notification);
    requestAnimationFrame(() => {
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    });
}

function setupGlobalErrorHandler() {
    window.addEventListener('error', (event) => {
        console.error('Erreur globale:', event.error);
        showNotification('Une erreur inattendue est survenue', 'error');
    });
}