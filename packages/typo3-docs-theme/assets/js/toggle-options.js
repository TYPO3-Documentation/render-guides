document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('options-toggle');
  const panel = document.getElementById('options-panel');
  if (!button || !panel) return;

  panel.addEventListener('toggle', (e) => {
    button.setAttribute('aria-expanded', e.newState === 'open');
  });

  panel.addEventListener('focusout', (e) => {
    if (!panel.contains(e.relatedTarget)) {
      panel.hidePopover();
    }
  });
});
