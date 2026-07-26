/**
 * Rendering tests for the code info modal details block.
 *
 * The class information used to arrive as an HTML blob in data-details, which
 * the XSS hardening then escaped, so the markup became visible text. The parts
 * now travel as plain text in data-signature / data-flags / data-summary and
 * the markup is written by the modal itself.
 *
 * The modal script is a side-effecting IIFE that captures #generalModal at
 * import time, so each test resets the module registry and imports it after
 * building the DOM.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

const setupModalDom = () => {
    document.body.replaceChildren();
    const modal = document.createElement('div');
    modal.id = 'generalModal';
    for (const [tag, id] of [
        ['span', 'generalModalLabel'],
        ['div', 'generalModalContent'],
        ['div', 'generalModalCustomButtons'],
        ['div', 'general-alert-success'],
    ]) {
        const child = document.createElement(tag);
        child.id = id;
        modal.appendChild(child);
    }
    document.body.appendChild(modal);
};

const openModal = async (dataset) => {
    await import('../../assets/js/code-inline.js');
    const trigger = document.createElement('a');
    Object.assign(trigger.dataset, dataset);
    const event = new Event('show.bs.modal');
    event.relatedTarget = trigger;
    document.getElementById('generalModal').dispatchEvent(event);
    return document.getElementById('generalModalContent');
};

describe('code info modal details', () => {
    beforeEach(() => {
        vi.resetModules();
        window.__xss = undefined;
        setupModalDom();
    });

    it('renders signature, flags and summary as markup', async () => {
        const content = await openModal({
            code: '\\TYPO3\\CMS\\Core\\Log\\LogManager',
            signature: 'final class LogManager',
            flags: 'internal!',
            summary: 'Manages loggers.',
        });

        expect(content.querySelector('code').textContent).toBe('final class LogManager');
        expect(content.querySelector('em').textContent).toBe('Manages loggers.');
        expect(content.textContent).toContain('internal!');
    });

    it('renders a quote in the summary as a quote', async () => {
        const content = await openModal({
            code: '\\TYPO3\\CMS\\Backend\\Authentication\\Event\\SwitchUserEvent',
            signature: 'final class SwitchUserEvent',
            summary: 'Triggered when a "SU" action has been triggered',
        });

        expect(content.querySelector('em').textContent).toBe('Triggered when a "SU" action has been triggered');
        expect(content.textContent).not.toContain('&quot;');
    });

    it('keeps plain data-details working for roles without API info', async () => {
        const content = await openModal({
            code: "$GLOBALS['TYPO3_CONF_VARS']",
            details: 'The main configuration is achieved via a set of global settings.',
        });

        expect(content.textContent).toContain('The main configuration is achieved');
        expect(content.querySelector('code')).toBeNull();
    });

    it('escapes markup arriving in signature or summary', async () => {
        const content = await openModal({
            code: '\\Some\\Class',
            signature: '<img src=x onerror="window.__xss = true">',
            summary: '<img src=y onerror="window.__xss = true">',
        });

        expect(content.textContent).toContain('onerror');
        expect(content.querySelector('img')).toBeNull();
        expect(content.querySelector('[onerror]')).toBeNull();
        expect(window.__xss).toBeUndefined();
    });
});
