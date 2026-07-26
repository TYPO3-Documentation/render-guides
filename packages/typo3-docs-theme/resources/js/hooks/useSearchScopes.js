import { useEffect, useState } from 'react';

// Build a scope slug that can never introduce a foreign origin. The slug ends
// up in buildHref() as "/" + slug and is used for navigation. The URL parser
// treats "\" as "/" and strips ASCII whitespace/control characters (tab, LF,
// CR), so values like "\evil.example" or "\t//evil.example" would otherwise
// resolve to "https://evil.example/". Drop ASCII control characters and
// backslashes, then collapse slash runs and remove empty segments, leaving a
// single-slash same-origin path.
const sanitizeScopeSlug = (value) => {
    let cleaned = '';
    for (const ch of value) {
        const code = ch.codePointAt(0);
        if (code <= 0x1f || code === 0x7f || code === 0x5c) {
            continue;
        }
        cleaned += ch;
    }
    return cleaned.split('/').filter(Boolean).join('/');
};

export const useSearchScopes = () => {
    const [scopes, setScopes] = useState([]);

    useEffect(() => {
        const initialScopes = [];
        const url = new URL(window.location.href);
        url.searchParams?.forEach((value, key) => {
            if (key === 'scope' && value) {
                const slug = sanitizeScopeSlug(decodeURIComponent(value));
                const packageName = slug.split('/').slice(1, 3).join('/');
                initialScopes.push({ type: 'manual', title: packageName, slug });
            } else if (key.startsWith('filters[')) {
                const filterExp = new RegExp(/filters\[(.*?)\]\[(.*?)\]/);
                const [, type, filterValue] = key.match(filterExp);
                initialScopes.push({
                    type: type === 'optionsaggs' ? 'option' : type,
                    title: filterValue
                });
            }
        });

        setScopes(initialScopes);
    }, []);

    return [scopes, setScopes];
};
