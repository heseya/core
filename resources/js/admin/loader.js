window.loader = {

  start: (element) => {
    let loader = document.createElement('div')
    loader.classList.add('loader--warper')
    loader.id = 'loader'
    
    let loader2 = document.createElement('div')
    loader2.classList.add('loader')
    loader.appendChild(loader2)

    element.appendChild(loader)
  },

  stop: () => {
    document.getElementById('loader').remove()
  }
}