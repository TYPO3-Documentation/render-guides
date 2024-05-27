(() => {
  'use strict'

  const availableTabs = Array.from(
    document.querySelectorAll('.nav-item > [role="tab"]')
  )

  function getTabNameFromElement(element) {
    return element.innerHTML.trim()
  }

  // select all tabs with the same name
  function onTabShow(event) {
    const tabName = getTabNameFromElement(event.target)
    const relatedTabs = availableTabs
      .filter((tab) => {
        const hasSameName = getTabNameFromElement(tab) === tabName
        const isSameTab = tab === event.target
        const isActive = tab.getAttribute('aria-selected') === 'true'

        return hasSameName && !isSameTab && !isActive
      })

    relatedTabs.forEach((tab) => {
      const trigger = new bootstrap.Tab(tab)
      trigger.show()
    })
  }

  document.addEventListener('shown.bs.tab', onTabShow)
})();
