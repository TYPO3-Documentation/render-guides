/**
 * Security regression test for the open-redirect sibling of
 * GHSA-m5h8-v326-qjw7:
 *   ?scope= accepted a backslash that the "/" split did not collapse, so
 *   buildHref("/" + slug) produced a protocol-relative-equivalent target
 *   ("/\evil.example" resolves to https://evil.example/).
 *
 * useSearchScopes must normalize backslashes like slashes so the derived slug
 * can never introduce a foreign origin.
 */
import { describe, it, expect } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useSearchScopes } from '../../resources/js/hooks/useSearchScopes';

const setLocation = (href) => {
    delete window.location;
    window.location = {
        href,
        pathname: new URL(href).pathname,
        search: new URL(href).search,
        searchParams: new URL(href).searchParams,
    };
};

const BASE = 'https://docs.typo3.org/m/typo3/x/main/en-us/Index.html';

describe('useSearchScopes — ?scope= open-redirect hardening', () => {
    it('collapses a backslash in the scope slug so it cannot yield a foreign origin', () => {
        setLocation(`${BASE}?scope=${encodeURIComponent('\\evil.example')}`);
        const { result } = renderHook(() => useSearchScopes());
        const [scopes] = result.current;
        expect(scopes).toHaveLength(1);
        const { slug } = scopes[0];
        expect(slug.startsWith('\\')).toBe(false);
        expect(slug).not.toMatch(/[\\/]{2,}/);
        // The value buildHref would produce ("/" + slug) must resolve same-origin.
        expect(new URL('/' + slug, BASE).origin).toBe('https://docs.typo3.org');
    });

    // The URL parser strips ASCII whitespace/control chars (tab, LF, CR) and
    // treats "\" as "/", so any of these smuggled before "//host" would make
    // "/" + slug resolve off-origin unless the slug drops them.
    it.each([
        ['backslash', '\\evil.example'],
        ['double-slash', '//evil.example'],
        ['tab + //', '\t//evil.example'],
        ['LF + //', '\n//evil.example'],
        ['CR + //', '\r//evil.example'],
        ['tab + /', '\t/evil.example'],
    ])('keeps the target same-origin for a %s payload', (_name, payload) => {
        setLocation(`${BASE}?scope=${encodeURIComponent(payload)}`);
        const { result } = renderHook(() => useSearchScopes());
        const [scopes] = result.current;
        expect(scopes).toHaveLength(1);
        const { slug } = scopes[0];
        expect(new URL('/' + slug, BASE).origin).toBe('https://docs.typo3.org');
    });

    it('still parses a normal slash-separated scope slug', () => {
        setLocation(`${BASE}?scope=${encodeURIComponent('/typo3/reference-coreapi/main/')}`);
        const { result } = renderHook(() => useSearchScopes());
        const [scopes] = result.current;
        expect(scopes).toHaveLength(1);
        expect(scopes[0].slug).toBe('typo3/reference-coreapi/main');
        expect(new URL('/' + scopes[0].slug, BASE).origin).toBe('https://docs.typo3.org');
    });
});
