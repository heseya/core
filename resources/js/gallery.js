class Tabs {
  constructor (body) {
    this.tabs = []
    this.currentTab = null

    if (!body)
      this.dom = document.createElement('div')

    else if (typeof body == 'string')
      this.dom = document.querySelector(body)

    else
      this.dom = body

    this.dom.classList.add('tabs')

    this.initTabs()
  }

  initTabs () {
    this.updateTabs()

    this.dom.ondragover = (ev) => {
      ev.preventDefault()
      ev.dataTransfer.dropEffect = 'move'
    }

    this.dom.ondragenter = () => {
      this.dom.classList.add('dragover')
    }

    this.dom.ondragleave = () => {
      this.dom.classList.remove('dragover')
    }

    this.dom.ondrop = (ev) => {
      ev.preventDefault()

      if (!isNaN(ev.dataTransfer.getData('text/plain')))
        this.moveTab(ev.dataTransfer.getData('text/plain'), this.tabs.length - 1)
      this.dom.classList.remove('dragover')
    }
  }

  addTab (tab) {
      this.tabs.push(tab)

      tab.dom.ondragstart = (ev) => {
          ev.dataTransfer.setData('text/plain', this.tabs.indexOf(tab).toString())
      }

      tab.dom.ondragover = (ev) => {
          ev.preventDefault()

          ev.dataTransfer.dropEffect = "move"
      }

      tab.dom.ondragenter = (ev) => {
          ev.stopPropagation()

          tab.dom.classList.add('dragover')
      }

      tab.dom.ondragleave = (ev) => {
          ev.stopPropagation()

          tab.dom.classList.remove('dragover')
      }

      tab.dom.ondrop = (ev) => {
          ev.preventDefault()
          ev.stopPropagation()

          this.moveTab(ev.dataTransfer.getData('text/plain'), this.tabs.indexOf(tab))
          tab.dom.classList.remove('dragover')
      }

      tab.dom.onmousedown = () => {
          this.selectTab(tab)
      }

      if (!this.currentTab)
          this.selectTab(tab)

      this.updateTabs()
  }

  delTab (tab) {
    this.tabs.splice(this.tabs.indexOf(tab), 1)

    this.updateTabs()
  }

  rotateTab(tab) {
    tab.rotate()
  }

  moveTab (from, to) {
    let tab = this.tabs.splice(from, 1)[0]
    this.tabs.splice(to, 0, tab)

    this.updateTabs()
  }

  selectTab (tab) {
    if (this.currentTab)
      this.currentTab.unselect()
    tab.select()
    this.currentTab = tab
  }

  updateTabs () {
    while (this.dom.firstChild) {
      this.dom.removeChild(this.dom.firstChild)
    }

    for(let tab of this.tabs) {
      this.dom.appendChild(tab.dom)
    }
  }
}


let tabs = new Tabs('#tabs')
window.oldpictures = []

for (let id of window.oldpictures) {
  const preview = document.createElement('div')
  preview.className = 'gallery__img'
  preview.innerHTML = '<img src="/img/icons/camera.svg" />'
  preview.children[0].src = `/img/thumbnails/${id}.jpeg?` + Math.random()

  const input = document.createElement('input')
  input.name = 'photos[]'
  input.type = 'hidden'
  input.value = id

  preview.appendChild(input)

  let tab = new Tab(preview, id)

  const del = document.createElement('div')
  del.className = 'remove'
  del.onclick = () => {
    tabs.delTab(tab)
  }

  const rotate = document.createElement('div')
  rotate.className = 'rotate'
  rotate.onclick = () => {
    tabs.rotateTab(tab)
  }

  preview.appendChild(rotate)
  preview.appendChild(del)

  tabs.addTab(tab)
}

window.addPicture = (container) => {
    const preview = document.createElement('div')
    preview.className = 'gallery__img add'
    preview.innerHTML = '<img src="/img/icons/camera.svg" />'

    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/x-png,image/jpeg'

    const id = document.createElement('input')
    id.name = 'photos[]'
    id.type = 'hidden'

    preview.appendChild(id)
    container.appendChild(preview)

    preview.onclick = () => input.click()

    preview.ondragover = (ev) => {
        ev.preventDefault()

        ev.dataTransfer.dropEffect = 'copy'
    }

    preview.ondrop = (ev) => {
        ev.preventDefault()
        ev.stopPropagation()

        if (ev.dataTransfer.files.length == 1)
            input.files = ev.dataTransfer.files
    }

    input.onchange = () => {
        preview.classList.remove('add')
        const reader = new FileReader()
        reader.readAsDataURL(input.files[0])

        preview.children[0].src = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

        reader.onload = event => {
            preview.classList.add('loading')
            preview.children[0].src = event.target.result

        }

        let data = new FormData()
        data.append('photo', input.files[0])

        let tab = new Tab(preview, 0)

        fetch('/api/admin/upload', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        }).then((response) => response.text())
        .then((text) => {
            console.log(text)
            tab.id = text
            id.value = text
            preview.classList.remove('loading')
        })

        const del = document.createElement('div')
        del.className = 'remove'
        del.onclick = () => {
            tabs.delTab(tab)
        }

        const rotate = document.createElement('div')
        rotate.className = 'rotate'
        rotate.onclick = () => {
            tabs.rotateTab(tab)
        }

        preview.onclick = null
        preview.ondrop = null
        preview.appendChild(del)
        preview.appendChild(rotate)

        tabs.addTab(tab)

        addPicture(container)
    }
}

addPicture(document.getElementById('tabs'))


class Tab {
  constructor (content, id, callback) {
      this.id = id
      this.dom = content
      this.dom.classList.add('tab')
      this.dom.draggable = true
      this.callback = callback
      this.rotateState = 0;
  }

    select () {
        this.dom.classList.add('active')
        if (this.callback)
            this.callback()
    }

    unselect () {
        this.dom.classList.remove('active')
    }

    rotate () {
        this.dom.classList.remove('rotated-' + this.rotateState)
        this.rotateState = (this.rotateState == 3) ? 0 : this.rotateState + 1
        this.dom.classList.add('rotated-' + this.rotateState)
        // console.log(this.id)

        let data = new FormData()
        data.append('id', this.id)
        data.append('pos', this.rotateState)

        fetch('/panel/save/photo-pos.php', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        }).then((response) => response.text())
        .then((text) => {
            console.log(text)
        })
    }
}
