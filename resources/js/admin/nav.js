let slideTime = 300;

class Panel {
    constructor (content, position) {
        this.dom = document.createElement('div')
        this.dom.classList.add('panel')

        this.dom.innerHTML = content

        this.position = position
    }

    slide (position) {
        this.warp(position)

        if (this.handler)
            clearTimeout(this.handler)

        this.dom.classList.add('slide')
        this.handler = setTimeout((() => this.dom.classList.remove('slide')).bind(this), slideTime)
    }

    warp (position) {
        this.dom.classList.remove('left', 'top', 'middle', 'bottom', 'right')
        this.dom.classList.add(this.position[0] < position[0] ? 'left' : this.position[0] > position[0] ? 'right' : this.position[1] < position[1] ? 'top' : this.position[1] > position[1] ? 'bottom' : 'middle')
    }
}

class Interface {
    constructor (config) {
        this.loader = document.getElementById('splashscreen')
        this.tabs = document.getElementById('tabs')
        this.logo = document.getElementById('logo')
        this.main = document.getElementById('main')
        this.panels = {}
        this.handlers = {}
        this.load(config)
    }
    
    async load (config) {
        let init = config.init
        let panels = config.panels
        let tabs = config.tabs
        
        this.focus = init.panel

        slideTime = init.slideTime || slideTime
        document.querySelector(':root').style.setProperty('--slide-time', slideTime / 1000 + 's')
        
        for (let l = 0; l < panels.length; l++) {
            for (let p = 0; p < panels[l].length; p++) {
                let response = await fetch(panels[l][p].src)
                let content = await response.text()
                
                let panel = new Panel(content, [l, p])
                
                this.panels[panels[l][p].id] = panel
                
                this.main.appendChild(panel.dom)
            }
        }

        this.switchPanel(this.focus)
        
        for (let t = 0; t < tabs.length; t++) {
            let tab = document.createElement('div')
            let img = document.createElement('img')
            img.src = tabs[t].icon
            tab.dataset.tooltip = tabs[t].tooltip
            tab.className = 'tab'
            tab.appendChild(img)
            
            tab.onclick = () => {
                this.switchPanel(tabs[t].panel)
            }
            
            this.tabs.appendChild(tab)
        }

        window.updateOrders()
        
        this.loader.classList.add('splashscreen--off')
        setTimeout(() => {
          this.loader.style.display = 'none'
        }, 430);
    }

    switchPanel (panel) {
      history.pushState({}, panel, panel);

      for (let id in this.panels) {
        if (panel == id || this.focus == id)
          this.panels[id].slide(this.panels[panel].position)
        else
          this.panels[id].warp(this.panels[panel].position)
      }

      this.lastFocus = this.focus
      this.focus = panel
    }

    slide (target) {
        if (this.handlers[target])
            clearTimeout(this.handlers[target])
        target.classList.add('slide')
        this.handlers[target] = setTimeout(() => target.classList.remove('slide'), slideTime)
    }
}

const init = async () => {
  let response = await fetch('../config.json')
  let text = await response.text()
  let config = JSON.parse(text)

  window.interface = new Interface(config)
}

init()