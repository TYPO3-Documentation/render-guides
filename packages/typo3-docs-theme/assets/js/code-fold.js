/**
 * Folds the lines of a code block the renderer marked as foldable.
 *
 * Folding starts only here, so without JavaScript every line is shown. The
 * folded lines stay in the document, which is why copying takes the whole
 * block either way.
 */
(() => {
  const CLASS_FOLDED = 'code-block-folded';
  const CLASS_OPEN = 'is-open';
  const ICON_UNFOLD = 'fa-up-right-and-down-left-from-center';
  const ICON_FOLD = 'fa-down-left-and-up-right-to-center';

  document.querySelectorAll('.code-block-foldable').forEach((wrapper) => {
    const unfoldButton = wrapper.querySelector('.code-block-unfold');
    const icon = unfoldButton?.querySelector('.icon');

    const setFolded = (folded) => {
      wrapper.classList.toggle(CLASS_FOLDED, folded);
      wrapper.querySelectorAll('.code-fold-toggle, .code-fold').forEach((element) => {
        element.classList.remove(CLASS_OPEN);
      });

      if (!unfoldButton) {
        return;
      }
      unfoldButton.setAttribute('aria-pressed', String(!folded));
      unfoldButton.title = folded ? 'Show all lines' : 'Fold lines';
      icon?.classList.toggle(ICON_UNFOLD, folded);
      icon?.classList.toggle(ICON_FOLD, !folded);
    };

    wrapper.querySelectorAll('.code-fold-toggle').forEach((toggle) => {
      toggle.addEventListener('click', () => {
        toggle.classList.add(CLASS_OPEN);
        toggle.nextElementSibling?.classList.add(CLASS_OPEN);
        if (!wrapper.querySelector(`.code-fold-toggle:not(.${CLASS_OPEN})`)) {
          setFolded(false);
        }
      });
    });

    unfoldButton?.addEventListener('click', () => {
      setFolded(!wrapper.classList.contains(CLASS_FOLDED));
    });
    unfoldButton?.removeAttribute('hidden');

    setFolded(true);
  });
})();
