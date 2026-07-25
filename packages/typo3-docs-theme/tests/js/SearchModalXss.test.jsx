/**
 * Security regression test for GHSA-m5h8-v326-qjw7:
 *   Reflected / DOM cross-site scripting in the documentation search modal.
 *
 * The search query — taken from the ?q= URL parameter and from live typing —
 * was rendered through dangerouslySetInnerHTML in SuggestRow's no-scope
 * branch, so a payload like <img src=x onerror=...> executed. These tests lock
 * in that:
 *   1. a user-controlled title is rendered as inert text, and
 *   2. legitimate backend highlight markup (<em>/<mark>) is still preserved,
 *      but stripped of every other tag and every attribute.
 */
import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render } from '@testing-library/react';
import SuggestRow from '../../resources/js/components/SuggestRow';
import SearchModal from '../../resources/js/components/SearchModal';

const IMG_PAYLOAD = '<img src=x onerror="window.__xss = true">';

describe('SuggestRow — XSS sink hardening (GHSA-m5h8-v326-qjw7)', () => {
    it('renders a user-controlled title (default, no titleIsHtml) as inert text', () => {
        const { container } = render(<SuggestRow title={IMG_PAYLOAD} />);
        expect(container.querySelector('img')).toBeNull();
        expect(container.querySelector('[onerror]')).toBeNull();
        expect(container.querySelector('.suggest-row__title').textContent).toBe(IMG_PAYLOAD);
    });

    it('keeps <em>/<mark> highlight but strips other tags and all attributes when titleIsHtml', () => {
        const { container } = render(
            <SuggestRow
                titleIsHtml
                title={`<em>Route</em>${IMG_PAYLOAD}<mark class="x" onmouseover="alert(1)">Enhancer</mark>`}
            />
        );
        expect(container.querySelector('em')).not.toBeNull();
        expect(container.querySelector('mark')).not.toBeNull();
        expect(container.querySelector('img')).toBeNull();
        expect(container.querySelector('[onerror]')).toBeNull();
        // The preserved allowed tag keeps no attributes after sanitization.
        expect(container.querySelector('mark').attributes.length).toBe(0);
        // The disallowed <img> is dropped entirely (it carries no text).
        expect(container.querySelector('.suggest-row__title').textContent).toBe('RouteEnhancer');
    });
});

describe('SearchModal — reflected XSS via ?q= (GHSA-m5h8-v326-qjw7)', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        HTMLDialogElement.prototype.showModal = function () { this.open = true; };
        HTMLDialogElement.prototype.close = function () { this.open = false; };
        globalThis.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ results: [], suggest: { suggestions: {} } }),
        });
        document.body.replaceChildren();
        const modalRoot = document.createElement('div');
        modalRoot.id = 'modal-root';
        document.body.append(modalRoot);
        const href = 'https://docs.typo3.org/?q=' + encodeURIComponent(IMG_PAYLOAD);
        delete window.location;
        window.location = {
            href,
            pathname: '/',
            search: new URL(href).search,
            searchParams: new URL(href).searchParams,
        };
        window.__xss = undefined;
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('echoes the reflected q payload as inert text, not an <img> element', () => {
        render(<SearchModal isOpen={true} onClose={() => {}} />, {
            container: document.getElementById('modal-root'),
        });
        const root = document.getElementById('modal-root');
        expect(root.querySelector('img')).toBeNull();
        expect(root.querySelector('[onerror]')).toBeNull();
        const titles = [...root.querySelectorAll('.suggest-row__title')].map((el) => el.textContent);
        expect(titles.some((t) => t.includes('onerror'))).toBe(true);
        expect(window.__xss).toBeUndefined();
    });
});
