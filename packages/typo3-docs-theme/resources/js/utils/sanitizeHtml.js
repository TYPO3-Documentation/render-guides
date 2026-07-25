/**
 * Sanitize backend-provided highlight markup for safe rendering through
 * dangerouslySetInnerHTML.
 *
 * The /search/suggest endpoint returns snippet titles that may contain
 * highlight markup (e.g. <em>term</em>). That markup has to be preserved, but
 * everything else must be stripped: any other tag or attribute that reaches
 * innerHTML is an XSS vector (e.g. <img src=x onerror=...>).
 *
 * Strategy: parse the input with the browser's own HTML parser inside an inert
 * <template> (no resource loads and no script execution happen during
 * parsing), then rebuild the string from scratch keeping only text nodes
 * (re-escaped) and the allowed wrapper tags WITHOUT any attributes. Because the
 * output is reconstructed rather than filtered in place, obfuscated or nested
 * payloads cannot survive.
 */
const ALLOWED_TAGS = new Set(['EM', 'MARK']);

const escapeText = (value) =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

const serialize = (node) => {
    let out = '';
    node.childNodes.forEach((child) => {
        if (child.nodeType === Node.TEXT_NODE) {
            out += escapeText(child.nodeValue ?? '');
        } else if (child.nodeType === Node.ELEMENT_NODE) {
            const inner = serialize(child);
            if (ALLOWED_TAGS.has(child.tagName)) {
                const tag = child.tagName.toLowerCase();
                out += `<${tag}>${inner}</${tag}>`;
            } else {
                // Drop the disallowed element and all of its attributes, but
                // keep its (already sanitized) text content.
                out += inner;
            }
        }
    });
    return out;
};

export const sanitizeHighlight = (input) => {
    if (input == null) {
        return '';
    }
    const template = document.createElement('template');
    template.innerHTML = String(input);
    return serialize(template.content);
};
