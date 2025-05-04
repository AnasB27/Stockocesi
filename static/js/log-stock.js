window.showStockHistoryGeneral = function() {
    const modal = document.getElementById('historyGeneralModal');
    const content = document.getElementById('history-general-content');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center;">Chargement...</div>';
    fetch('/stockocesi/stock/history-general')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                content.innerHTML = `<div class="error-message">${data.message || 'Erreur.'}</div>`;
                return;
            }
            if (!data.history.length) {
                content.innerHTML = '<div>Aucun mouvement enregistré.</div>';
                return;
            }

            const products = [...new Set(data.history.map(item => item.product_name || ''))].filter(Boolean);


            let clearBtn = `<button id="clear-history-btn" style="float:right;margin-bottom:10px;">🗑️ Vider l'historique</button>`;


            let selectHtml = `<label for="history-product-select">Filtrer par produit :</label>
                <select id="history-product-select">
                    <option value="">Tous les produits</option>
                    ${products.map(p => `<option value="${p}">${p}</option>`).join('')}
                </select>`;


            let tableHtml = `<div style="max-height:400px;overflow-y:auto;">
                <table class="stock-table" id="history-table"><thead>
                    <tr><th>Date</th><th>Produit</th><th>Mouvement</th><th>Quantité</th><th>Utilisateur</th><th>Raison</th></tr>
                </thead><tbody>`;
            data.history.forEach(item => {
                tableHtml += `<tr>
                    <td>${item.movement_date}</td>
                    <td>${item.product_name || ''}</td>
                    <td>${item.movement_type || ''}</td>
                    <td>${item.quantity}</td>
                    <td>${item.user_id || ''}</td>
                    <td>${item.reason || ''}</td>
                </tr>`;
            });
            tableHtml += '</tbody></table></div>';

            content.innerHTML = clearBtn + selectHtml + tableHtml;


            document.getElementById('history-product-select').addEventListener('change', function() {
                const value = this.value;
                document.querySelectorAll('#history-table tbody tr').forEach(row => {
                    const product = row.children[1].textContent;
                    row.style.display = !value || product === value ? '' : 'none';
                });
            });


            document.getElementById('clear-history-btn').addEventListener('click', function() {
                if (confirm('Voulez-vous vraiment vider tout l\'historique du stock ?')) {
                    fetch('/stockocesi/stock/clear-history', { method: 'POST' })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                content.innerHTML = '<div>Historique vidé avec succès.</div>';
                            } else {
                                content.innerHTML = `<div class="error-message">${res.message || 'Erreur lors de la suppression.'}</div>`;
                            }
                        })
                        .catch(() => {
                            content.innerHTML = '<div class="error-message">Erreur réseau.</div>';
                        });
                }
            });
        })
        .catch(() => {
            content.innerHTML = '<div class="error-message">Erreur réseau.</div>';
        });
};

window.closeHistoryGeneralModal = function() {
    document.getElementById('historyGeneralModal').style.display = 'none';
};