window.payment = function(code)
{
    fetch('/orders/' + code + '/pay', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    }).then((response) => window.infoModal(response));
}
