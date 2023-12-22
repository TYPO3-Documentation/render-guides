(() => {
  const SELECTOR_BUTTON = '.headerlink';
  const SELECTOR_MODAL = '#permalinkModal';
  const SELECTOR_ALERT_RST_NO_ANCHOR = '.alert-permalink-rst';
  const SELECTOR_PERMALINK_URI = '#permalink-uri';
  const SELECTOR_PERMALINK_RST = '#permalink-rst';
  const SELECTOR_ALERT_SUCCESS = '#permalink-alert-success';
  const SELECTOR_COPY_BUTTON = '.copy-button';

  function generateUri(section, rstAnchor) {
    return rstAnchor ? `${window.location.origin}${window.location.pathname}#${rstAnchor}` : `${window.location.origin}${window.location.pathname}#${section?.id || ''}`;
  }

  function generateRstLink(permalinkModal, section, headerText, rstAnchor, filename) {
    const interlinkTarget = permalinkModal.dataset.interlinkShortcode || 'somemanual';
    if (rstAnchor) {
      return `:ref:\`${headerText} <${interlinkTarget}:${rstAnchor}>\``;
    }
    return `:doc:\`${headerText} <${interlinkTarget}:${filename}#${section?.id || ''}>\``;
  }

  function showHideRstAnchorAlert(permalinkModal, rstAnchor) {
    const alertPermalinkRstNoAnchor = permalinkModal.querySelector(SELECTOR_ALERT_RST_NO_ANCHOR);
    if (!rstAnchor) {
      alertPermalinkRstNoAnchor.classList.remove('d-none');
    } else {
      alertPermalinkRstNoAnchor.classList.add('d-none');
    }
  }

  function updateInputsAndTextareas(permalinkModal, headerText, uri, rstLink) {
    permalinkModal.querySelector(SELECTOR_PERMALINK_URI).value = uri;
    permalinkModal.querySelector('#permalink-html').value = `<a href="${uri}">${headerText}</a>`;
    permalinkModal.querySelector(SELECTOR_PERMALINK_RST).value = rstLink;
  }

  function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function handleCopyButtons(permalinkModal) {
    const alertSuccessDiv = permalinkModal.querySelector(SELECTOR_ALERT_SUCCESS);
    const copyButtons = permalinkModal.querySelectorAll(SELECTOR_COPY_BUTTON);
    if (!navigator.clipboard || !navigator.clipboard.writeText) {
      console.info('"navigator.clipboard.writeText" is not available. Update to a modern browser to copy code to the system\'s clipboard');
      copyButtons.forEach(button => button.disabled = true);
    } else {
      copyButtons.forEach(button => {
        button.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const targetElement = permalinkModal.querySelector(`#${targetId}`);
          if (!targetElement) {
            console.warn('Cannot copy link as no input is available!');
            return;
          }
          alertSuccessDiv.classList.remove('d-none');
          alertSuccessDiv.innerHTML = `Link <code>${htmlEscape(targetElement.value)}</code> was copied to your Clipboard.`;
          navigator.clipboard.writeText(targetElement.value);
        });
      });
    }
  }

  const permalinkModal = document.querySelector(SELECTOR_MODAL);
  permalinkModal.addEventListener('show.bs.modal', function (event) {
    const item = event.relatedTarget;
    const section = item.closest('section');
    const rstAnchor = section ? section.dataset.rstAnchor : null;
    const headerElement = item.closest('h1, h2, h3, h4, h5, h6');
    const headerText = headerElement ? headerElement.innerText : '';

    showHideRstAnchorAlert(permalinkModal, rstAnchor);

    const uri = generateUri(section, rstAnchor);
    const filename = permalinkModal.dataset.currentFilename;
    const rstLink = generateRstLink(permalinkModal, section, headerText, rstAnchor, filename);

    updateInputsAndTextareas(permalinkModal, headerText, uri, rstLink);


    handleCopyButtons(permalinkModal);
  });
})();
