import React from 'react';
import { createRoot } from 'react-dom/client';
import GlobalSearch from './components/GlobalSearch';

export const PROXY_URL = 'https://docs.typo3.org/search';
// export const PROXY_URL = 'https://t3docs-search-indexer.ddev.site';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('global-search-root');
    if (container) {
        const root = createRoot(container);
        root.render(<GlobalSearch />);
    }
});
