window.closeModal = () => {
    document.getElementById('modal-confirm').classList.add('modal--hidden')
    document.getElementById('modal-info').classList.add('modal--hidden')
}

window.confirmModal = (title, url) => {
    let modal = document.getElementById('modal-confirm')

    document.getElementById('modal-title').innerText = title
    document.getElementById('modal-form').action = url
    modal.classList.remove('modal--hidden')
}

window.infoModal = (content) => {
    let modal = document.getElementById('modal-info')

    document.getElementById('modal-content').innerText = content
    modal.classList.remove('modal--hidden')
}
