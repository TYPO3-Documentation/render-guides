/**
 * The "/permalink/" route on docs.typo3.org resolves anchors only. For a
 * headline without an anchor, the reST field offered a permalink built from
 * the file path, "manual:Path/Index#section", which answers 404. It now offers
 * the page URL, the same as the Markdown and HTML fields.
 *
 * The modal script is a side-effecting IIFE that captures #linkReferenceModal
 * at import time, so each test resets the module registry and imports the file
 * after building the DOM.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

const setupDom = (currentFilename) => {
    document.body.innerHTML = `
      <section class="section" id="news" data-rst-anchor="news">
        <h1>News<a class="headerlink" href="#news" title="Reference this headline"></a></h1>
        <section class="section" id="some-further-explanations">
          <h2>Some further explanations<a class="headerlink" href="#some-further-explanations" title="Reference this headline"></a></h2>
        </section>
      </section>
      <div id="linkReferenceModal" data-current-filename="${currentFilename}" data-interlink-shortcode="georgringer/news">
        <h5></h5>
        <div id="permalink-alert-success" class="d-none"></div>
        <div class="permalink-short-wrapper"><input id="permalink-short"></div>
        <input id="permalink-uri">
        <div class="alert-permalink-rst"></div>
        <div><textarea id="permalink-rst"></textarea></div>
        <textarea id="permalink-md"></textarea>
        <textarea id="permalink-html"></textarea>
      </div>`;
};

const openModalFor = (headingSelector) => {
    const event = new Event('show.bs.modal');
    event.relatedTarget = document.querySelector(`${headingSelector} .headerlink`);
    document.getElementById('linkReferenceModal').dispatchEvent(event);
};

const value = (id) => document.getElementById(id).value;
const isHidden = (id) => document.getElementById(id).closest('.d-none') !== null;
const pageUrl = () => `${window.location.origin}${window.location.pathname}`;

describe('link reference modal', () => {
    beforeEach(() => {
        vi.resetModules();
        // jsdom has no innerText, which the modal reads the headline from
        if (!('innerText' in HTMLElement.prototype)) {
            Object.defineProperty(HTMLElement.prototype, 'innerText', {
                get() { return this.textContent; },
                configurable: true,
            });
        }
    });

    it('offers the permalink of a headline with an anchor', async () => {
        setupDom('Tutorials/Index');
        await import('../../assets/js/link-reference-modal.js');
        openModalFor('h1');

        const permalink = 'https://docs.typo3.org/permalink/georgringer-news:news';
        expect(value('permalink-short')).toBe(permalink);
        expect(value('permalink-rst')).toBe(`\`News <${permalink}>\`_`);
        expect(value('permalink-md')).toBe(`[News](${permalink})`);
        expect(isHidden('permalink-short')).toBe(false);
    });

    it('offers the page URL in reST for a headline without an anchor', async () => {
        setupDom('Tutorials/Index');
        await import('../../assets/js/link-reference-modal.js');
        openModalFor('h2');

        const url = `${pageUrl()}#some-further-explanations`;
        expect(value('permalink-rst')).toBe(`\`Some further explanations <${url}>\`_`);
        expect(value('permalink-rst')).not.toContain('/permalink/');
        expect(value('permalink-md')).toBe(`[Some further explanations](${url})`);
        expect(isHidden('permalink-short')).toBe(true);
        expect(isHidden('permalink-rst')).toBe(false);
    });

    it('offers no reST link in the single HTML file for a headline without an anchor', async () => {
        setupDom('');
        await import('../../assets/js/link-reference-modal.js');
        openModalFor('h2');

        expect(isHidden('permalink-rst')).toBe(true);
    });
});
