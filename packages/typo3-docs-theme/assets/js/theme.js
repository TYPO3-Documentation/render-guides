// T3Docs

// ensure our own namespace
if (typeof window.T3Docs === 'undefined') {
  window.T3Docs = {};
}

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


// wrap tables to make them responsive
function makeTablesResponsive() { 'use strict';
  var tables = document.querySelectorAll('.rst-content table.docutils');
  for (var i = 0; i < tables.length; i++) {
    var wrapper = document.createElement('div');
    wrapper.classList.add('table-responsive');
    tables[i].parentNode.insertBefore(wrapper, tables[i]);
    wrapper.appendChild(tables[i]);
  }
}
makeTablesResponsive();


jQuery(document).ready(function () { 'use strict';

  function setVersionContent(content) {
    var options = document.createElement('dl');
    options.innerHTML = content;
    var versionOptions = document.getElementById("toc-version-options");
    versionOptions.innerHTML = '';
    versionOptions.appendChild(options);
  }

  var versionNode = document.getElementById("toc-version");
  if (versionNode) {
    versionNode.addEventListener('click', function () {
      var versionWrapper = document.getElementById("toc-version-wrapper");
      versionWrapper.classList.toggle('toc-version-wrapper-active');
      var versionOptions = document.getElementById("toc-version-options");
      if (!versionOptions.dataset.ready) {
        var versionsUri = 'https://docs.typo3.org/services/ajaxversions.php?url=' + encodeURI(document.URL);
        jQuery.ajax({
          url: versionsUri,
          success: function (result) {
            setVersionContent(result);
            var versionOptions = document.getElementById("toc-version-options");
            versionOptions.dataset.ready = true;
          },
          error: function () {
            setVersionContent('<p>No data available.</p>');
            var versionOptions = document.getElementById("toc-version-options");
            versionOptions.dataset.ready = true;
          }
        });
      }
    });
  }

  // start with collapsed menu on a TYPO3 Exceptions page
  jQuery('li.toctree-l1.current').filter(":contains('TYPO3 Exceptions')").removeClass('current');

  // fill in version hints
  if (!!DOCUMENTATION_OPTIONS && !!DOCUMENTATION_OPTIONS.URL_ROOT) {
    var coll = document.getElementsByClassName('version-hints-inner');
    if (!!coll && coll.length==1 && coll[0].dataset && coll[0].dataset.runAjax==='yes') {
      jQuery.ajax({
        url: DOCUMENTATION_OPTIONS.URL_ROOT+"_static/ajax-version-hints.html",
        success: function (texthtml) {
          jQuery(".version-hints-inner").html(texthtml);
        }
      });
    }
  }

});
