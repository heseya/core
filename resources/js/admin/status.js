document.getElementById('payment_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status płatności?')) {

        fetch('/admin/orders/' + window.order_code + '/status', {
            method: 'POST',
            body: JSON.stringify({
                type: 'payment',
                status: event.target.value,
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        }).then((response) => window.responseNotify(response));
    }
});

document.getElementById('shop_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status zanówienia?')) {

        fetch('/admin/orders/' + window.order_code + '/status', {
            method: 'POST',
            body: JSON.stringify({
                type: 'shop',
                status: event.target.value,
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        }).then((response) => window.responseNotify(response));
    }
});

document.getElementById('delivery_status').addEventListener('change', (event) => {

    if (confirm('Czy na pewno chcesz zmienić status dostawy?')) {

        fetch('/admin/orders/' + window.order_code + '/status', {
            method: 'POST',
            body: JSON.stringify({
                type: 'delivery',
                status: event.target.value,
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        }).then((response) => window.responseNotify(response));
    }
});

window.responseNotify = (response) => {
    if (response.status === 204) {
        bulmaToast.toast({
            message: 'Zmiany został poprawnie zapisane!',
            type: 'is-success',
            duration: 4000,
            animate: { in: 'bounceInRight', out: 'bounceOutRight' },
        });
    } else {
        bulmaToast.toast({
            message: 'Nie udało się zapisać zmian!',
            type: 'is-danger',
            duration: 6000,
            animate: { in: 'bounceInRight', out: 'bounceOutRight' },
        });
    }
}
