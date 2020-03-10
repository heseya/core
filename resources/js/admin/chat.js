window.formatMails = function() {

    const tags = '.gmail_quote, .inpl-collapsed'

    document.querySelectorAll(tags).forEach(tag => {
        tag.classList.toggle('hidden-message')
    })

    document.querySelectorAll('.bubble').forEach(tag => {
        tag.innerHTML = tag.innerHTML.replace(/^(\<br\>)+/, '')
    })
}
