window.closeModal = () => {
    document.querySelector('.modal').classList.add('modal--hidden')
}

window.confirmModal = (title, url) => {
    let modal = document.getElementById('modal-confirm')

    document.getElementById('modal-title').innerText = title
    document.getElementById('modal-form').action = url
    modal.classList.remove('modal--hidden')
}
