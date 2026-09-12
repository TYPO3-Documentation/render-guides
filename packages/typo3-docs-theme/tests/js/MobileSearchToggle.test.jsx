/**
 * Regression guard for #1391: tapping #search-toggle on mobile used to
 * reveal a panel containing nothing but another "Search" button instead
 * of opening the search modal directly.
 *
 * Covers both entry points into SearchModal: the mobile #search-toggle
 * (forwarded via menu-expandable.js) and GlobalSearch's own visible
 * desktop button.
 */
import React from 'react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import GlobalSearch from '../../resources/js/components/GlobalSearch';

beforeEach(() => {
    vi.resetModules();

    // HTMLDialogElement is unsupported in jsdom; same polyfill as SearchModal.test.jsx.
    HTMLDialogElement.prototype.showModal = function () { this.open = true; };
    HTMLDialogElement.prototype.close = function () { this.open = false; };

    document.body.innerHTML = `
        <button id="toc-toggle"></button>
        <button id="search-toggle"></button>
        <search><div id="global-search-root"></div></search>
    `;
});

describe('search modal trigger', () => {
    it('mobile: #search-toggle opens the modal', async () => {
        render(<GlobalSearch />, { container: document.getElementById('global-search-root') });

        await import('../../assets/js/menu-expandable.js');

        act(() => {
            document.getElementById('search-toggle').click();
        });

        expect(document.getElementById('search-modal')?.open).toBe(true);
    });

    it('desktop: the GlobalSearch button opens the modal directly', () => {
        render(<GlobalSearch />, { container: document.getElementById('global-search-root') });

        act(() => {
            screen.getByRole('button', { name: /search/i }).click();
        });

        expect(document.getElementById('search-modal')?.open).toBe(true);
    });
});
