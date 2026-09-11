(() => {
  window.addEventListener("load", () => {
    const SELECTOR_SEARCH_SCOPE_SELECT_LIST = 'searchscope';
    const regex = /^\/(c|m|p|h|other)\/[A-Za-z0-9\-_]+\/[A-Za-z0-9\-_]+\/[A-Za-z0-9\-.]+\/[A-Za-z0-9\-]+\/(Changelog\/[A-Za-z0-9\-.]+\/)?/;
    const path = window.location.pathname;
    const match = path.match(regex);
    const manualPath = match ? match[0] : null;

    if (manualPath) {
      const searchScopeSelectList = document.getElementById(SELECTOR_SEARCH_SCOPE_SELECT_LIST);

      // The scope select lives in the theme's page header. A project that
      // overrides that template can match the manual URL pattern without
      // rendering it, and the add() below would then dereference null and
      // abort the remaining page scripts.
      if (!searchScopeSelectList) {
        return;
      }

      const newOption = document.createElement('option');
      newOption.value = manualPath;
      newOption.text = 'Search current';
      // by default the first option (Search all) is selected
      // newOption.setAttribute('selected', 'selected');
      searchScopeSelectList.add(newOption);
    }
  });
})();
