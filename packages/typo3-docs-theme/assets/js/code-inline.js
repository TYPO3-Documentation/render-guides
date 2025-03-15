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

  function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
        content.innerHTML += `<p><strong>Language info:</strong> ${item.dataset.shortdescription}</p>`;
      }
      if (item.dataset.details) {
        content.innerHTML += `<p>${item.dataset.details}</p>`;
      }
      content.innerHTML += `
        <div class="mb-3">
          <label class="form-label" for="code-snippet">Code Snippet: </label>
          <div class="input-group">
              <textarea class="form-control code" id="code-snippet" readonly>${item.dataset.code}</textarea>
              <button type="button" class="btn btn-outline-secondary copy-button" data-target="code-snippet"><i class="far fa-clone"></i></button>
          </div>
        </div>
      `;

      if (item.dataset.fqn) {
        if (item.dataset.fqn !== item.dataset.code) {
          content.innerHTML += `
          <div class="mb-3">
            <label class="form-label" for="fqn-snippet">Fully Qualified Name (FQN): </label>
            <div class="input-group">
                <textarea class="form-control code" id="fqn-snippet" readonly>${item.dataset.fqn}</textarea>
                <button type="button" class="btn btn-outline-secondary copy-button" data-target="fqn-snippet"><i class="far fa-clone"></i></button>
            </div>
          </div>
        `;
        }

        content.innerHTML += `
          <div class="mb-3">
            <label class="form-label" for="use-statement">PHP Use Statement: </label>
            <div class="input-group">
                <textarea class="form-control code" id="use-statement" readonly>use ${item.dataset.fqn};</textarea>
                <button type="button" class="btn btn-outline-secondary copy-button" data-target="use-statement"><i class="far fa-clone"></i></button>
            </div>
          </div>
        `;
      }

      let links = '';
      if (item.dataset.morelink) {
        links += `<a class="btn btn-light" href="${item.dataset.morelink}" target="_blank">More Info</a>`;
      }
      if (links) {
        content.innerHTML += `<div class="btn-group mt-2" role="group">${links}</div>`;
      }
      handleCopyButtons(generalModal);
    });
  }
})();
