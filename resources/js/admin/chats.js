window.updateChats = () => {

  let chats = document.getElementById('chats')

  window.loader.start(chats)

  fetch('/api/admin/chats', {credentials: 'same-origin'})
  .then(response => response.json())
  .then(data => {

    if(data.error) return;

    let chatsTemp = chats.innerHTML

    data.forEach(row => {

      chatsTemp += `
      <a href="/admin/chat/${row.id}">
        <li class="clickable">
          <div class="avatar">
            <img src="${row.avatar}">
          </div>
          <div>
            <div class="${row.unread ? `unread` : ``}">${row.client.name}</div>
            <small>${row.snippet}</small>
          </div>
        </li>
      </a>`
    });

    chats.innerHTML = chatsTemp
  })

  window.loader.stop()
}
