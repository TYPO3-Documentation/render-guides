(() => {
  const SELECTOR_MODAL = '#generalModal';
  const SELECTOR_COPY_BUTTON = '.copy-button';
  const SELECTOR_ALERT_SUCCESS = '#general-alert-success';

  function handleCopyButtons(generalModal) {
    const alertSuccessDiv = generalModal.querySelector(SELECTOR_ALERT_SUCCESS);
    const copyButtons = generalModal.querySelectorAll(SELECTOR_COPY_BUTTON);
    if (!navigator.clipboard || !navigator.clipboard.writeText) {
      console.info('"navigator.clipboard.writeText" is not available. Update to a modern browser to copy code to the system\'s clipboard');
      copyButtons.forEach(button => button.disabled = true);
    } else {
      copyButtons.forEach(button => {
        button.addEventListener('click', function () {
          const targetId = this.getAttribute('data-target');
          const targetElement = generalModal.querySelector(`#${targetId}`);
          if (!targetElement) {
            console.warn('Cannot copy link as no input is available!');
            return;
          }
          alertSuccessDiv.classList.remove('d-none');
          alertSuccessDiv.innerHTML = `Path <code>${htmlEscape(targetElement.value)}</code> was copied to your clipboard.`;
          navigator.clipboard.writeText(targetElement.value);
        });
      });
    }
  }

  function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  const generalModal = document.querySelector(SELECTOR_MODAL);
  if (generalModal) {
    generalModal.addEventListener('show.bs.modal', function (event) {
      const item = event.relatedTarget;
      if (!item.dataset.filename) {
        return;
      }
      const generalModalLabel = generalModal.querySelector('#generalModalLabel');
      const content = generalModal.querySelector('#generalModalContent');
      generalModalLabel.innerText = item.dataset.filename;
      if (item.dataset.scope) {
        generalModalLabel.innerText += ' (' + item.dataset.scope + ')';
      }
      handleCopyButtons(generalModal);
      content.innerHTML = '';
      if (item.dataset.shortdescription) {
        content.innerHTML += `<p>${item.dataset.shortdescription}</p>`;
      }
      content.innerHTML += `
        <div class="mb-3">
          <label class="form-label" for="composer-path">Path in Composer-based TYPO3 Installations: </label>
          <div class="input-group">
              <textarea class="form-control code" id="composer-path" readonly>${item.dataset.composerpath}${item.dataset.filename}</textarea>
              <button type="button" class="btn btn-outline-secondary copy-button" data-target="composer-path"><i class="far fa-clone"></i></button>
          </div>
          <p>Example: <code>${item.dataset.composerpathprefix}${item.dataset.composerpath}${item.dataset.filename}</code></p>
        </div>
        <div class="mb-3">
          <label class="form-label" for="classic-path">Path in Classic TYPO3 Installations: </label>
          <div class="input-group">
              <textarea class="form-control code" id="classic-path" readonly>${item.dataset.classicpath}${item.dataset.filename}</textarea>
              <button type="button" class="btn btn-outline-secondary copy-button" data-target="classic-path"><i class="far fa-clone"></i></button>
          </div>
          <p>Example: <code>${item.dataset.classicpathprefix}${item.dataset.classicpath}${item.dataset.filename}</code></p>
        </div>
  `;
      var links = '';
      if (item.dataset.source) {
        const url = new URL(item.dataset.source);
        var srcString = 'Source';
        if (url.hostname === 'github.com') {
          srcString = 'GitHub';
        }
        if (url.hostname === 'gitlab.com') {
          srcString = 'GitLab';
        }
        links += `<a class="btn btn-light" href="${item.dataset.source}">${srcString}</a>`;
      }
      if (item.dataset.issues) {
        links += `<a class="btn btn-light" href="${item.dataset.issues}">Report issue</a>`;
      }
      if (links) {
        content.innerHTML += `<div class="btn-group mt-2" role="group" aria-label="Links to GitHub / GitLab">${links}</div>`;
      }
      const generalModalCustomButtons = generalModal.querySelector('#generalModalCustomButtons');

      // Add more buttons to the modal footer
      generalModalCustomButtons.innerHTML = `
          <a href="${item.href}" class="btn btn-default"><i class="fa-solid fa-arrow-right"></i>&nbsp;Documentation</a>
      `;
      if (item.dataset.documentation) {
        const url = new URL(item.dataset.documentation);
        const isExternal = url.hostname !== 'docs.typo3.org';
        generalModalCustomButtons.innerHTML += `
            <a href="${item.dataset.documentation}" class="btn btn-default">
                <i class="fa-solid fa-book"></i>&nbsp;Documentation ${isExternal ? '(external)' : ''}
            </a>
        `;
      }
      if (item.dataset.homepage) {
        const url = new URL(item.dataset.homepage);
        const isTER = url.hostname === 'extensions.typo3.org';
        if (isTER) {
          generalModalCustomButtons.innerHTML += `
            <a href="${item.dataset.homepage}" class="btn btn-default">
                <i class="fa-brands fa-typo3"></i>&nbsp;TER
            </a>
        `;
        }
      }
      handleCopyButtons(generalModal);
    });
  }
})();
