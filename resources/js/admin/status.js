document.getElementById('payment_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status płatności?')) {

        fetch('/admin/orders/' + window.order_id + '/status', {
            method: 'POST',
            body: JSON.stringify({
                type: 'payment',
                status: event.target.value,
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
});

document.getElementById('shop_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status zanówienia?')) {

        fetch('/admin/orders/' + window.order_id + '/status', {
            method: 'POST',
            body: JSON.stringify({
                type: 'shop',
                status: event.target.value,
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
});

document.getElementById('delivery_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status dostawy?')) {

        fetch('/admin/orders/' + window.order_id + '/status', {
            method: 'POST',
            body: JSON.stringify({
                type: 'delivery',
                status: event.target.value,
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
});
