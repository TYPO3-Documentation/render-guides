document.addEventListener('DOMContentLoaded', () => {
  const toggleButton = document.getElementById('options-toggle');
  const panel = document.getElementById('options-panel');

  function togglePanel() {
    const isVisible = panel.classList.contains('show');
    panel.classList.toggle('show', !isVisible);
    panel.setAttribute('aria-hidden', isVisible ? 'true' : 'false');
  }

  toggleButton.addEventListener('click', (e) => {
    e.stopPropagation();
    togglePanel();
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target) && e.target !== toggleButton) {
      panel.classList.remove('show');
      panel.setAttribute('aria-hidden', 'true');
    }
  });
});
