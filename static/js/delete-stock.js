function deleteStock(stockId, currentQuantity) {
    let qty = prompt("Combien d'unités voulez-vous supprimer ? (max " + currentQuantity + ")");
    if (qty === null) return; // Annulé

    qty = parseInt(qty, 10);
    if (isNaN(qty) || qty <= 0 || qty > currentQuantity) {
        alert("Quantité invalide.");
        return;
    }

    if (!confirm("Supprimer " + qty + " unité(s) du stock ?")) return;

    fetch('/stockocesi/stock/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ stock_id: stockId, quantity: qty })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || "Erreur lors de la suppression.");
        }
    })
    .catch(() => alert("Erreur réseau lors de la suppression."));
}