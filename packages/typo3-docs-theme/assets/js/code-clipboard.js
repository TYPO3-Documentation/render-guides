(() => {
  const SELECTOR_BUTTON = '.code-block-copy';
  const SELECTOR_CODE = '.code-block';
  const SELECTOR_HIDE_CLASS = 'code-block-hide';
  const SELECTOR_ICON_COPY = '.code-block-copy-icon';
  const SELECTOR_ICON_CHECK = '.code-block-check-icon';
  const SELECTOR_PARENT = '.code-block-wrapper';
  const SELECTOR_TOOLTIP_CHECK = '.code-block-check-tooltip';

  const COPIED_TIMEOUT_MILLISECONDS = 3000;

  if (!navigator.clipboard && !navigator.clipboard.writeText) {
    console.info('"navigator.clipboard.writeText" is not available. Update to a modern browser to copy code to the system\'s clipboard');
    return;
  }

  const toggleCopyClasses = (wrapperElement) => {
    const copyIconElement = wrapperElement.querySelector(SELECTOR_ICON_COPY);
    const checkIconElement = wrapperElement.querySelector(SELECTOR_ICON_CHECK);
    const tooltipElement = wrapperElement.querySelector(SELECTOR_TOOLTIP_CHECK);

    copyIconElement.classList.toggle(SELECTOR_HIDE_CLASS);
    checkIconElement.classList.toggle(SELECTOR_HIDE_CLASS);
    tooltipElement.classList.toggle(SELECTOR_HIDE_CLASS);
  };

  [...document.querySelectorAll(SELECTOR_BUTTON)].forEach(item => {
    item.addEventListener('click', event => {
      const wrapperElement = event.target.closest(SELECTOR_PARENT);
      const codeBlockElement = wrapperElement.querySelector(SELECTOR_CODE);
      if (!codeBlockElement) {
        console.warn('Cannot copy code as no code block is available!');
        return;
      }
      navigator.clipboard.writeText(codeBlockElement.textContent);

      toggleCopyClasses(wrapperElement);
      setTimeout(() => {
        toggleCopyClasses(wrapperElement);
      }, COPIED_TIMEOUT_MILLISECONDS);
    });
  });
})();
