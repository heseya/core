window.darkMode = function() {
  if(localStorage.dark == undefined || localStorage.dark == 0) {
    localStorage.setItem('dark', 1);
    document.body.classList.add('dark')
  }
  else {
    localStorage.setItem('dark', 0);
    document.body.classList.remove('dark')
  }
}

if(localStorage.dark == 1) {
  document.body.classList.add('dark')
}
