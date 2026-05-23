/**
 * Regression test for issue #120:
 *   "Search in current" via keyboard does not work.
 *
 * Steps to reproduce on the live site:
 *   1. open a manual page (e.g. brotkrueml/schema)
 *   2. open the search modal, type a query
 *   3. press ArrowDown until the "Search in current" suggestion is active
 *   4. press Enter
 *
 * Expected: navigate to the scoped search URL.
 * Actual (bug): navigate to the un-scoped /search/search?q=… URL.
 *
 * Root cause: handleKeyDown's useCallback misses `activeIndex` in its
 * dependency array, so the closure captures a stale activeIndex=-1
 * and falls through to the else-branch on Enter.
 */
import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, act } from '@testing-library/react';
import SearchModal from '../../resources/js/components/SearchModal';

beforeEach(() => {
    // useSearchSuggestions wraps fetchSuggestions in lodash.debounce(…, 300).
    // Fake timers prevent the 300ms callback from firing after the test
    // ends and producing state updates outside act().
    vi.useFakeTimers();

    // HTMLDialogElement is unsupported in jsdom; provide minimal polyfills so
    // dialog.showModal()/close() do not throw and the modal renders normally.
    HTMLDialogElement.prototype.showModal = function () { this.open = true; };
    HTMLDialogElement.prototype.close = function () { this.open = false; };

    // The hook tries to fetch from PROXY_URL on every keystroke; keep the
    // response shape valid so debounce callbacks (if any leak past timer
    // cleanup) don't throw.
    globalThis.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ results: [], suggest: { suggestions: {} } }),
    });

    // Simulate the page-header's #searchscope <select> populated by
    // search-form.js when viewing a specific manual. The SearchModal reads
    // children[1].value to determine the current scope.
    document.body.replaceChildren();
    const select = document.createElement('select');
    select.id = 'searchscope';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Search all';
    const opt1 = document.createElement('option');
    opt1.value = '/p/brotkrueml/schema/3.11/en-us/';
    opt1.textContent = 'Search current';
    select.append(opt0, opt1);
    const modalRoot = document.createElement('div');
    modalRoot.id = 'modal-root';
    document.body.append(select, modalRoot);

    // Stub navigation: capture writes to window.location.href instead of
    // letting jsdom warn about "not implemented: navigation". The initial
    // href must remain a valid URL because useSearchScopes constructs
    // `new URL(window.location.href)`.
    const initialHref = 'https://docs.typo3.org/p/brotkrueml/schema/3.11/en-us/Developer/Breadcrumb.html';
    delete window.location;
    window.location = {
        href: initialHref,
        pathname: '/p/brotkrueml/schema/3.11/en-us/Developer/Breadcrumb.html',
        search: '',
        searchParams: new URL(initialHref).searchParams,
    };
});

afterEach(() => {
    vi.useRealTimers();
});

describe('SearchModal keyboard navigation — issue #120', () => {
    it('Enter on the "Search in current" suggestion navigates to the scoped URL', async () => {
        render(<SearchModal isOpen={true} onClose={() => {}} />, {
            container: document.getElementById('modal-root'),
        });

        const input = screen.getByPlaceholderText(/search documentation/i);

        // Type a query. This populates `searchQuery`, which makes
        // decomposedScopes render two entries: "Search all" and
        // "Search in current".
        await act(async () => {
            fireEvent.change(input, { target: { value: 'xmlns' } });
        });

        // Two ArrowDown presses move the active highlight from -1 to 1
        // (i.e. the second entry: "Search in current").
        await act(async () => {
            fireEvent.keyDown(input, { key: 'ArrowDown' });
            fireEvent.keyDown(input, { key: 'ArrowDown' });
        });

        // Capture which <a> the keyboard handler ends up clicking. The
        // listener also cancels the default action so jsdom doesn't emit
        // "Not implemented: navigation" when the anchor is clicked.
        let clickedHref = null;
        const captureClick = (e) => {
            const anchor = e.target.closest('a');
            if (anchor) {
                clickedHref = anchor.getAttribute('href');
                e.preventDefault();
            }
        };
        document.addEventListener('click', captureClick, { capture: true, once: true });

        try {
            await act(async () => {
                fireEvent.keyDown(input, { key: 'Enter' });
            });

            // The fix must reach the highlighted "Search in current" anchor
            // (carries the manual scope + version filter). Before the fix,
            // the stale closure routed Enter into the else-branch, writing
            // an un-scoped URL into window.location.href instead — so we
            // first assert the navigation actually went through an anchor
            // click (which kills the failure mode where window.location is
            // set even though it happens to also contain `scope=`).
            expect(clickedHref).not.toBeNull();
            expect(clickedHref).toMatch(/scope=/);
            expect(clickedHref).toContain('brotkrueml%2Fschema');
            expect(clickedHref).toMatch(/[?&]q=xmlns/);
        } finally {
            document.removeEventListener('click', captureClick, { capture: true });
        }
    });
});
