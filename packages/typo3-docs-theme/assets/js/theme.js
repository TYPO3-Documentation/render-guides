/**
 * Entry point for `resources/public/js/theme.min.js`.
 *
 * Every module here registers its own listeners or custom elements when it is
 * evaluated, so none of them export anything and the imports are for their
 * side effects alone. The one exception is
 * `all-documentations-menu-base.js`, which the two menu modules import a class
 * from.
 *
 * The order below is the alphabetical order the previous glob-and-concatenate
 * build happened to produce. Nothing is known to depend on it -- the modules
 * attach `DOMContentLoaded` and `load` handlers, or, like `code-fold.js`, work
 * on a DOM that is already parsed because the bundle loads at the end of
 * `<body>` -- but keeping it costs nothing and rules out a difference nobody
 * would look for.
 */
import './all-documentations-menu-base.js';
import './all-documentations-menu-mobile.js';
import './all-documentations-menu.js';
import './code-clipboard.js';
import './code-fold.js';
import './code-inline.js';
import './composer-modal.js';
import './file-modal.js';
import './image-zoom.js';
import './link-reference-modal.js';
import './menu-expandable.js';
import './scrolling.js';
import './search-form.js';
import './tabs-sync.js';
import './toggle-options.js';
import './toggle-section.js';
import './versions.js';

// The global search application, previously built into `assets/js/search.js`
// by a separate vite pass and then minified a second time as part of the
// concatenation. It is a normal module of this bundle now.
import '../../resources/js/search.jsx';
