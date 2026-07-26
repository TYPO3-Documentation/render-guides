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
            console.warn('Cannot copy as no input is available!');
            return;
          }
          alertSuccessDiv.classList.remove('d-none');
          alertSuccessDiv.innerHTML = `Code <code>${htmlEscape(targetElement.value)}</code> was copied to your clipboard.`;
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
      if (!item.dataset.code) {
        return;
      }
      const generalModalLabel = generalModal.querySelector('#generalModalLabel');
      const content = generalModal.querySelector('#generalModalContent');
      generalModalLabel.innerText = item.dataset.code;

      handleCopyButtons(generalModal);
      content.innerHTML = '';
      if (item.dataset.shortdescription) {
        content.innerHTML += `<p><strong>Language info:</strong> ${htmlEscape(item.dataset.shortdescription)}</p>`;
      }
      // Every markup fragment below is written here, never taken from a data-*
      // value: the values are plain text and are escaped exactly once.
      const detailParts = [];
      if (item.dataset.signature) {
        detailParts.push(`<code>${htmlEscape(item.dataset.signature)}</code>`);
      }
      if (item.dataset.flags) {
        detailParts.push(htmlEscape(item.dataset.flags));
      }
      if (item.dataset.summary) {
        detailParts.push(`<em>${htmlEscape(item.dataset.summary)}</em>`);
      }
      if (item.dataset.details) {
        detailParts.push(htmlEscape(item.dataset.details));
      }
      if (detailParts.length) {
        content.innerHTML += `<p>${detailParts.join('<br>')}</p>`;
      }
      content.innerHTML += `
        <div class="mb-3">
          <label class="form-label" for="code-snippet">Code Snippet: </label>
          <div class="input-group">
              <textarea class="form-control code" id="code-snippet" readonly>${htmlEscape(item.dataset.code)}</textarea>
              <button type="button" class="btn btn-outline-secondary copy-button" data-target="code-snippet"><i class="fa-regular fa-clone"></i></button>
          </div>
        </div>
      `;

      if (item.dataset.fqn) {
        if (item.dataset.fqn !== item.dataset.code) {
          content.innerHTML += `
          <div class="mb-3">
            <label class="form-label" for="fqn-snippet">Fully Qualified Name (FQN): </label>
            <div class="input-group">
                <textarea class="form-control code" id="fqn-snippet" readonly>${htmlEscape(item.dataset.fqn)}</textarea>
                <button type="button" class="btn btn-outline-secondary copy-button" data-target="fqn-snippet"><i class="fa-regular fa-clone"></i></button>
            </div>
          </div>
        `;
        }

        content.innerHTML += `
          <div class="mb-3">
            <label class="form-label" for="use-statement">PHP Use Statement: </label>
            <div class="input-group">
                <textarea class="form-control code" id="use-statement" readonly>use ${htmlEscape(item.dataset.fqn)};</textarea>
                <button type="button" class="btn btn-outline-secondary copy-button" data-target="use-statement"><i class="fa-regular fa-clone"></i></button>
            </div>
          </div>
        `;
      }

      let links = '';
      if (item.dataset.morelink) {
        const url = safeUrl(item.dataset.morelink);
        if (url) {
          links += `<a class="btn btn-light" href="${attrEscape(url.href)}" target="_blank">More Info</a>`;
        }
      }
      if (links) {
        content.innerHTML += `<div class="btn-group mt-2" role="group">${links}</div>`;
      }
      handleCopyButtons(generalModal);
    });
  }
})();
