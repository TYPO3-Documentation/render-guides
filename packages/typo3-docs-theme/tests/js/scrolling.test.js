/**
 * Regression guard for links to the top of the page: the click handler on all
 * in-page links cancelled the browser's own jump for href="#" and then found
 * no hash to scroll to, so the "Back to top" link of a confval, the menu entry
 * of the current page and a reference to the page's own title did nothing.
 *
 * The script registers on DOMContentLoaded, so each test resets the module
 * registry, imports it and fires the event itself.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

const click = (selector) => {
    const event = new MouseEvent('click', { bubbles: true, cancelable: true });
    document.querySelector(selector).dispatchEvent(event);
    return event;
};

describe('in-page links', () => {
    beforeEach(async () => {
        vi.resetModules();
        history.replaceState(null, '', '/');
        document.body.innerHTML = `
            <a href="#top" class="backToTop">Back to top</a>
            <a href="#" aria-current="page">Current page</a>
            <a href="#section" class="backToList">Back to list</a>
            <div id="section"></div>
        `;
        window.scrollTo = vi.fn();
        await import('../../assets/js/scrolling.js');
        document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('scrolls to the top for "#top"', () => {
        click('.backToTop');

        expect(window.location.hash).toBe('#top');
        expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });

    it('leaves a bare "#" to the browser, which jumps to the top', () => {
        expect(click('[aria-current="page"]').defaultPrevented).toBe(false);
    });

    it('still handles a link to an anchor itself', () => {
        expect(click('.backToList').defaultPrevented).toBe(true);
        expect(window.location.hash).toBe('#section');
    });
});
