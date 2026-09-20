/**
 * Without a clipboard -- a page served over plain http from any host but
 * localhost gets none -- the copy button script threw on loading. All scripts
 * are bundled into one file, so every script after it stopped as well: the
 * menu toggles, search, and the version switch among them.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

describe('code block copy button', () => {
    beforeEach(() => {
        vi.resetModules();
        document.body.innerHTML = '<div class="code-block-wrapper"><pre class="code-block">echo 1;</pre><button class="code-block-copy"></button></div>';
    });

    it('loads without a clipboard', async () => {
        vi.stubGlobal('navigator', { ...navigator, clipboard: undefined });
        const info = vi.spyOn(console, 'info').mockImplementation(() => {});

        await expect(import('../../assets/js/code-clipboard.js')).resolves.toBeDefined();
        expect(info).toHaveBeenCalled();

        vi.unstubAllGlobals();
        info.mockRestore();
    });
});
