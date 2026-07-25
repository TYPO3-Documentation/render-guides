/**
 * Security regression tests for GHSA-fx4m-7fr2-h279:
 *   Second-order DOM-XSS via doc-author data-* attributes.
 *
 * The info modals (file / code / composer) read data-* attributes off the
 * clicked element and concatenated them straight into innerHTML, so
 * doc-author (and third-party package) content such as
 * <img src=x onerror=...> executed, and javascript: URLs reached href="".
 * These tests lock in that data-* values are escaped as text and that only
 * http(s) links are rendered.
 *
 * The modal scripts are side-effecting IIFEs that capture #generalModal at
 * import time, so each test resets the module registry and dynamically
 * imports the file under test after building the DOM.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

const IMG_PAYLOAD = '<img src=x onerror="window.__xss = true">';

const setupModalDom = () => {
    document.body.innerHTML = `
      <div id="generalModal">
        <span id="generalModalLabel"></span>
        <div id="generalModalContent"></div>
        <div id="generalModalCustomButtons"></div>
        <div id="general-alert-success" class="d-none"></div>
      </div>`;
};

const openModal = (dataset) => {
    const trigger = document.createElement('a');
    Object.assign(trigger.dataset, dataset);
    const event = new Event('show.bs.modal');
    event.relatedTarget = trigger;
    document.getElementById('generalModal').dispatchEvent(event);
};

const assertNoInjection = () => {
    const root = document.getElementById('generalModal');
    // The handler ran and rendered the payload as inert TEXT. This is the
    // discriminating check: on the unfixed code the same input produced a live
    // <img onerror> element instead of the literal text.
    expect(document.getElementById('generalModalContent').textContent).toContain('onerror');
    expect(root.querySelector('img')).toBeNull();
    expect(root.querySelector('[onerror]')).toBeNull();
    const hrefs = [...root.querySelectorAll('a')].map((a) => a.getAttribute('href') || '');
    expect(hrefs.some((h) => h.startsWith('javascript:'))).toBe(false);
    expect(window.__xss).toBeUndefined();
};

describe('doc-author data-* DOM-XSS hardening (GHSA-fx4m-7fr2-h279)', () => {
    beforeEach(() => {
        vi.resetModules();
        window.__xss = undefined;
        setupModalDom();
    });

    it('file-modal escapes data-* content and drops a javascript: source link', async () => {
        await import('../../assets/js/file-modal.js');
        openModal({
            filename: 'Foo.php',
            shortdescription: IMG_PAYLOAD,
            composerpath: 'vendor/',
            classicpath: 'typo3conf/',
            source: 'javascript:window.__xss = true',
            issues: 'https://github.com/x/y/issues',
        });
        assertNoInjection();
        // the legitimate https issues link is still rendered
        const hrefs = [...document.getElementById('generalModal').querySelectorAll('a')].map((a) => a.href);
        expect(hrefs.some((h) => h.startsWith('https://github.com/'))).toBe(true);
    });

    it('code-inline escapes data-details / data-code', async () => {
        await import('../../assets/js/code-inline.js');
        openModal({ code: 'foo()', details: IMG_PAYLOAD, morelink: 'javascript:window.__xss = true' });
        assertNoInjection();
    });

    it('composer-modal escapes data-description and validates hrefs', async () => {
        await import('../../assets/js/composer-modal.js');
        openModal({
            composername: 'vendor/pkg',
            description: IMG_PAYLOAD,
            composercommand: 'composer req vendor/pkg',
            source: 'javascript:window.__xss = true',
        });
        assertNoInjection();
    });

    it('file-modal neutralizes a double-quote in a URL host (href attribute breakout)', async () => {
        // new URL() leaves a literal " unescaped in the host, so an un-escaped
        // href="" interpolation would let this add an onmouseover handler.
        await import('../../assets/js/file-modal.js');
        openModal({
            filename: 'Foo.php',
            composerpath: 'vendor/',
            classicpath: 'typo3conf/',
            source: 'https://x"onmouseover=window.__xss=1//',
        });
        const root = document.getElementById('generalModal');
        expect(root.querySelector('[onmouseover]')).toBeNull();
        // no anchor carries an attribute other than the expected class/href
        [...root.querySelectorAll('a')].forEach((a) => {
            expect(a.getAttribute('onmouseover')).toBeNull();
        });
        expect(window.__xss).toBeUndefined();
    });
});
