window.cartItems = 0

window.addItem = ($id, depth = 0, parent = 'items') => {

    window.cartItems++;

    div = document.createElement('div')
    div.classList.add('depth-' + depth)
    div.id = 'item' + window.cartItems

    div.innerHTML =
    '<div class="columns">' +
        '<div class="column is-2">' +
            '<div class="field">' +
                '<label class="label">Symbol</label>' +
                '<div class="control">' +
                    '<input name="' + parent + '[' + window.cartItems + '][symbol]" class="input">' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="column is-6">' +
            '<div class="field">' +
                '<label class="label">Nazwa</label>' +
                '<div class="control">' +
                    '<input name="' + parent + '[' + window.cartItems + '][name]" class="input" required>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="column is-1">' +
            '<div class="field">' +
                '<label class="label">Ilość</label>' +
                '<div class="control">' +
                    '<input name="' + parent + '[' + window.cartItems + '][qty]" class="input" required>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="column is-2">' +
            '<div class="field">' +
                '<label class="label">Cena</label>' +
                '<div class="control">' +
                    '<input name="' + parent + '[' + window.cartItems + '][price]" class="input" required>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="column is-1">' +
            '<div class="buttons">' +
                '<button class="button is-small" type="button" onclick="window.addItem(`subItem' + window.cartItems + '`, ' + (depth + 1) + ', `' + parent + '[' + window.cartItems + '][children]`)">' +
                    '<span class="icon is-small">' +
                        '<img src="/img/icons/plus.svg">' +
                    '</span>' +
                '</button>' +
                '<button class="button is-small" type="button" onclick="window.removeItem(`item' + window.cartItems + '`)">' +
                    '<span class="icon is-small">' +
                        '<img src="/img/icons/trash.svg">' +
                    '</span>' +
                '</button>' +
            '</div>' +
        '</div>' +
    '</div>' +

    '<div class="subItem" id="subItem' + window.cartItems + '"></div>'

    document.getElementById($id).appendChild(div)
}

window.removeItem = ($id) => {
    document.getElementById($id).remove()
}