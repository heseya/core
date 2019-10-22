window.closeModal = () => {
    document.getElementById('modal').classList.add('modal--hidden')
}

window.confirmModal = (title) => {
    let modal = document.getElementById('modal')

    document.getElementById('modal__title').innerText = title
    modal.classList.remove('modal--hidden')
}
