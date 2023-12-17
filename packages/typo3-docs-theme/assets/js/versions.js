jQuery(document).ready(function () {
  'use strict';

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
