document.addEventListener('DOMContentLoaded', () => {
  const languageSelect = document.getElementById('languageSelect');
  const versionSelect = document.getElementById('versionSelect');

  const DEFAULT_URL = 'https://docs.typo3.org/services/versionsJson.php?url=';

  let currentURL = document.URL;
  const overrideUrl = versionSelect.getAttribute('data-override-url-self');
  const proxyUrl = versionSelect.getAttribute('data-override-url-proxy');

  if (overrideUrl) {
    currentURL = overrideUrl;
  }

  const fetchUrl = (proxyUrl || DEFAULT_URL) + encodeURIComponent(currentURL);

  let versionsByLanguage = {};

  fetch(fetchUrl)
    .then(response => {
      if (!response.ok) throw new Error('Failed to fetch versions');
      return response.json();
    })
    .then(data => {
      // Group by language
      data.forEach(item => {
        const lang = item.language.toLowerCase();
        if (!versionsByLanguage[lang]) versionsByLanguage[lang] = [];
        versionsByLanguage[lang].push(item);
      });

      const languageKeys = Object.keys(versionsByLanguage);
      const currentLangFromUrl = getLanguageFromUrl(currentURL);

      if (languageKeys.length <= 1 && versionsByLanguage['en-us']) {
        // ✅ English only
        renderVersionSelect(versionsByLanguage['en-us']);
      } else {
        // ✅ Multiple languages
        languageSelect.classList.remove('d-none');
        versionSelect.innerHTML = '<option disabled selected>Select a version</option>';

        languageKeys.sort().forEach(lang => {
          const option = document.createElement('option');
          option.value = lang;
          option.textContent = humanizeLanguage(lang);
          languageSelect.appendChild(option);
        });

        // ✅ Preselect based on URL or fallback to English
        const defaultLang = languageKeys.includes(currentLangFromUrl) ? currentLangFromUrl : 'en-us';
        languageSelect.value = defaultLang;

        renderVersionSelect(versionsByLanguage[defaultLang]);

        // ✅ Language switch triggers navigation immediately
        languageSelect.addEventListener('change', () => {
          const selectedLang = languageSelect.value;
          const versions = versionsByLanguage[selectedLang];

          if (versions && versions.length > 0) {
            renderVersionSelect(versions);
            const firstVersionUrl = toAbsoluteUrl(versions[0].url);
            window.location.href = firstVersionUrl;
          }
        });
      }
    })
    .catch(error => {
      console.error(error);
      versionSelect.innerHTML = '<option disabled>Error loading versions</option>';
    });

  // ✅ Manual version change
  versionSelect.addEventListener('change', (e) => {
    if (e.target.value) {
      window.location.href = e.target.value;
    }
  });

  function renderVersionSelect(versionData) {
    versionSelect.innerHTML = '';
    const seen = new Set();

    const currentVersion = versionSelect.getAttribute('data-current-version');

    const sortedData = versionData.sort((a, b) => {
      const priority = (v) => {
        if (v === 'main') return Infinity;
        const num = parseFloat(v);
        return isNaN(num) ? -1 : num;
      };
      return priority(b.version) - priority(a.version);
    });

    sortedData.forEach(item => {
      if (!seen.has(item.version)) {
        const option = document.createElement('option');
        option.value = toAbsoluteUrl(item.url);
        option.textContent = item.version;
        if (item.version === currentVersion) {
          option.selected = true;
        }
        versionSelect.appendChild(option);
        seen.add(item.version);
      }
    });

    if (versionSelect.options.length === 0) {
      const option = document.createElement('option');
      option.textContent = 'No versions available';
      option.disabled = true;
      versionSelect.appendChild(option);
    }
  }


  function humanizeLanguage(langCode) {
    const map = {
      'en-us': 'English',
      'de-de': 'German',
      'fr-fr': 'French',
      'ru-ru': 'Russian'
    };
    return map[langCode] || langCode.toUpperCase();
  }

  function getLanguageFromUrl(url) {
    const langRegex = /\/([a-z]{2}-[a-z]{2})\//i;
    const match = url.match(langRegex);
    return match ? match[1].toLowerCase() : '';
  }

  function toAbsoluteUrl(url) {
    try {
      const link = document.createElement('a');
      link.href = url;
      return link.href;
    } catch {
      return url;
    }
  }
});
