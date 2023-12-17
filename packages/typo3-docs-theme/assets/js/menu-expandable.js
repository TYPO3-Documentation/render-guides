// toggle expand-collapse state of menu item
function toggleCurrent(event) { 'use strict';
  event.preventDefault();
  var link = event.currentTarget.parentElement;
  var element = link.parentElement;
  var siblings = element.parentElement.parentElement.querySelectorAll('li.current');
  for (var i = 0; i < siblings.length; i++) {
    if (siblings[i] !== element) {
      siblings[i].classList.remove('current');
    }
  }
  element.classList.toggle('current');
}

// add toggle icon to a-tags of menu items in .toc navigations
function makeMenuExpandable() { 'use strict';
  var tocs = document.getElementsByClassName('toc');
  for (var i = 0; i < tocs.length; i++) {
    var links = tocs[i].getElementsByTagName('a');
    for (var ii = 0; ii < links.length; ii++) {
      if (links[ii].nextSibling) {
        var expand = document.createElement('span');
        expand.classList.add('toctree-expand');
        expand.addEventListener('click', toggleCurrent, true);
        links[ii].prepend(expand);
      }
    }
  }
}
makeMenuExpandable();

jQuery(document).ready(function () {
  'use strict';

  // start with collapsed menu on a TYPO3 Exceptions page
  jQuery('li.toctree-l1.current').filter(":contains('TYPO3 Exceptions')").removeClass('current');
});
