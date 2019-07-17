let products = document.getElementById('products')

let formatter = new Intl.NumberFormat('pl-PL', {
  style: 'currency',
  currency: 'PLN',
});

window.updateProducts = () => {

  window.loader.start(products)

  fetch('/api/admin/products', {credentials: 'same-origin'})
  .then(response => response.json())
  .then(data => {

    if(data.error) return;

    let temp = products.innerHTML

    data.forEach(row => {

      temp += `
        <a href="/admin/products/${row.id}" class="product">
          <div class="product__img">
            <img src="${row.img}">
          </div>
          <div class="flex">
            <div class="name">
              ${row.name}<br/>
              <small>${formatter.format(row.price)}</small>  
            </div>
          </div>
        </a>`
    });

    products.innerHTML = temp
  })

  window.loader.stop()
}