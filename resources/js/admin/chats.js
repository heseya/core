const rtf = new Intl.RelativeTimeFormat('pl', { numeric: 'auto' });

let chats = document.getElementById('chats')

function updateChats () {

  loader(orders)

  fetch('/api/admin/orders', {credentials: 'same-origin'})
  .then(response => response.json())
  .then(data => {

    if(data.error) return;

    let days

    data.forEach(row => {

      let created = new Date(row.created_at);
      let sec = created.getTime() - new Date().getTime();
      created = Math.ceil(sec / (1000 * 60 * 60 * 24));

      if(days != created) {
        let e = document.createElement('li')
        e.classList.add('separator')
        e.innerText = rtf.format(created, 'day')
        orders.appendChild(e)
        
        days = created
      }

      let e = document.createElement('li')

      let left = document.createElement('div')

      let top = document.createElement('div')
      top.innerText = row.code
      left.appendChild(top)

      let bottom = document.createElement('small')
      bottom.innerText = row.email
      left.appendChild(bottom)

      e.appendChild(left)

      let sum = document.createElement('div')
      sum.innerText = row.sum
      sum.classList.add('sum')
      e.appendChild(sum)

      let status = document.createElement('div')
      status.classList.add('status')
      e.appendChild(status)

      row.status.forEach(color => {
        if(color == null) return
        let x = document.createElement('div')
        x.classList.add('status-circle')
        x.classList.add('status-circle__' + color)
        status.appendChild(x)
      });

      let a = document.createElement('a')
      a.href = '/admin/orders/' + row.id

      a.appendChild(e)
      orders.appendChild(a)
    });

    loader.remove()
  })
}

if(chats !== null)
  updateOrders()