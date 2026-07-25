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
          alertSuccessDiv.innerHTML = `Snippet <code>${htmlEscape(targetElement.value)}</code> was copied to your clipboard.`;
          navigator.clipboard.writeText(targetElement.value);
        });
      });
    }
  }

  // Escape a value for HTML element / RCDATA (<textarea>) content.
  function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
  }

  // Escape a value for a double-quoted HTML attribute (also encodes quotes).
  function attrEscape(text) {
    return String(text ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;');
  }

  // Only accept http(s) URLs; returns a URL object or null (blocks
  // javascript:/data:). The href is still attribute-escaped at the
  // interpolation site, because new URL() does NOT encode quotes in the host.
  function safeUrl(value) {
    try {
      const url = new URL(value, window.location.origin);
      return (url.protocol === 'http:' || url.protocol === 'https:') ? url : null;
    } catch {
      return null;
    }
  }

  const generalModal = document.querySelector(SELECTOR_MODAL);
  if (generalModal) {
    generalModal.addEventListener('show.bs.modal', function (event) {
      const item = event.relatedTarget;
      if (!item.dataset.composername) {
        return;
      }
      const generalModalLabel = generalModal.querySelector('#generalModalLabel');
      const content = generalModal.querySelector('#generalModalContent');
      generalModalLabel.innerText = item.dataset.composername;
      handleCopyButtons(generalModal);
      content.innerHTML = `
        <p>${htmlEscape(item.dataset.description)}</p>
        <p>Install the package using Composer: </p>
        <div class="input-group">
            <textarea class="form-control code" id="composer-command" readonly>${htmlEscape(item.dataset.composercommand)}</textarea>
            <button type="button" class="btn btn-outline-secondary copy-button" data-target="composer-command"><i class="fa-regular fa-clone"></i></button>
        </div>
  `;
      var links = '';
      if (item.dataset.source) {
        const url = safeUrl(item.dataset.source);
        if (url) {
          var srcString = 'Source';
          if (url.hostname === 'github.com') {
            srcString = 'GitHub';
          }
          if (url.hostname === 'gitlab.com') {
            srcString = 'GitLab';
          }
          links += `<a class="btn btn-light" href="${attrEscape(url.href)}">${srcString}</a>`;
        }
      }
      if (item.dataset.issues) {
        const url = safeUrl(item.dataset.issues);
        if (url) {
          links += `<a class="btn btn-light" href="${attrEscape(url.href)}">Report issue</a>`;
        }
      }
      if (links) {
        content.innerHTML += `<div class="btn-group mt-2" role="group" aria-label="Links to GitHub / GitLab">${links}</div>`;
      }
      const generalModalCustomButtons = generalModal.querySelector('#generalModalCustomButtons');

      // Add more buttons to the modal footer
      const packagistHref = item.href ? safeUrl(item.href) : null;
      generalModalCustomButtons.innerHTML = `
          <a href="${packagistHref ? attrEscape(packagistHref.href) : '#'}" class="btn btn-default"><i class="fa-solid fa-arrow-right"></i>&nbsp;Packagist</a>
      `;
      if (item.dataset.documentation) {
        const url = safeUrl(item.dataset.documentation);
        if (url) {
          const isExternal = url.hostname !== 'docs.typo3.org';
          generalModalCustomButtons.innerHTML += `
            <a href="${attrEscape(url.href)}" class="btn btn-default">
                <i class="fa-solid fa-book"></i>&nbsp;Documentation ${isExternal ? '(external)' : ''}
            </a>
        `;
        }
      }
      if (item.dataset.homepage) {
        const url = safeUrl(item.dataset.homepage);
        if (url) {
          const isTER = url.hostname === 'extensions.typo3.org';
          if (isTER) {
            generalModalCustomButtons.innerHTML += `
            <a href="${attrEscape(url.href)}" class="btn btn-default">
                <i class="fa-brands fa-typo3"></i>&nbsp;TER
            </a>
        `;
          }
        }
      }
      handleCopyButtons(generalModal);
    });
  }
})();
