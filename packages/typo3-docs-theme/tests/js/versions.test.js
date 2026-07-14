/**
 * Regression test for the version switcher (versions.js).
 *
 * Two bugs on packages that have both "0.1" and "0.10" (e.g. netresearch/nr-vault):
 *   1. Sorting used parseFloat(), so parseFloat("0.10") === 0.1 and "0.10"
 *      sorted together with the 0.x group at the bottom instead of first.
 *   2. Pre-selection used the rendered data-current-version attribute, which is
 *      "0.1" on the 0.10 page (server-side numeric coercion of "0.10"), so the
 *      dropdown pre-selected the wrong entry.
 *
 * The fix sorts each dotted component numerically and derives the active
 * version from the page URL.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

// Importing the script registers its DOMContentLoaded handler once.
import '../../assets/js/versions.js';

function versionList(versions) {
  return versions.map(version => ({
    version,
    language: 'en-us',
    url: `/p/netresearch/pkg/${version}/en-us/Index.html`,
  }));
}

// netresearch/nr-vault: has both 0.1 and 0.10.
const NR_VAULT_VERSIONS = versionList(['0.1', '0.10', '0.2', '0.9', 'main']);

// netresearch/nr-llm: multi-digit minors 0.10, 0.11, 0.12.
const NR_LLM_VERSIONS = versionList(
  ['0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.12', '0.11', '0.1', '0.10', 'main']);

function setup(currentUrl, dataCurrentVersion, versions) {
  document.body.replaceChildren();

  const languageSelect = document.createElement('select');
  languageSelect.id = 'languageSelect';

  const versionSelect = document.createElement('select');
  versionSelect.id = 'versionSelect';
  versionSelect.setAttribute('data-current-version', dataCurrentVersion);
  versionSelect.setAttribute('data-override-url-self', currentUrl);

  document.body.append(languageSelect, versionSelect);

  global.fetch = vi.fn(() =>
    Promise.resolve({ ok: true, json: () => Promise.resolve(versions) }));

  return versions.length;
}

async function runAndWait(expectedCount) {
  document.dispatchEvent(new Event('DOMContentLoaded'));
  await vi.waitFor(() => {
    const options = document.querySelectorAll('#versionSelect option');
    expect(options.length).toBe(expectedCount);
  });
  return [...document.querySelectorAll('#versionSelect option')];
}

describe('version switcher', () => {
  it('sorts 0.10 above 0.9 and the 0.x group (not last)', async () => {
    // On the 0.10 page the server renders data-current-version="0.1" (coercion).
    const count = setup(
      'https://docs.typo3.org/p/netresearch/nr-vault/0.10/en-us/Index.html', '0.1', NR_VAULT_VERSIONS);
    const order = (await runAndWait(count)).map(option => option.textContent);

    expect(order).toEqual(['main', '0.10', '0.9', '0.2', '0.1']);
  });

  it('pre-selects the version from the URL (0.10), not the coerced attribute (0.1)', async () => {
    const count = setup(
      'https://docs.typo3.org/p/netresearch/nr-vault/0.10/en-us/Index.html', '0.1', NR_VAULT_VERSIONS);
    const selected = (await runAndWait(count)).find(option => option.selected);

    expect(selected.textContent).toBe('0.10');
  });

  it('orders multi-digit minors correctly (nr-llm: 0.12, 0.11, 0.10, 0.9, …)', async () => {
    const count = setup(
      'https://docs.typo3.org/p/netresearch/nr-llm/0.12/en-us/Index.html', '0.12', NR_LLM_VERSIONS);
    const options = await runAndWait(count);

    expect(options.map(option => option.textContent)).toEqual(
      ['main', '0.12', '0.11', '0.10', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1']);
    expect(options.find(option => option.selected).textContent).toBe('0.12');
  });

  it('orders TYPO3 LTS versions numerically (12.4 > 11.5 > 10.4 > 9.5 > 8.7)', async () => {
    const versions = versionList(['8.7', '9.5', '10.4', '11.5', '12.4', 'main']);
    const count = setup(
      'https://docs.typo3.org/p/netresearch/pkg/12.4/en-us/Index.html', '12.4', versions);
    const order = (await runAndWait(count)).map(option => option.textContent);

    // Lexicographic comparison would yield "9.5" > "10.4"; numeric must not.
    expect(order).toEqual(['main', '12.4', '11.5', '10.4', '9.5', '8.7']);
  });

  it('sorts non-numeric labels like "draft" below all numbered releases (real reference-coreapi list)', async () => {
    // Actual version set served by versionsJson.php for m/typo3/reference-coreapi.
    const versions = versionList(
      ['draft', '13.4', '6.2', 'main', '14.3', '8.7', '12.4', '7.6', '9.5', '10.4', '11.5']);
    const count = setup(
      'https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Index.html', '13.4', versions);
    const order = (await runAndWait(count)).map(option => option.textContent);

    expect(order).toEqual(
      ['main', '14.3', '13.4', '12.4', '11.5', '10.4', '9.5', '8.7', '7.6', '6.2', 'draft']);
  });

  it('falls back to data-current-version when the URL contains no version segment', async () => {
    const count = setup('https://example.org/some/preview/page.html', '0.9', NR_VAULT_VERSIONS);
    const selected = (await runAndWait(count)).find(option => option.selected);

    expect(selected.textContent).toBe('0.9');
  });

  it('orders versions with a differing number of components (13.4.21 > 13.4 > 13.3)', async () => {
    const versions = versionList(['13.3', '13.4', '13.4.21', '14.0', 'main']);
    const count = setup(
      'https://docs.typo3.org/m/typo3/manual/13.4/en-us/Index.html', '13.4', versions);
    const order = (await runAndWait(count)).map(option => option.textContent);

    expect(order).toEqual(['main', '14.0', '13.4.21', '13.4', '13.3']);
  });
});
