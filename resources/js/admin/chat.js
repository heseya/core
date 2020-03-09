window.formatMails = function() {
    console.log(document.querySelectorAll('.gmail_quote'));

    document.querySelectorAll('.gmail_quote').forEach(tag => {
        console.log(tag)
        tag.style.display = 'none';
    });
}
