/**
 * Regression test for the search scope option (search-form.js).
 *
 * The "Search current" option is added when the URL matches a manual path, but
 * the select it is added to was looked up without a null check. A page header
 * that does not render #searchscope — possible since templates became
 * overridable — made the add() call dereference null and throw, which aborts
 * the remaining page scripts.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Importing the script registers its load handler once.
import '../../assets/js/search-form.js';

const MANUAL_PATH = '/m/typo3/tutorial-getting-started/12.4/en-us/';

function visitManualPage() {
  window.history.pushState({}, '', MANUAL_PATH + 'Concepts/Index.html');
}

// Errors thrown inside an event listener do not propagate to the dispatchEvent
// caller — the DOM reports them instead. Listening for them is what makes the
// guard actually testable.
function captureListenerErrors() {
  const errors = [];
  const onError = (event) => {
    event.preventDefault();
    errors.push(event.error ?? event.message);
  };
  window.addEventListener('error', onError);
  return {
    errors,
    stop: () => window.removeEventListener('error', onError),
  };
}

describe('search scope option', () => {
  let capture;

  beforeEach(() => {
    document.body.replaceChildren();
    capture = captureListenerErrors();
  });

  afterEach(() => {
    capture.stop();
  });

  it('adds a "Search current" option scoped to the manual', () => {
    const select = document.createElement('select');
    select.id = 'searchscope';
    document.body.appendChild(select);
    visitManualPage();

    window.dispatchEvent(new Event('load'));

    expect(Array.from(select.options).map(option => option.value)).toContain(MANUAL_PATH);
    expect(capture.errors).toEqual([]);
  });

  it('does not throw when the page header has no scope select', () => {
    // URL matches a manual path, but #searchscope is absent.
    visitManualPage();

    window.dispatchEvent(new Event('load'));

    expect(capture.errors).toEqual([]);
  });

  it('adds nothing outside a manual path', () => {
    const select = document.createElement('select');
    select.id = 'searchscope';
    document.body.appendChild(select);
    window.history.pushState({}, '', '/search/search?q=example');

    window.dispatchEvent(new Event('load'));

    expect(select.options).toHaveLength(0);
    expect(capture.errors).toEqual([]);
  });
});
