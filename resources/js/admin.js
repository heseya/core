require('./admin/orders.js')
require('./admin/chats.js')

require('./admin/dark.js')

function loader (element) {
  let loader = document.createElement('div')
  loader.classList.add('loader--warper')

  let loader2 = document.createElement('div')
  loader2.classList.add('loader')
  loader.appendChild(loader2)
  element.appendChild(loader)
}