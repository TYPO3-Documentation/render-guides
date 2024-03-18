(() => {
  const SELECTOR_BUTTON = '.headerlink';
  const SELECTOR_MODAL = '#linkReferenceModal';
  const SELECTOR_ALERT_RST_NO_ANCHOR = '.alert-permalink-rst';
  const SELECTOR_PERMALINK_URI = '#permalink-uri';
  const SELECTOR_PERMALINK_RST = '#permalink-rst';
  const SELECTOR_PERMALINK_HTML = '#permalink-html';
  const SELECTOR_ALERT_SUCCESS = '#permalink-alert-success';
  const SELECTOR_COPY_BUTTON = '.copy-button';

  function generateUri(section, rstAnchor) {
    // Firefox returns the string "null", whereas Chromium returns "file://"
    if (window.location.origin === 'null' || window.location.origin === 'file://') {
      return null;
    }
    return rstAnchor ? `${window.location.origin}${window.location.pathname}#${rstAnchor}` : `${window.location.origin}${window.location.pathname}#${section?.id || ''}`;
  }

  function generateRstLink(linkReferenceModal, section, headerText, rstAnchor, filename) {
    const interlinkTarget = linkReferenceModal.dataset.interlinkShortcode || 'somemanual';
    if (rstAnchor) {
      return `:ref:\`${headerText} <${interlinkTarget}:${rstAnchor}>\``;
    }
    if (filename === '') {
      return '';
    }
    return `:doc:\`${headerText} <${interlinkTarget}:${filename}#${section?.id || ''}>\``;
  }

  function showHideRstAnchorAlert(linkReferenceModal, rstAnchor) {
    const alertPermalinkRstNoAnchor = linkReferenceModal.querySelector(SELECTOR_ALERT_RST_NO_ANCHOR);
    if (!rstAnchor) {
      alertPermalinkRstNoAnchor.classList.remove('d-none');
    } else {
      alertPermalinkRstNoAnchor.classList.add('d-none');
    }
  }

  function updateInputsAndTextareas(linkReferenceModal, header, headerText, uri, rstLink) {
    if (header) {
      linkReferenceModal.querySelector('h5').innerHTML = header;
    }
    if (uri === null) {
      // this can happen when opening a local file
      linkReferenceModal.querySelector(SELECTOR_PERMALINK_URI).value = '';
      linkReferenceModal.querySelector(SELECTOR_PERMALINK_HTML).value = '';
    } else {
      linkReferenceModal.querySelector(SELECTOR_PERMALINK_URI).value = uri;
      linkReferenceModal.querySelector(SELECTOR_PERMALINK_HTML).value = `<a href="${uri}">${headerText}</a>`;
    }
    const rstInput = linkReferenceModal.querySelector(SELECTOR_PERMALINK_RST);
    const rstSection = rstInput.closest('div');
    if (rstLink === '') {
      // this happens in the single html file for links that have no anchor
      rstSection.classList.add('d-none')
    } else {
      rstSection.classList.remove('d-none')
      rstInput.value = rstLink;
    }
  }

  function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function handleCopyButtons(linkReferenceModal) {
    const alertSuccessDiv = linkReferenceModal.querySelector(SELECTOR_ALERT_SUCCESS);
    const copyButtons = linkReferenceModal.querySelectorAll(SELECTOR_COPY_BUTTON);
    if (!navigator.clipboard || !navigator.clipboard.writeText) {
      console.info('"navigator.clipboard.writeText" is not available. Update to a modern browser to copy code to the system\'s clipboard');
      copyButtons.forEach(button => button.disabled = true);
    } else {
      copyButtons.forEach(button => {
        button.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const targetElement = linkReferenceModal.querySelector(`#${targetId}`);
          if (!targetElement) {
            console.warn('Cannot copy link as no input is available!');
            return;
          }
          alertSuccessDiv.classList.remove('d-none');
          alertSuccessDiv.innerHTML = `Snippet <code>${htmlEscape(targetElement.value)}</code> was copied to your clipboard.`;
          navigator.clipboard.writeText(targetElement.value);
        });
      });
    }
  }

  const linkReferenceModal = document.querySelector(SELECTOR_MODAL);
  linkReferenceModal.addEventListener('show.bs.modal', function (event) {
    const item = event.relatedTarget;
    const section = item.closest('section');
    const rstAnchor = section ? section.dataset.rstAnchor : null;
    const headerElement = item.closest('h1, h2, h3, h4, h5, h6, dt');
    const headerText = headerElement ? headerElement.innerText : '';
    const rstLinkData = item.dataset.rstcode;
    const header = item.title;

    showHideRstAnchorAlert(linkReferenceModal, rstAnchor || rstLinkData);

    const uri = generateUri(section, rstAnchor);
    const filename = linkReferenceModal.dataset.currentFilename;
    const rstLink = rstLinkData?rstLinkData:generateRstLink(linkReferenceModal, section, headerText, rstAnchor, filename);

    updateInputsAndTextareas(linkReferenceModal, header, headerText, uri, rstLink);


    handleCopyButtons(linkReferenceModal);
  });
})();
