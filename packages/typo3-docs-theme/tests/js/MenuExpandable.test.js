/**
 * Regression guard for #1037: the toggles of the menu were added only once the
 * "All documentation" menu had been loaded, which a local render cannot do, so
 * its own menu had none.
 *
 * The script is a side-effecting IIFE, so each test resets the module registry
 * and imports it after building the DOM.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

const pageMenu = `
    <button id="toc-toggle"></button>
    <div class="main_menu">
        <ul class="menu-level-1">
            <li><a href="a.html">With sub-entries</a><ul class="menu-level-2">
                <li><a href="a/b.html">Leaf</a></li>
            </ul></li>
            <li><a href="c.html">Leaf too</a></li>
        </ul>
    </div>
`;

const toggles = () => document.querySelectorAll('.main_menu .toctree-expand');

describe('menu toggles', () => {
    beforeEach(() => {
        vi.resetModules();
        document.body.innerHTML = pageMenu;
    });

    it('adds a toggle to each entry with sub-entries without waiting for the documentation menu', async () => {
        await import('../../assets/js/menu-expandable.js');

        expect(toggles()).toHaveLength(1);
        expect(toggles()[0].getAttribute('aria-label')).toBe('Toggle With sub-entries');
    });

    it('adds the toggles of the documentation menu once it is loaded, and no second one to the page menu', async () => {
        await import('../../assets/js/menu-expandable.js');

        const allDocumentation = document.createElement('div');
        allDocumentation.className = 'main_menu';
        allDocumentation.innerHTML = '<ul><li><a href="#">Guides</a><ul><li><a href="/g">Guide</a></li></ul></li></ul>';
        document.body.appendChild(allDocumentation);
        window.dispatchEvent(new CustomEvent('all-documentation-menu-loaded'));

        expect(toggles()).toHaveLength(2);
        expect(allDocumentation.querySelectorAll('.toctree-expand')).toHaveLength(1);
    });

    it('opens an entry with its toggle', async () => {
        await import('../../assets/js/menu-expandable.js');

        toggles()[0].click();

        expect(toggles()[0].parentElement.classList.contains('active')).toBe(true);
        expect(toggles()[0].getAttribute('aria-expanded')).toBe('true');
    });
});
