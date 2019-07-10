require('./admin/orders.js')
require('./admin/chats.js')

require('./admin/dark.js')

window.toBottom = function () {
  window.scrollTo(0, document.body.scrollHeight);
}