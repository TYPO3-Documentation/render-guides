(() => {
  'use strict'

  // add a separate state for expanded elements
  const elements = Array.from(document.querySelectorAll('.main_menu li.active:has(li)'))
  elements.forEach((element) => {
    element.classList.add('expanded')
  })
})()
