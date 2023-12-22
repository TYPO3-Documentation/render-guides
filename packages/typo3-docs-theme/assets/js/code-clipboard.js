(() => {
  const SELECTOR_BUTTON = '.code-block-copy';
  const SELECTOR_CODE = '.code-block';
  const SELECTOR_PARENT = '.code-block-wrapper';

  if (!navigator.clipboard && !navigator.clipboard.writeText) {
    console.info('"navigator.clipboard.writeText" is not available. Update to a modern browser to copy code to the system\'s clipboard');
    return;
  }

  [...document.querySelectorAll(SELECTOR_BUTTON)].forEach(item => {
    item.addEventListener('click', event => {
      const codeBlockElement = event.target.closest(SELECTOR_PARENT).querySelector(SELECTOR_CODE);
      if (!codeBlockElement) {
        console.warn('Cannot copy code as no code block is available!');
        return;
      }
      navigator.clipboard.writeText(codeBlockElement.textContent);
    });
  });

})();
