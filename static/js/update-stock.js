window.editStock = function(stockId) {
    const row = document.querySelector(`tr[data-id='${stockId}']`);
    if (!row) return;

    document.getElementById('update-stock-id').value = stockId;
    document.getElementById('update-name').value = row.querySelector('[data-label="Nom"]').textContent.trim();
    document.getElementById('update-description').value = row.querySelector('[data-label="Description"]').textContent.trim();
    document.getElementById('update-quantite').value = row.querySelector('[data-label="Quantité"]').textContent.trim();
    document.getElementById('update-prix').value = row.querySelector('[data-label="Prix"]').textContent.replace('€','').replace(',','.').trim();
    document.getElementById('update-seuil-alerte').value = row.querySelector('[data-label="Seuil d\'alerte"]').textContent.trim();

    // Affiche la modale uniquement ici
    document.getElementById('updateStockModal').style.display = 'flex';
};

window.closeUpdateModal = function() {
    document.getElementById('updateStockModal').style.display = 'none';
};

// Empêche la fermeture accidentelle au chargement
document.getElementById('updateStockModal').style.display = 'none';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('updateStockForm');
    if (!form) return;
    form.onsubmit = function(e) {
        e.preventDefault();
        const data = {
            stock_id: document.getElementById('update-stock-id').value,
            name: document.getElementById('update-name').value,
            description: document.getElementById('update-description').value,
            quantite: document.getElementById('update-quantite').value,
            prix: document.getElementById('update-prix').value,
            seuil_alerte: document.getElementById('update-seuil-alerte').value
        };
        fetch('/stockocesi/stock/update', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                alert(resp.message || "Produit modifié !");
                location.reload();
            } else {
                alert(resp.message || "Erreur lors de la modification.");
            }
        })
        .catch(() => alert("Erreur réseau."));
    };
});