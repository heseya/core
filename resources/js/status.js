document.getElementById('payment_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status?')) {

        fetch('/api/admin/status', {
            method: 'POST', // or 'PUT'
            body: JSON.stringify({
                order_id: window.order_id,
                type: 'payment',
                status: event.target.value,
            }), // data can be `string` or {object}!
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
});

document.getElementById('shop_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status?')) {

        fetch('/api/admin/status', {
            method: 'POST', // or 'PUT'
            body: JSON.stringify({
                order_id: window.order_id,
                type: 'shop',
                status: event.target.value,
            }), // data can be `string` or {object}!
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
});

document.getElementById('delivery_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status?')) {

        fetch('/api/admin/status', {
            method: 'POST', // or 'PUT'
            body: JSON.stringify({
                order_id: window.order_id,
                type: 'delivery',
                status: event.target.value,
            }), // data can be `string` or {object}!
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
});
