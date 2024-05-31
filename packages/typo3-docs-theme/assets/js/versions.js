(() => {
  'use strict';

  const SELECTOR_VERSION_ID = 'toc-version';
  const SELECTOR_VERSION_OPTIONS_ID = 'toc-version-options';
  const SELECTOR_VERSION_WRAPPER_ID = 'toc-version-wrapper';
  const SELECTOR_VERSION_WRAPPER_ACTIVE_CLASS = 'toc-version-wrapper-active';
  const LANGUAGE_DEFAULT = 'A_Default';

  // it should have at least one more digit than the largest number part in
  // version strings
  const VERSION_SORT_BASE = 100000;

  const versionElement = document.getElementById(SELECTOR_VERSION_ID);
  if (!versionElement) {
    return;
  }

  async function retrieveListOfVersions() {
    let urlSelf = document.URL;
    let URL_TEMPLATE = 'https://docs.typo3.org/services/versionsJson.php?url=';

    if (versionElement.getAttribute('data-override-url-self')) {
      urlSelf = versionElement.getAttribute('data-override-url-self');
      URL_TEMPLATE = versionElement.getAttribute('data-override-url-proxy');
      console.log('AJAX version selector API: Developer mode enabled. Adjust data-override-url-self to simulate different menus. More information: https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html');
      console.log('Currently: ' + urlSelf);
      console.log('The API PROXY is currently served from: ' + URL_TEMPLATE);
    }
    const url = URL_TEMPLATE + encodeURI(urlSelf);

    try {
      const response = await fetch(url);
      if (!response.ok) {
        console.log('AJAX version selector API: Request failure or empty response.');
        return '';
      }

      return response.json();
    } catch (e) {
      console.log('AJAX version selector API: Request failed, likely CORS issue. Read the documentation to configure a proxy.');
      return '';
    }
  }

  function setVersionContent(parentElement, jsonData) {
    const options = document.createElement('dl');

    let defaultLanguage = 'en-us';

    // This is a list of "known" languages. We cannot execute
    // server-side language list parsing, because the versionJson.php
    // file is not under the TYPO3 Documentation Team's direct control.
    // If a language does not match the list defined here it falls back
    // to just using the language key like before.
    // Currently, only german, french and russian translations are used
    // for existing projects.
    let staticLanguages = {
      'de-de': 'German',
      'de-at': 'German (Austria)',
      'de-ch': 'German (Switzerland)',
      'en-gb': 'English',
      'fr-fr': 'French',
      'ru-ru': 'Russian',
    };
    staticLanguages[defaultLanguage] = LANGUAGE_DEFAULT;  // Underscore ensures alphabetical sorted first

    if (typeof jsonData !== 'object') {
      console.log('AJAX version selector API: Request failed, no JSON returned.');
      parentElement.innerHTML = '<p>Versions unavailable.</p>';
      return;
    }

    let unsortedOutput = {'currentfile': {}, 'singlefile': {}};

    // We sort by:
    // - First, english language links:
    //    - "main"
    //    - then all version numbers, with highest first (12.4, 11.5, 10.5, 9.7, 8.6, ...)
    //    - then all "named versions", alphabetically sorted ("alpha", "draft", "testing", "verified")
    // - Then all languages other than english, alphabetically sorted by their name
    //    - "main"
    //    - "named versions"
    //    - "version numbers"
    // - Then all links to "in one file" with the same sorting
    //
    // Thus the array is multidimensional:
    // unsortedOutput[singlefile|currentfile][language][1_main|2_numeric|3_named][...] = [url, title]
    //
    // Example:
    //
    // main
    // 12.4
    // 11.5
    // 10.4
    // 9.5
    // 8.7
    // 7.6
    // draft
    //
    // French:
    //   7.6
    //
    // Russian:
    //   main
    //   12.4
    //
    // In one file:
    //   main
    //   12.4
    //   11.5
    //   10.4
    //   9.5
    //   8.7
    //   7.6
    //   draft
    //
    //   French:
    //     7.6
    //
    //   Russian:
    //     main
    //     12.4

    for (let linkList in jsonData) {
      let currentItem = jsonData[linkList];

      let language = currentItem.language;
      let version = currentItem.version;
      let resolvedStaticLanguage = staticLanguages[language.toLocaleLowerCase()]

      if (resolvedStaticLanguage) {
        language = resolvedStaticLanguage;
      }

      // The "1_", "2_", "3_" ensures proper sortability.
      let versionType = '3_named';
      if (version === 'main') {
        versionType = '1_main';
      } else {
        let versionTrimmed = version.trim();
        let versionAsFloat = parseFloat(versionTrimmed);
        if (!isNaN(versionAsFloat) && Number(versionTrimmed) === versionAsFloat) {
          // make each number part have the same digit count, allowing to
          // properly sort as a string
          version = version.split('.').map(n => +n+VERSION_SORT_BASE).join('.')
          versionType = '2_numeric';
        }
      }

      // We assume that currentfile and singlefile are always filled in parallel.
      if (!unsortedOutput['currentfile'][language]) {
        unsortedOutput['currentfile'][language] = {};
        unsortedOutput['singlefile'][language] = {};
      }
      if (!unsortedOutput['singlefile'][language][versionType]) {
        unsortedOutput['currentfile'][language][versionType] = {};
        unsortedOutput['singlefile'][language][versionType] = {};
      }
      if (!unsortedOutput['singlefile'][language][versionType][version]) {
        unsortedOutput['currentfile'][language][versionType][version] = {};
        unsortedOutput['singlefile'][language][versionType][version] = {};
      }

      unsortedOutput['currentfile'][language][versionType][version] = currentItem.url;
      unsortedOutput['singlefile'][language][versionType][version] = currentItem.singleUrl;
    }

    // Leeloo multisort
    let sortedOutput = sortObjectByKey(unsortedOutput);

    let content = '';
    content += addHtmlFromType('currentfile', sortedOutput);
    content += addHtmlFromType('singlefile', sortedOutput);

    options.innerHTML = content;
    parentElement.innerHTML = '';
    parentElement.appendChild(options);
  }

  function addHtmlFromType(baseIndexKey, sortedOutput) {
    let html = '';
    if (baseIndexKey === 'singlefile') {
      html += '<dd><p><details><summary><strong>In one file:</strong></summary>';
    }

    for (let language in sortedOutput[baseIndexKey]) {
      if (language != LANGUAGE_DEFAULT) {
        html += '<dd><p><strong>' + language + '</strong></p></dd>';
      }
      for (let versionType in sortedOutput[baseIndexKey][language]) {
        // versionType: 1_main, 2_numeric, 3_named
        for (let version in sortedOutput[baseIndexKey][language][versionType]) {
          let parsedVersion = version

          if (versionType === '2_numeric') {
            // restore version string from before sorting
            parsedVersion = version.split('.').map(n => +n-VERSION_SORT_BASE).join('.')
          }

          html += '<dd><a href="' + sortedOutput[baseIndexKey][language][versionType][version] + '">' + parsedVersion + '</a></dd>';
        }
      }
    }

    if (baseIndexKey === 'singlefile') {
      html += '</details></p></dd>';
    }

    return html;
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

  function sortObjectByKey(obj) {
    if (obj !== null && typeof obj === 'object' && !Array.isArray(obj)) {
      const sortedObj = {};

      // Separate numeric and string keys
      const numericKeys = Object.keys(obj).filter(key => !isNaN(key)).sort((a, b) => b - a);
      const stringKeys = Object.keys(obj).filter(key => isNaN(key)).sort();

      // Combine the sorted keys
      const keys = [...numericKeys, ...stringKeys];

      keys.forEach(key => {
        sortedObj[key] = sortObjectByKey(obj[key]);
      });
      return sortedObj;
    } else {
      return obj;
    }
  }

  versionElement.addEventListener('click', addListOfVersions);
})();
