import React from 'react';
import { createRoot } from 'react-dom/client';
import GlobalSearch from './components/GlobalSearch';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('global-search-root');
    if (container) {
        const root = createRoot(container);
        root.render(<GlobalSearch />);
    }
}); 