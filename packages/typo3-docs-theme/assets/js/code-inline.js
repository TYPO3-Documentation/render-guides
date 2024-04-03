(() => {
  var popoverTriggerList = [].slice.call(document.querySelectorAll('.code-inline[aria-description]'));
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    var ariaDescription = popoverTriggerEl.getAttribute('aria-description');
    var ariaDetails = popoverTriggerEl.getAttribute('aria-details');
    return new bootstrap.Popover(popoverTriggerEl, {
      title:ariaDetails?ariaDescription:'',
      content:  ariaDetails?ariaDetails:ariaDescription,
      trigger: 'hover',
      placement: 'bottom'
    });
  });
})();
