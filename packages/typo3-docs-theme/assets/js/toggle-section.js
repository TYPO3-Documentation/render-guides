class ToggleSection {
  TOGGLE_CLASS = 'toggle-section';
  TOGGLE_ICON_SHOW = ['fa-solid', 'fa-eye'];
  TOGGLE_ICON_HIDE = ['fa-solid', 'fa-eye-slash'];
  TOGGLE_TITLE_SHOW = 'Show Section';
  TOGGLE_TITLE_HIDE = 'Hide section';
  TOGGLE_ALL_SECTIONS_SELECTOR = '.toggle-all-sections';
  TOGGLE_ALL_SECTIONS_TITLE_SHOW = 'Expand all sections';
  TOGGLE_ALL_SECTIONS_TITLE_HIDE = 'Collapse all sections';
  sections = [];


  constructor() {
    this.sectionHeadings = document.querySelectorAll('section > :is(h2,h3,h4,h5,h6)');
    this.sectionHeadings.forEach(heading => {
      const toggleSectionButton = this.createToggleSectionButton()
      const headerLink = heading.querySelector('a.headerlink');
      const section = heading.parentElement;
      this.sections.push(section)
      heading.insertBefore(toggleSectionButton, headerLink);
      toggleSectionButton.addEventListener('click', (e) => {
        e.preventDefault()
        this.toggleSection(section)
      })
    })
    if (this.sectionHeadings) {
      const links = document.querySelectorAll('a')
      links.forEach(link => {
        if (link.hash) {
          link.addEventListener('click', (e) => {
            const section = document.querySelector(link.hash)
            if (section) {
              this.changeSection(section, false)
            }
          })
        }
      })
    }
    const toggleAllSectionsButton = document.querySelector(this.TOGGLE_ALL_SECTIONS_SELECTOR)
    if (toggleAllSectionsButton) {
      toggleAllSectionsButton.addEventListener('click', () => {
        toggleAllSectionsButton.classList.toggle('hide-all')
        const hideAll = toggleAllSectionsButton.classList.contains('hide-all')
        toggleAllSectionsButton.setAttribute('title', hideAll ? this.TOGGLE_ALL_SECTIONS_TITLE_SHOW : this.TOGGLE_ALL_SECTIONS_TITLE_HIDE);
        toggleAllSectionsButton.textContent = hideAll ? this.TOGGLE_ALL_SECTIONS_TITLE_SHOW : this.TOGGLE_ALL_SECTIONS_TITLE_HIDE;
        this.sections.forEach(section => {
          this.changeSection(section, hideAll)
        })
      })
    }
  }

  createToggleSectionButton() {
    const toggleButton = document.createElement('a')
    toggleButton.classList.add(this.TOGGLE_CLASS);
    toggleButton.setAttribute('href', '#');
    toggleButton.setAttribute('title', this.TOGGLE_TITLE_HIDE);
    const icon = document.createElement('i');
    icon.classList.add(...this.TOGGLE_ICON_HIDE);
    toggleButton.appendChild(icon);
    return toggleButton;
  }

  toggleSection(section) {
    const isCollapsed = section.classList.contains('section-collapsed');
    this.changeSection(section, !isCollapsed)
  }

  changeSection(section, hide) {
    if (hide) {
      section.classList.add('section-collapsed');
    } else {
      section.classList.remove('section-collapsed');
    }
    const headings = ['H2', 'H3', 'H4', 'H5', 'H6'];
    section.querySelectorAll(':scope > *').forEach(sectionEl => {
      if (headings.includes(sectionEl.nodeName)) {
        const toggle = sectionEl.querySelector('.' + this.TOGGLE_CLASS);
        const icon = toggle.querySelector(':scope > i');
        toggle.setAttribute('title', hide ? this.TOGGLE_TITLE_SHOW : this.TOGGLE_TITLE_HIDE);
        icon.classList.remove(...icon.classList);
        const classesToAdd = hide ? this.TOGGLE_ICON_SHOW : this.TOGGLE_ICON_HIDE;
        icon.classList.add(...classesToAdd);
      } else {
        if (hide) {
          sectionEl.classList.add('collapsed-section-content');
        } else {
          sectionEl.classList.remove('collapsed-section-content');
        }
      }
    })
  }
}


new ToggleSection()
