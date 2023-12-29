(() => {
  'use strict';

  const URL_TEMPLATE = 'https://docs.typo3.org/services/ajaxversions.php?url=';

  const SELECTOR_VERSION_ID = 'toc-version';
  const SELECTOR_VERSION_OPTIONS_ID = 'toc-version-options';
  const SELECTOR_VERSION_WRAPPER_ID = 'toc-version-wrapper';
  const SELECTOR_VERSION_WRAPPER_ACTIVE_CLASS = 'toc-version-wrapper-active';

  const versionElement = document.getElementById(SELECTOR_VERSION_ID);
  if (!versionElement) {
    return;
  }

  async function retrieveListOfVersions() {
    const url = URL_TEMPLATE + encodeURI(document.URL);
    const response = await fetch(url);
    if (!response.ok) {
      return '';
    }

    return response.text();
  }

  function setVersionContent(parentElement, content) {
    const options = document.createElement('dl');
    options.innerHTML = content;
    parentElement.innerHTML = '';
    parentElement.appendChild(options);
  }

  function addListOfVersions() {
    const versionWrapperElement = document.getElementById(SELECTOR_VERSION_WRAPPER_ID);
    const versionOptions = document.getElementById(SELECTOR_VERSION_OPTIONS_ID);

    versionWrapperElement.classList.toggle(SELECTOR_VERSION_WRAPPER_ACTIVE_CLASS);
    if (versionOptions.dataset.ready) {
      return;
    }

    retrieveListOfVersions().then(data => {
      if (data === '') {
        data = '<p>No data available.</p>';
      }
      setVersionContent(versionOptions, data);
      versionOptions.dataset.ready = 'true';
    });
  }

  document.addEventListener('click', addListOfVersions);
})();
