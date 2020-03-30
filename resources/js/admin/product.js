const addSchema = document.getElementById('schema-add')
const schemas = document.getElementById('schemas')

let schemaCount = document.getElementById('schema-count')

window.deleteSchema = (id, old = false) => document.getElementById(`schema-${old ? 'old-' : ''}${id}`).remove()
window.deleteItem = (schemaId, itemId, oldSchema = false, newItem = false) => document.getElementById(
    `schema-${oldSchema ? 'old-' : ''}${schemaId}-item-${newItem ? 'new-' : ''}${itemId}`).remove()

window.changeProductType = () => {
    const type = document.getElementById('digital').value

    switch (parseInt(type)) {
        case 0:
            document.getElementById(`schemas`).innerHTML = `
            <input type="hidden" id="schema-count" name="schemas" value='0'>`
            document.getElementById('schema-button').innerHTML = `
            <div class="column">
                <label class="label">Schematy</label>
            </div>
            <div class="column">
                <div id="schema-add" class="top-nav--button" onclick="addSchema()">
                    <img class="icon" src="/img/icons/plus.svg">
                </div>
            </div>`
            break;
        case 1:
            document.getElementById('schemas').innerHTML = ''
            document.getElementById('schema-button').innerHTML = ''
    }
}

window.changeSchemaType = (schemaId, old = false) => {
    const type = document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-type`).value

    switch (parseInt(type)) {
        case 0:
            document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-items`).innerHTML = `
            <input type="hidden" id="schema-${old ? 'old-' : ''}${schemaId}-item-count" name="schema-${old ? 'old-' : ''}${schemaId}-items" value="0">`
            document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-button`).innerHTML = `
            <div class="column">
                <div class="button is-black" onclick="addItem(${schemaId}${old ? ', true' : ''})">Dodaj przedmiot</div>
            </div>`
            break;
        case 1:
            document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-items`).innerHTML = ''
            document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-button`).innerHTML = ''
    }
}

window.addItem = (schemaId, old = false) => {
    let itemCount = document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-item-count`)
    let itemsTemplate = document.getElementById('items-template').innerHTML

    const item = document.createElement('div')
    item.id = `schema-${old ? 'old-' : ''}${schemaId}-item-${old ? 'new-' : ''}${itemCount.value}`
    item.className = 'columns has-no-margin-bottom'
    
    item.innerHTML = `
    <div class="column">
        <div class="field">
            <label class="label" for="schema-${old ? 'old-' : ''}${schemaId}-item-${old ? 'new-' : ''}${itemCount.value}-id">Przedmiot</label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="schema-${old ? 'old-' : ''}${schemaId}-item-${old ? 'new-' : ''}${itemCount.value}-id">
                        ${itemsTemplate}
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="column">
        <div class="field">
            <label class="label" for="schema-${old ? 'old-' : ''}${schemaId}-item-${old ? 'new-' : ''}${itemCount.value}-price">Dodatkowa cena</label>
            <div class="control">
                <input type="number" name="schema-${old ? 'old-' : ''}${schemaId}-item-${old ? 'new-' : ''}${itemCount.value}-price"
                class="input" required autocomplete="off" value="0" step="0.01">
            </div>
        </div>
    </div>
    <div class="column">
        <div class="top-nav--button" onclick="deleteItem(${schemaId}, ${itemCount.value++}${old ? ', true, true' : ''})">
            <img class="icon" src="/img/icons/trash.svg">
        </div>
    </div>`

    document.getElementById(`schema-${old ? 'old-' : ''}${schemaId}-items`).appendChild(item)
}

window.addSchema = () => {
    const schema = document.createElement('div')
    schema.id = 'schema-' + schemaCount.value

    schema.innerHTML = `
    <div class="columns has-no-margin-bottom">
        <div class="column">
            <div class="field">
                <label class="label" for="schema-${schemaCount.value}-name">Nazwa</label>
                <div class="control">
                    <input name="schema-${schemaCount.value}-name" class="input" required autocomplete="off">
                </div>
            </div>
        </div>
        <div class="column">
            <div class="field">
                <label class="label" for="schema-${schemaCount.value}-type">Typ</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select id="schema-${schemaCount.value}-type" name="schema-${schemaCount.value}-type" oninput="changeSchemaType(${schemaCount.value})">
                            <option value="0">Przedmioty</option>
                            <option value="1">Pole tekstowe</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="field">
                <label class="label" for="schema-${schemaCount.value}-required">Wymagany</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="schema-${schemaCount.value}-required">
                            <option value="0">Opcjonalny</option>
                            <option value="1">Wymagany</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="top-nav--button" onclick="deleteSchema(${schemaCount.value})">
                <img class="icon" src="/img/icons/trash.svg">
            </div>
        </div>
    </div>
    <div id="schema-${schemaCount.value}-items">
        <input type="hidden" id="schema-${schemaCount.value}-item-count" name="schema-${schemaCount.value}-items" value="0">
    </div>
    <div id="schema-${schemaCount.value}-button" class="columns has-no-margin-bottom">
        <div class="column">
            <div class="button is-black" onclick="addItem(${schemaCount.value++})">Dodaj przedmiot</div>
        </div>
    </div>`

    schemas.appendChild(schema)
}